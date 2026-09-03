<?php

use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Mail\OrderReceipt;
use App\Mail\OrderReceivedNotification;
use App\Models\CampRegistration;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Mail;

function webhook(array $payload, string $signature = 'fake')
{
    return test()->call('POST', route('stripe.webhook'), [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], json_encode($payload));
}

test('an invalid signature is rejected', function () {
    webhook(['type' => 'checkout.session.completed', 'session_id' => 'cs_x'], 'bad')->assertStatus(400);
});

test('events without a session id or for unknown sessions are acknowledged', function () {
    webhook(['type' => 'checkout.session.completed'])->assertOk()->assertSee('Ignored');
    webhook(['type' => 'checkout.session.completed', 'session_id' => 'cs_unknown', 'payment_status' => 'paid'])->assertOk()->assertSee('Unknown order');
});

test('a completed checkout marks the order paid, decrements stock, and queues emails idempotently', function () {
    Mail::fake();

    $variant = ProductVariant::factory()->create(['stock' => 5]);
    $order = Order::factory()->create(['stripe_checkout_session_id' => 'cs_test_123']);
    OrderItem::factory()->for($order)->create(['product_variant_id' => $variant->id, 'quantity' => 2]);

    webhook([
        'type' => 'checkout.session.completed',
        'session_id' => 'cs_test_123',
        'payment_intent' => 'pi_abc',
        'payment_status' => 'paid',
    ])->assertOk();

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->paid_at)->not->toBeNull()
        ->and($order->stripe_payment_intent_id)->toBe('pi_abc')
        ->and($variant->refresh()->stock)->toBe(3);

    Mail::assertQueued(OrderReceipt::class, fn (OrderReceipt $mail) => $mail->hasTo($order->email));
    Mail::assertQueued(OrderReceivedNotification::class);

    // Replayed event does nothing more.
    webhook([
        'type' => 'checkout.session.completed',
        'session_id' => 'cs_test_123',
        'payment_intent' => 'pi_abc',
        'payment_status' => 'paid',
    ])->assertOk();

    expect($variant->refresh()->stock)->toBe(3);
    Mail::assertQueuedCount(2);
});

test('an unpaid completed event does not mark the order paid', function () {
    $order = Order::factory()->create(['stripe_checkout_session_id' => 'cs_unpaid']);

    webhook(['type' => 'checkout.session.completed', 'session_id' => 'cs_unpaid', 'payment_status' => 'unpaid'])->assertOk();

    expect($order->refresh()->status)->toBe(OrderStatus::Pending);
});

test('a completed camp checkout marks the registration paid', function () {
    Mail::fake();

    $order = Order::factory()->camp()->create(['stripe_checkout_session_id' => 'cs_camp']);
    OrderItem::factory()->for($order)->create(['product_variant_id' => null]);
    $registration = CampRegistration::factory()->pending()->create(['order_id' => $order->id]);

    webhook(['type' => 'checkout.session.completed', 'session_id' => 'cs_camp', 'payment_status' => 'paid'])->assertOk();

    $registration->refresh();

    expect($registration->status)->toBe(RegistrationStatus::Paid)
        ->and($registration->expires_at)->toBeNull();
});

test('an expired checkout cancels the pending order and its camp registration', function () {
    $order = Order::factory()->camp()->create(['stripe_checkout_session_id' => 'cs_expired']);
    $registration = CampRegistration::factory()->pending()->create(['order_id' => $order->id]);

    webhook(['type' => 'checkout.session.expired', 'session_id' => 'cs_expired'])->assertOk();

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($registration->refresh()->status)->toBe(RegistrationStatus::Cancelled);
});

test('an expired checkout leaves an already paid order alone', function () {
    $order = Order::factory()->paid()->create(['stripe_checkout_session_id' => 'cs_paid_then_expired']);

    webhook(['type' => 'checkout.session.expired', 'session_id' => 'cs_paid_then_expired'])->assertOk();

    expect($order->refresh()->status)->toBe(OrderStatus::Paid);
});
