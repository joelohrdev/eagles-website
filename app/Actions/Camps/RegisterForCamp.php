<?php

namespace App\Actions\Camps;

use App\Actions\Orders\CreateCheckoutSession;
use App\Actions\Orders\MarkOrderPaid;
use App\Contracts\PaymentGateway;
use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\RegistrationStatus;
use App\Exceptions\CampRegistrationClosed;
use App\Mail\CampRegistrationConfirmation;
use App\Mail\CampRegistrationReceived;
use App\Models\Camp;
use App\Models\CampRegistration;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Services\SiteSettings;
use App\Support\Payments\CheckoutSession;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Register a player for chosen days of a camp. Free selections (and ones a
 * coupon covers in full) are confirmed immediately; paid ones create a pending
 * order + registration hold and return a checkout session to redirect to.
 */
class RegisterForCamp
{
    /**
     * Unpaid holds one email can have on a camp at once. Holds take a spot
     * before payment, so without a cap one person (or a script) could fill a
     * capped camp without paying. Two allows siblings signed up together.
     */
    public const int MAX_UNPAID_HOLDS_PER_EMAIL = 2;

    public function __construct(
        private CreateCheckoutSession $createCheckoutSession,
        private QuoteCampSelection $quoteCampSelection,
        private MarkOrderPaid $markOrderPaid,
        private PaymentGateway $gateway,
        private ActivityLogger $activity,
        private SiteSettings $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Validated registration fields, plus `days` (day ids),
     *                                      `camp_age_group_id` and an optional `coupon_code`.
     * @return array{registration: CampRegistration, checkout: CheckoutSession|null}
     *
     * @throws CampRegistrationClosed
     * @throws ValidationException when the selection, age group or coupon is not valid or a slot is full.
     */
    public function handle(Camp $camp, array $data): array
    {
        $dayIds = array_map('intval', $data['days'] ?? []);
        $ageGroupId = isset($data['camp_age_group_id']) ? (int) $data['camp_age_group_id'] : null;
        $couponCode = $data['coupon_code'] ?? null;
        $attributes = Arr::except($data, ['days', 'camp_age_group_id', 'coupon_code']);

        /** @var array{registration: CampRegistration, order: Order|null} $result */
        $result = DB::transaction(function () use ($camp, $dayIds, $ageGroupId, $couponCode, $attributes): array {
            /** @var Camp $locked */
            $locked = Camp::query()->whereKey($camp->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isRegistrationOpen()) {
                throw new CampRegistrationClosed;
            }

            $quote = $this->quoteCampSelection->handle($locked, $dayIds, $ageGroupId, $couponCode, lockCoupon: true);

            if ($quote->total() > 0) {
                if (! $this->gateway->isAvailable()) {
                    throw ValidationException::withMessages(['registration' => __('Online payment isn’t available right now. Please contact us to register.')]);
                }

                $this->ensureHoldAllowed($locked, (string) $attributes['email']);
            }
            $playerName = trim($attributes['player_first_name'].' '.$attributes['player_last_name']);
            $order = null;

            if ($quote->subtotal > 0) {
                $coupon = $quote->appliedCoupon();

                $order = Order::query()->create([
                    'type' => OrderType::Camp,
                    'email' => $attributes['email'],
                    'name' => $attributes['parent_name'],
                    'phone' => $attributes['phone'],
                    'fulfillment' => Fulfillment::Pickup,
                    'subtotal' => $quote->subtotal,
                    'coupon_id' => $coupon?->id,
                    'coupon_code' => $quote->discountLabel(),
                    'discount' => $quote->discount(),
                    'total' => $quote->total(),
                    'status' => OrderStatus::Pending,
                ]);

                foreach ($quote->lines as $line) {
                    $order->items()->create([
                        'description' => "{$line->description()} — {$playerName}",
                        'unit_price' => $line->price,
                        'quantity' => 1,
                    ]);
                }
            }

            $needsPayment = $order !== null && $quote->total() > 0;

            $registration = $locked->registrations()->create([
                ...$attributes,
                'camp_age_group_id' => $locked->ageGroups->isEmpty() ? null : $ageGroupId,
                'order_id' => $order?->id,
                'status' => $needsPayment ? RegistrationStatus::PendingPayment : RegistrationStatus::Paid,
                'registered_at' => now(),
                'expires_at' => $needsPayment ? now()->addMinutes(CampRegistration::PENDING_HOLD_MINUTES) : null,
            ]);

            $registration->days()->sync(collect($quote->lines)->flatMap(fn ($line) => $line->days->pluck('id'))->all());

            return ['registration' => $registration, 'order' => $order];
        });

        $registration = $result['registration']->setRelation('camp', $camp);
        $order = $result['order'];

        $this->activity->log('camp_registration.created', "{$registration->playerName()} registered for {$camp->name}", $registration, [
            'camp' => $camp->name,
            'player' => $registration->playerName(),
            'parent' => $registration->parent_name,
            'email' => $registration->email,
            'age_group' => $registration->ageGroup?->name,
            'days' => $registration->scheduleLines(),
            'order' => $order?->number,
            'total' => $order?->total ?? 0,
            'discount' => $order?->discount ?? 0,
            'discount_label' => $order?->discount > 0 ? $order->coupon_code : null,
            'status' => $registration->status->label(),
        ]);

        if ($order !== null && $order->total === 0) {
            /** A coupon covered the whole price: confirm it like a paid order (receipt + org notice), with no trip to Stripe. */
            $this->markOrderPaid->handle($order);

            return ['registration' => $registration->refresh()->setRelation('camp', $camp), 'checkout' => null];
        }

        if ($order === null) {
            Mail::to($registration->email)->queue(new CampRegistrationConfirmation($registration));

            if ($orgEmail = $this->settings->get('email')) {
                Mail::to($orgEmail)->queue(new CampRegistrationReceived($registration));
            }

            return ['registration' => $registration, 'checkout' => null];
        }

        $checkout = $this->createCheckoutSession->handle(
            $order,
            URL::signedRoute('checkout.success', ['order' => $order->number]),
            URL::signedRoute('checkout.cancel', ['order' => $order->number]),
        );

        return ['registration' => $registration, 'checkout' => $checkout];
    }

    /**
     * @throws ValidationException when this email already has the maximum unpaid holds on the camp.
     */
    private function ensureHoldAllowed(Camp $camp, string $email): void
    {
        $holds = $camp->registrations()
            ->where('status', RegistrationStatus::PendingPayment)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereRaw('lower(email) = ?', [Str::lower(trim($email))])
            ->count();

        if ($holds >= self::MAX_UNPAID_HOLDS_PER_EMAIL) {
            throw ValidationException::withMessages(['email' => __(
                'This email already has :count unpaid spots on hold for this camp. Finish paying for them, or wait up to :minutes minutes for the holds to end.',
                ['count' => $holds, 'minutes' => CampRegistration::PENDING_HOLD_MINUTES],
            )]);
        }
    }
}
