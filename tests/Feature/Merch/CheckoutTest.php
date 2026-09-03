<?php

use App\Actions\Orders\MarkOrderPaid;
use App\Enums\OrderStatus;
use App\Mail\OrderReceipt;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

function checkoutPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Pat Parent',
        'email' => 'pat@example.com',
        'phone' => '630-555-0100',
        'fulfillment' => 'pickup',
    ], $overrides);
}

test('checkout page redirects to cart when empty', function () {
    $this->get(route('checkout.create'))->assertRedirect(route('cart.index'));
});

test('checkout page renders with cart contents', function () {
    $variant = ProductVariant::factory()->create();

    $this->withSession(['cart' => [$variant->id => 2]])
        ->get(route('checkout.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('site/Checkout/Create')->where('cart.count', 2));
});

test('checkout creates a pending order with snapshot items and redirects to the gateway', function () {
    $variant = ProductVariant::factory()->create(['size' => 'M', 'color' => 'Navy']);
    $variant->product->update(['price' => 2500]);

    $response = $this->withSession(['cart' => [$variant->id => 2]])
        ->withHeaders(['X-Inertia' => 'true'])
        ->post(route('checkout.store'), checkoutPayload());

    $order = Order::query()->firstOrFail();

    $response->assertStatus(409)
        ->assertHeader('X-Inertia-Location', URL::signedRoute('checkout.success', ['order' => $order->number]));

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->total)->toBe(5000)
        ->and($order->stripe_checkout_session_id)->toStartWith('cs_fake_')
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->description)->toBe($variant->product->name)
        ->and($order->items->first()->size)->toBe('M')
        ->and($order->items->first()->unit_price)->toBe(2500)
        ->and($order->items->first()->quantity)->toBe(2);

    // Cart is kept until payment is confirmed.
    expect(session('cart'))->toBe([$variant->id => 2]);
});

test('checkout without inertia header redirects to the gateway url', function () {
    $variant = ProductVariant::factory()->create();

    $this->withSession(['cart' => [$variant->id => 1]])
        ->post(route('checkout.store'), checkoutPayload())
        ->assertRedirect();
});

test('checkout requires shipping fields when shipping', function () {
    $variant = ProductVariant::factory()->create();

    $this->withSession(['cart' => [$variant->id => 1]])
        ->from(route('checkout.create'))
        ->post(route('checkout.store'), checkoutPayload(['fulfillment' => 'shipping']))
        ->assertSessionHasErrors(['shipping_address_line1', 'shipping_city', 'shipping_state', 'shipping_postal_code']);

    expect(Order::query()->count())->toBe(0);
});

test('checkout fails when stock is insufficient', function () {
    $variant = ProductVariant::factory()->create(['stock' => 1]);

    $this->withSession(['cart' => [$variant->id => 3]])
        ->from(route('checkout.create'))
        ->post(route('checkout.store'), checkoutPayload())
        ->assertSessionHasErrors('cart');

    expect(Order::query()->count())->toBe(0);
});

test('honeypot submissions are silently ignored', function () {
    $variant = ProductVariant::factory()->create();

    $this->withSession(['cart' => [$variant->id => 1]])
        ->post(route('checkout.store'), checkoutPayload(['website' => 'http://spam.example']))
        ->assertRedirect(route('cart.index'));

    expect(Order::query()->count())->toBe(0);
});

test('success page shows pending state and then clears the cart once paid', function () {
    Mail::fake();
    $variant = ProductVariant::factory()->create();
    $order = Order::factory()->create(['stripe_checkout_session_id' => 'cs_fake_abc']);
    $order->items()->create(['product_variant_id' => $variant->id, 'description' => 'Tee', 'unit_price' => 2500, 'quantity' => 1]);
    $url = URL::signedRoute('checkout.success', ['order' => $order->number]);

    $this->withSession(['cart' => [$variant->id => 1]])
        ->get($url)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('site/Checkout/Success')
            ->where('order.is_pending', true)
            ->where('order.is_paid', false));

    expect(session('cart'))->toBe([$variant->id => 1]);

    app(MarkOrderPaid::class)->handle($order);

    $this->withSession(['cart' => [$variant->id => 1]])
        ->get($url)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('order.is_paid', true)->where('order.number', $order->number));

    expect(session('cart'))->toBeNull();
});

test('success page requires a valid signature', function () {
    $order = Order::factory()->create();

    $this->get(route('checkout.success', ['order' => $order->number]))->assertForbidden();
});

test('cancel releases a pending merch order and keeps the cart', function () {
    $variant = ProductVariant::factory()->create();
    $order = Order::factory()->create();

    $this->withSession(['cart' => [$variant->id => 1]])
        ->get(URL::signedRoute('checkout.cancel', ['order' => $order->number]))
        ->assertRedirect(route('cart.index'));

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and(session('cart'))->toBe([$variant->id => 1]);
});

test('webhook marks the order paid, decrements stock, and queues the receipt', function () {
    Mail::fake();
    $variant = ProductVariant::factory()->create(['stock' => 5]);
    $order = Order::factory()->create(['stripe_checkout_session_id' => 'cs_fake_123']);
    $order->items()->create(['product_variant_id' => $variant->id, 'description' => 'Tee', 'unit_price' => 2500, 'quantity' => 2]);

    $this->postJson(route('stripe.webhook'), [
        'type' => 'checkout.session.completed',
        'session_id' => 'cs_fake_123',
        'payment_intent' => 'pi_test_1',
        'payment_status' => 'paid',
    ], ['Stripe-Signature' => 'fake'])->assertOk();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->stripe_payment_intent_id)->toBe('pi_test_1')
        ->and($order->paid_at)->not->toBeNull()
        ->and($variant->fresh()->stock)->toBe(3);

    Mail::assertQueued(OrderReceipt::class, fn (OrderReceipt $mail) => $mail->order->is($order) && $mail->hasTo($order->email));
});

test('webhook is idempotent for already paid orders', function () {
    Mail::fake();
    $variant = ProductVariant::factory()->create(['stock' => 5]);
    $order = Order::factory()->paid()->create(['stripe_checkout_session_id' => 'cs_fake_dup']);
    $order->items()->create(['product_variant_id' => $variant->id, 'description' => 'Tee', 'unit_price' => 2500, 'quantity' => 2]);

    $this->postJson(route('stripe.webhook'), [
        'type' => 'checkout.session.completed',
        'session_id' => 'cs_fake_dup',
        'payment_status' => 'paid',
    ], ['Stripe-Signature' => 'fake'])->assertOk();

    expect($variant->fresh()->stock)->toBe(5);
    Mail::assertNothingQueued();
});

test('webhook expired session cancels the pending order', function () {
    $order = Order::factory()->create(['stripe_checkout_session_id' => 'cs_fake_exp']);

    $this->postJson(route('stripe.webhook'), [
        'type' => 'checkout.session.expired',
        'session_id' => 'cs_fake_exp',
    ], ['Stripe-Signature' => 'fake'])->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});

test('webhook rejects invalid signatures', function () {
    $this->postJson(route('stripe.webhook'), ['type' => 'checkout.session.completed'], ['Stripe-Signature' => 'nope'])
        ->assertStatus(400);
});
