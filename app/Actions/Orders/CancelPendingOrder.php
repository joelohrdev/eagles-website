<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\RegistrationStatus;
use App\Models\Order;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

/**
 * Cancel an order that was never paid (checkout abandoned/expired) and release any held spot.
 */
class CancelPendingOrder
{
    public function __construct(private ActivityLogger $activity) {}

    /**
     * @param  string  $reason  Why, for the activity log ("Customer left the payment page", ...).
     */
    public function handle(Order $order, string $reason = 'Cancelled'): Order
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

        $order->refresh()->loadMissing('campRegistration');

        $this->activity->log('order.cancelled', "Order {$order->number} cancelled: {$reason}", $order, array_filter([
            'reason' => $reason,
            'player' => $order->campRegistration?->playerName(),
            'total' => $order->total,
        ], fn ($value) => $value !== null));

        return $order;
    }
}
