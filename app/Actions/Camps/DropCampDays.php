<?php

namespace App\Actions\Camps;

use App\Actions\Orders\RefundOrder;
use App\Contracts\PaymentGateway;
use App\Enums\RegistrationStatus;
use App\Mail\CampRegistrationChanged;
use App\Models\CampRegistration;
use App\Models\Order;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Take days off a paid camp registration (a player who can't make a session)
 * and give back the difference. The suggested refund is what they paid minus
 * what the days they keep would cost on their own, priced the same way as
 * sign-up: $225 for both sessions, keeping one $125 session, gives back $100.
 */
class DropCampDays
{
    public function __construct(
        private QuoteCampSelection $quoteCampSelection,
        private PaymentGateway $gateway,
        private ActivityLogger $activity,
        private RefundOrder $refundOrder,
    ) {}

    /**
     * Cancel a paid registration outright and refund everything not already
     * given back. The Stripe refund goes first: if it's turned down nothing
     * changes, and if it goes through, the order and registration are marked
     * refunded (Stripe's refund webhook would do the same, so a hiccup after
     * the refund still ends in the right state). A free sign-up is just cancelled.
     *
     * @throws ValidationException when the registration isn't paid, or Stripe turns the refund down.
     */
    public function cancelAndRefund(CampRegistration $registration): CampRegistration
    {
        if ($registration->status !== RegistrationStatus::Paid) {
            throw ValidationException::withMessages(['registration' => __('Only a paid registration can be cancelled and refunded.')]);
        }

        $order = $registration->order;

        if ($order === null || $order->amountKept() === 0) {
            $registration->forceFill(['status' => RegistrationStatus::Cancelled])->save();
            $this->activity->log('camp_registration.cancelled', "{$registration->playerName()}'s registration cancelled by staff", $registration, [
                'player' => $registration->playerName(),
            ]);

            return $registration;
        }

        DB::transaction(function () use ($order): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPaid() || $locked->amountKept() === 0) {
                throw ValidationException::withMessages(['registration' => __('This order has already been refunded.')]);
            }

            try {
                $this->gateway->refund($locked, $locked->amountKept());
            } catch (Throwable $e) {
                report($e);

                throw ValidationException::withMessages(['registration' => __('Stripe didn’t make the refund, so nothing was changed: :reason', ['reason' => $e->getMessage()])]);
            }

            $this->refundOrder->handle($locked);
        });

        return $registration->refresh();
    }

    /**
     * @param  list<int>  $dropDayIds
     * @return array{kept_day_ids: list<int>, kept_price: int, paid: int, suggested_refund: int, max_refund: int}
     *
     * @throws ValidationException
     */
    public function preview(CampRegistration $registration, array $dropDayIds): array
    {
        if ($registration->status !== RegistrationStatus::Paid) {
            throw ValidationException::withMessages(['days' => __('Only a paid registration can have days taken off.')]);
        }

        $heldDayIds = $registration->days()->pluck('camp_session_days.id')->map(fn ($id): int => (int) $id)->all();
        $dropDayIds = array_values(array_unique(array_map('intval', $dropDayIds)));

        if ($dropDayIds === [] || array_diff($dropDayIds, $heldDayIds) !== []) {
            throw ValidationException::withMessages(['days' => __('Choose days this player is booked on.')]);
        }

        $keptDayIds = array_values(array_diff($heldDayIds, $dropDayIds));

        if ($keptDayIds === []) {
            throw ValidationException::withMessages(['days' => __('Keep at least one day. To cancel everything, refund the whole order in Stripe.')]);
        }

        $order = $registration->order;
        $keptPrice = $this->quoteCampSelection
            ->reprice($registration->camp, $keptDayIds, $order?->coupon)
            ->total();
        $paid = $order?->amountKept() ?? 0;

        return [
            'kept_day_ids' => $keptDayIds,
            'kept_price' => $keptPrice,
            'paid' => $paid,
            'suggested_refund' => max(0, $paid - $keptPrice),
            'max_refund' => $paid,
        ];
    }

    /**
     * @param  list<int>  $dropDayIds
     * @param  int  $refund  Cents to give back; staff may change the suggestion, up to what was paid.
     *
     * @throws ValidationException when the days or amount aren't valid, or Stripe turns the refund down (nothing is changed then).
     */
    public function handle(CampRegistration $registration, array $dropDayIds, int $refund): CampRegistration
    {
        $dropped = $registration->days()->whereIn('camp_session_days.id', $dropDayIds)->get()->map->title()->all();

        DB::transaction(function () use ($registration, $dropDayIds, $refund): void {
            $order = $registration->order_id
                ? Order::query()->whereKey($registration->order_id)->lockForUpdate()->firstOrFail()
                : null;
            $registration->setRelation('order', $order);
            $preview = $this->preview($registration, $dropDayIds);

            if ($refund < 0 || $refund > $preview['max_refund']) {
                throw ValidationException::withMessages(['refund' => __('The refund can be up to $:max.', ['max' => number_format($preview['max_refund'] / 100, 2)])]);
            }

            $registration->days()->sync($preview['kept_day_ids']);

            if ($refund > 0 && $order !== null) {
                $order->forceFill(['refunded_amount' => $order->refunded_amount + $refund])->save();

                /** Last, so if Stripe turns it down the booking change above is rolled back too. */
                try {
                    $this->gateway->refund($order, $refund);
                } catch (Throwable $e) {
                    report($e);

                    throw ValidationException::withMessages(['refund' => __('Stripe didn’t make the refund, so nothing was changed: :reason', ['reason' => $e->getMessage()])]);
                }
            }
        });

        $registration->refresh()->load(['camp', 'order', 'days']);

        $this->activity->log('camp_registration.days_dropped', "{$registration->playerName()} dropped ".implode(', ', $dropped).($refund > 0 ? ' — $'.number_format($refund / 100, 2).' refunded' : ''), $registration, array_filter([
            'player' => $registration->playerName(),
            'dropped' => $dropped,
            'kept' => $registration->scheduleLines(),
            'refund' => $refund ?: null,
            'order' => $registration->order?->number,
        ], fn ($value) => $value !== null));

        Mail::to($registration->email)->queue(new CampRegistrationChanged($registration, $refund));

        return $registration;
    }
}
