<?php

namespace App\Actions\Camps;

use App\Actions\Orders\CreateCheckoutSession;
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
use App\Services\SiteSettings;
use App\Support\Payments\CheckoutSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Register a player for a camp. Free camps are confirmed immediately;
 * paid camps create a pending order + registration hold and return a
 * checkout session the caller should redirect to.
 */
class RegisterForCamp
{
    public function __construct(
        private CreateCheckoutSession $createCheckoutSession,
        private SiteSettings $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Validated registration fields.
     * @return array{registration: CampRegistration, checkout: CheckoutSession|null}
     *
     * @throws CampRegistrationClosed
     */
    public function handle(Camp $camp, array $data): array
    {
        /** @var array{registration: CampRegistration, order: Order|null} $result */
        $result = DB::transaction(function () use ($camp, $data): array {
            /** @var Camp $locked */
            $locked = Camp::query()->whereKey($camp->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isRegistrationOpen()) {
                throw new CampRegistrationClosed;
            }

            $playerName = trim($data['player_first_name'].' '.$data['player_last_name']);
            $order = null;

            if (! $locked->isFree()) {
                $order = Order::query()->create([
                    'type' => OrderType::Camp,
                    'email' => $data['email'],
                    'name' => $data['parent_name'],
                    'phone' => $data['phone'],
                    'fulfillment' => Fulfillment::Pickup,
                    'subtotal' => $locked->price,
                    'total' => $locked->price,
                    'status' => OrderStatus::Pending,
                ]);

                $order->items()->create([
                    'description' => "{$locked->name} — {$playerName}",
                    'unit_price' => $locked->price,
                    'quantity' => 1,
                ]);
            }

            $registration = $locked->registrations()->create([
                ...$data,
                'order_id' => $order?->id,
                'status' => $order ? RegistrationStatus::PendingPayment : RegistrationStatus::Paid,
                'registered_at' => now(),
                'expires_at' => $order ? now()->addMinutes(CampRegistration::PENDING_HOLD_MINUTES) : null,
            ]);

            return ['registration' => $registration, 'order' => $order];
        });

        $registration = $result['registration']->setRelation('camp', $camp);
        $order = $result['order'];

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
}
