<?php

use App\Enums\CouponScope;
use App\Enums\OrderStatus;
use App\Mail\OrderReceipt;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Two $25 shirts in the cart.
 */
function shirtsInCart(): ProductVariant
{
    $variant = ProductVariant::factory()->create();
    $variant->product->update(['price' => 2500]);
    test()->withSession(['cart' => [$variant->id => 2]]);

    return $variant;
}

function merchCheckout(array $overrides = []): array
{
    return array_merge(['name' => 'Pat Parent', 'email' => 'pat@example.com', 'phone' => '630-555-0100', 'fulfillment' => 'pickup'], $overrides);
}

test('the checkout quote prices the cart with a coupon', function (Closure $coupon, int $discount) {
    shirtsInCart();
    $coupon();

    $this->postJson(route('checkout.quote'), ['coupon_code' => 'shirts'])
        ->assertOk()
        ->assertJson(['subtotal' => 5000, 'discount' => $discount, 'coupon_code' => 'SHIRTS', 'total' => 5000 - $discount]);
})->with([
    '20% off' => [fn () => Coupon::factory()->percent(20)->create(['code' => 'SHIRTS']), 1000],
    '$15 off the order' => [fn () => Coupon::factory()->fixedOrder(1500)->create(['code' => 'SHIRTS']), 1500],
    '$5 off each item' => [fn () => Coupon::factory()->fixedPerItem(500)->create(['code' => 'SHIRTS']), 1000],
]);

test('camp-only coupons are turned down at merch checkout', function () {
    shirtsInCart();
    Coupon::factory()->scope(CouponScope::Camps)->create(['code' => 'CAMPS']);

    $this->postJson(route('checkout.quote'), ['coupon_code' => 'CAMPS'])
        ->assertJsonValidationErrors(['coupon_code' => 'That coupon code can’t be used for merch.']);

    $this->post(route('checkout.store'), merchCheckout(['coupon_code' => 'CAMPS']))
        ->assertSessionHasErrors('coupon_code');

    expect(Order::count())->toBe(0);
});

test('placing a merch order records the coupon and discount', function () {
    shirtsInCart();
    $coupon = Coupon::factory()->percent(20)->create(['code' => 'SHIRTS']);

    $this->post(route('checkout.store'), merchCheckout(['coupon_code' => 'shirts']));

    $order = Order::query()->sole();

    expect($order->subtotal)->toBe(5000)
        ->and($order->discount)->toBe(1000)
        ->and($order->total)->toBe(4000)
        ->and($order->coupon_id)->toBe($coupon->id)
        ->and($order->coupon_code)->toBe('SHIRTS')
        ->and($order->status)->toBe(OrderStatus::Pending);
});

test('a merch order a coupon covers in full is confirmed without Stripe', function () {
    Mail::fake();
    shirtsInCart();
    Coupon::factory()->percent(100)->create(['code' => 'FREE']);

    $this->post(route('checkout.store'), merchCheckout(['coupon_code' => 'FREE']))
        ->assertRedirect(URL::signedRoute('checkout.success', ['order' => Order::query()->sole()->number]));

    $order = Order::query()->sole();

    expect($order->total)->toBe(0)
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($order->stripe_checkout_session_id)->toBeNull();

    Mail::assertQueued(OrderReceipt::class);
});

test('a misspelled code says it does not exist', function () {
    shirtsInCart();
    Coupon::factory()->percent(20)->create(['code' => 'THANKS30']);

    $this->postJson(route('checkout.quote'), ['coupon_code' => 'THANK30'])
        ->assertJsonValidationErrors(['coupon_code' => 'That coupon code doesn’t exist. Check the spelling and try again.']);
});
