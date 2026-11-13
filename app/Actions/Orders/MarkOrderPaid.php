<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\RegistrationStatus;
use App\Mail\CampRegistrationConfirmation;
use App\Mail\OrderReceipt;
use App\Mail\OrderReceivedNotification;
use App\Models\Camp;
use App\Models\CampRegistration;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Services\SiteSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Transition an order to paid, finalize what it purchased, and send emails:
 * the receipt, a camp confirmation with the schedule for camp orders, and the org notice.
 * Idempotent: an already-paid order is left untouched.
 *
 * A payment can land after its hold ended and the order was cancelled (a slow
 * webhook). The money is recorded either way; the camp spot is given back only
 * if there is still room, otherwise staff are warned in the activity log.
 */
class MarkOrderPaid
{
    public function __construct(
        private SiteSettings $settings,
        private ActivityLogger $activity,
    ) {}

    public function handle(Order $order, ?string $paymentIntentId = null): Order
    {
        if (! $order->isPending() && $order->status !== OrderStatus::Cancelled) {
            return $order;
        }

        $spotKept = true;

        $updated = DB::transaction(function () use ($order, $paymentIntentId, &$spotKept): bool {
            /** Re-read under a lock so two deliveries of the same webhook can't both confirm it. */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending() && $locked->status !== OrderStatus::Cancelled) {
                return false;
            }

            $wasCancelled = $locked->status === OrderStatus::Cancelled;

            $locked->forceFill([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
                'stripe_payment_intent_id' => $paymentIntentId ?? $locked->stripe_payment_intent_id,
            ])->save();

            if ($locked->type === OrderType::Camp && $locked->campRegistration) {
                $spotKept = ! $wasCancelled || $this->hasRoomFor($locked->campRegistration);

                if ($spotKept) {
                    $locked->campRegistration->forceFill([
                        'status' => RegistrationStatus::Paid,
                        'expires_at' => null,
                    ])->save();
                }
            }

            if ($locked->type === OrderType::Merch) {
                $locked->loadMissing('items.variant');

                foreach ($locked->items as $item) {
                    if ($item->variant && $item->variant->stock !== null) {
                        $item->variant->decrement('stock', min($item->quantity, $item->variant->stock));
                    }
                }
            }

            return true;
        });

        if (! $updated) {
            return $order->refresh();
        }

        $order->refresh()->load(['items', 'campRegistration.camp']);

        $amount = '$'.number_format($order->total / 100, 2);
        $this->activity->log('order.paid', $order->total > 0
            ? "Payment of {$amount} received for order {$order->number}"
            : "Order {$order->number} confirmed (nothing to pay)", $order, array_filter([
                'type' => $order->type->label(),
                'player' => $order->campRegistration?->playerName(),
                'camp' => $order->campRegistration?->camp?->name,
                'total' => $order->total,
                'discount' => $order->discount ?: null,
                'discount_label' => $order->discount > 0 ? $order->coupon_code : null,
                'payment_intent' => $order->stripe_payment_intent_id,
            ], fn ($value) => $value !== null));

        if (! $spotKept) {
            $this->activity->log('order.paid_without_spot', "Order {$order->number} was paid after its hold ended and the camp is now full — refund it or make room", $order, array_filter([
                'player' => $order->campRegistration?->playerName(),
                'camp' => $order->campRegistration?->camp?->name,
                'total' => $order->total,
            ], fn ($value) => $value !== null));
        }

        Mail::to($order->email)->queue(new OrderReceipt($order));

        if ($order->campRegistration && $spotKept) {
            /** The receipt covers the money; this one tells the family where and when to show up. */
            Mail::to($order->campRegistration->email)->queue(new CampRegistrationConfirmation($order->campRegistration));
        }

        if ($orgEmail = $this->settings->get('email')) {
            Mail::to($orgEmail)->queue(new OrderReceivedNotification($order));
        }

        return $order;
    }

    /**
     * Whether every day of a released registration still has a free slot for its age group.
     */
    private function hasRoomFor(CampRegistration $registration): bool
    {
        /** @var Camp $camp */
        $camp = Camp::query()->whereKey($registration->camp_id)->lockForUpdate()->firstOrFail();

        return $registration->days()->pluck('camp_session_days.id')->every(
            fn (int $dayId): bool => $camp->slotRemaining($dayId, $registration->camp_age_group_id) !== 0,
        );
    }
}
