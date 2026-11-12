<?php

namespace App\Actions\Orders;

use App\Actions\Coupons\ResolveCoupon;
use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\Cart;
use App\Support\Coupons\CouponCalculator;
use App\Support\Coupons\DiscountableLine;
use Illuminate\Support\Collection;
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

    public function __construct(
        private Cart $cart,
        private ResolveCoupon $resolveCoupon,
    ) {}

    /**
     * What the cart costs with an optional coupon, for the checkout page's live total.
     *
     * @return array{subtotal: int, discount: int, coupon_code: string|null, shipping: int, total: int}
     *
     * @throws ValidationException when the coupon can't be used.
     */
    public function quote(?string $couponCode, Fulfillment $fulfillment = Fulfillment::Pickup): array
    {
        return $this->price(
            $this->cart->lines(),
            $this->resolveCoupon->handle($couponCode, OrderType::Merch),
            $fulfillment,
        );
    }

    /**
     * @param  Collection<int, array{unit_price: int, quantity: int, line_total: int}>  $lines
     * @return array{subtotal: int, discount: int, coupon_code: string|null, shipping: int, total: int}
     */
    private function price(Collection $lines, ?Coupon $coupon, Fulfillment $fulfillment): array
    {
        $subtotal = (int) $lines->sum('line_total');
        $discount = $coupon ? CouponCalculator::discount(
            $coupon,
            $lines->map(fn (array $line): DiscountableLine => new DiscountableLine($line['unit_price'], $line['quantity']))->values()->all(),
        ) : 0;
        $shipping = $fulfillment === Fulfillment::Shipping ? self::SHIPPING_FLAT_CENTS : 0;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'coupon_code' => $discount > 0 ? $coupon?->code : null,
            'shipping' => $shipping,
            'total' => $subtotal - $discount + $shipping,
        ];
    }

    /**
     * @param  array<string, mixed>  $customer  Validated checkout fields, with an optional `coupon_code`.
     *
     * @throws ValidationException when the cart is empty, stock is insufficient or the coupon can't be used.
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

            $fulfillment = Fulfillment::from($customer['fulfillment']);
            $coupon = $this->resolveCoupon->handle($customer['coupon_code'] ?? null, OrderType::Merch, lock: true);
            $price = $this->price($lines, $coupon, $fulfillment);

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
                'subtotal' => $price['subtotal'],
                'coupon_id' => $price['coupon_code'] !== null ? $coupon?->id : null,
                'coupon_code' => $price['coupon_code'],
                'discount' => $price['discount'],
                'total' => $price['total'],
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
