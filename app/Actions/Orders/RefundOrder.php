<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\RegistrationStatus;
use App\Mail\OrderRefunded;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Mark a paid or fulfilled order refunded after Stripe reports a full refund,
 * releasing any camp spot it held, and email the customer. Idempotent:
 * anything not paid is left alone and gets no second email.
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

        Mail::to($order->email)->queue(new OrderRefunded($order->refresh()));

        return $order;
    }
}
