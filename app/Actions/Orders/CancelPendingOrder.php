<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\RegistrationStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Cancel an order that was never paid (checkout abandoned/expired) and release any held spot.
 */
class CancelPendingOrder
{
    public function handle(Order $order): Order
    {
        if (! $order->isPending()) {
            return $order;
        }

        DB::transaction(function () use ($order): void {
            $order->forceFill(['status' => OrderStatus::Cancelled])->save();

            if ($order->type === OrderType::Camp) {
                $order->campRegistration?->forceFill(['status' => RegistrationStatus::Cancelled])->save();
            }
        });

        return $order->refresh();
    }
}
