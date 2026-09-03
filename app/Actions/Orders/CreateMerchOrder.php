<?php

namespace App\Actions\Orders;

use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turn the session cart into a pending merch order with snapshotted line items.
 */
class CreateMerchOrder
{
    /**
     * Flat shipping charge in cents. Shipping is currently free / pickup-focused.
     */
    public const int SHIPPING_FLAT_CENTS = 0;

    public function __construct(private Cart $cart) {}

    /**
     * @param  array<string, mixed>  $customer  Validated checkout fields.
     *
     * @throws ValidationException when the cart is empty or stock is insufficient.
     */
    public function handle(array $customer): Order
    {
        return DB::transaction(function () use ($customer): Order {
            $lines = $this->cart->lines();

            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['cart' => __('Your cart is empty.')]);
            }

            $variantIds = $lines->pluck('variant.id')->all();
            $locked = ProductVariant::query()->whereKey($variantIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($lines as $line) {
                /** @var ProductVariant $variant */
                $variant = $locked->get($line['variant']->id);

                if (! $variant->isInStock($line['quantity'])) {
                    throw ValidationException::withMessages([
                        'cart' => __(':item only has :stock left in stock.', [
                            'item' => $line['variant']->product->name.' ('.$line['variant']->label().')',
                            'stock' => $variant->stock,
                        ]),
                    ]);
                }
            }

            $subtotal = (int) $lines->sum('line_total');
            $fulfillment = Fulfillment::from($customer['fulfillment']);
            $shipping = $fulfillment === Fulfillment::Shipping ? self::SHIPPING_FLAT_CENTS : 0;

            $order = Order::query()->create([
                'type' => OrderType::Merch,
                'email' => $customer['email'],
                'name' => $customer['name'],
                'phone' => $customer['phone'] ?? null,
                'fulfillment' => $fulfillment,
                'shipping_address_line1' => $customer['shipping_address_line1'] ?? null,
                'shipping_address_line2' => $customer['shipping_address_line2'] ?? null,
                'shipping_city' => $customer['shipping_city'] ?? null,
                'shipping_state' => $customer['shipping_state'] ?? null,
                'shipping_postal_code' => $customer['shipping_postal_code'] ?? null,
                'subtotal' => $subtotal,
                'total' => $subtotal + $shipping,
                'status' => OrderStatus::Pending,
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_variant_id' => $line['variant']->id,
                    'description' => $line['variant']->product->name,
                    'size' => $line['variant']->size,
                    'color' => $line['variant']->color,
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                ]);
            }

            return $order;
        });
    }
}
