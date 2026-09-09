<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\RegistrationStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Mark a paid or fulfilled order refunded after Stripe reports a full refund,
 * releasing any camp spot it held. Idempotent: anything not paid is left alone.
 */
class RefundOrder
{
    public function handle(Order $order): Order
    {
        if (! $order->isPaid()) {
            return $order;
        }

        DB::transaction(function () use ($order): void {
            $order->forceFill(['status' => OrderStatus::Refunded])->save();

            if ($order->type === OrderType::Camp) {
                $order->campRegistration?->forceFill(['status' => RegistrationStatus::Refunded])->save();
            }
        });

        return $order->refresh();
    }
}
