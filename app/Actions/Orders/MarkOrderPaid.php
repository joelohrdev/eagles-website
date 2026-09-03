<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\RegistrationStatus;
use App\Mail\OrderReceipt;
use App\Mail\OrderReceivedNotification;
use App\Models\Order;
use App\Services\SiteSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Transition an order to paid, finalize what it purchased, and send emails.
 * Idempotent: an already-paid order is left untouched.
 */
class MarkOrderPaid
{
    public function __construct(private SiteSettings $settings) {}

    public function handle(Order $order, ?string $paymentIntentId = null): Order
    {
        if ($order->isPaid()) {
            return $order;
        }

        DB::transaction(function () use ($order, $paymentIntentId): void {
            $order->forceFill([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
                'stripe_payment_intent_id' => $paymentIntentId ?? $order->stripe_payment_intent_id,
            ])->save();

            if ($order->type === OrderType::Camp) {
                $order->campRegistration?->forceFill([
                    'status' => RegistrationStatus::Paid,
                    'expires_at' => null,
                ])->save();
            }

            if ($order->type === OrderType::Merch) {
                $order->loadMissing('items.variant');

                foreach ($order->items as $item) {
                    if ($item->variant && $item->variant->stock !== null) {
                        $item->variant->decrement('stock', min($item->quantity, $item->variant->stock));
                    }
                }
            }
        });

        $order->refresh()->load(['items', 'campRegistration.camp']);

        Mail::to($order->email)->queue(new OrderReceipt($order));

        if ($orgEmail = $this->settings->get('email')) {
            Mail::to($orgEmail)->queue(new OrderReceivedNotification($order));
        }

        return $order;
    }
}
