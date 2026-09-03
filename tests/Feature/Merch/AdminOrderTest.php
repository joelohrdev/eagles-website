<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

beforeEach(function () {
    $this->staff = User::factory()->staff()->create();
});

test('staff can list and filter orders', function () {
    Order::factory()->paid()->create(['email' => 'alice@example.com']);
    Order::factory()->create(['email' => 'bob@example.com']);

    $this->actingAs($this->staff)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/orders/Index')->has('orders.data', 2));

    $this->actingAs($this->staff)
        ->get(route('admin.orders.index', ['status' => 'paid']))
        ->assertInertia(fn ($page) => $page->has('orders.data', 1)->where('orders.data.0.email', 'alice@example.com'));

    $this->actingAs($this->staff)
        ->get(route('admin.orders.index', ['q' => 'bob']))
        ->assertInertia(fn ($page) => $page->has('orders.data', 1)->where('orders.data.0.email', 'bob@example.com'));
});

test('staff can view an order', function () {
    $order = Order::factory()->paid()->create();
    OrderItem::factory()->for($order)->create();

    $this->actingAs($this->staff)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/orders/Show')
            ->where('order.number', $order->number)
            ->has('order.items', 1)
            ->where('stripeUrl', 'https://dashboard.stripe.com/payments/'.$order->stripe_payment_intent_id));
});

test('staff can mark a paid order fulfilled with notes', function () {
    $order = Order::factory()->paid()->create();

    $this->actingAs($this->staff)
        ->put(route('admin.orders.update', $order), ['status' => 'fulfilled', 'notes' => 'Picked up Saturday'])
        ->assertRedirect(route('admin.orders.show', $order));

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Fulfilled)
        ->and($order->fulfilled_at)->not->toBeNull()
        ->and($order->notes)->toBe('Picked up Saturday');
});

test('invalid status transitions are rejected', function () {
    $order = Order::factory()->create(); // pending

    $this->actingAs($this->staff)
        ->from(route('admin.orders.show', $order))
        ->put(route('admin.orders.update', $order), ['status' => 'fulfilled'])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});
