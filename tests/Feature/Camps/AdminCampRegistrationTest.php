<?php

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Models\Camp;
use App\Models\CampRegistration;
use App\Models\Order;
use App\Models\User;

beforeEach(function () {
    $this->staff = User::factory()->staff()->create();
    $this->camp = Camp::factory()->create();
});

test('staff can list registrations for a camp', function () {
    CampRegistration::factory()->count(3)->for($this->camp)->create();
    CampRegistration::factory()->create(); // other camp

    $this->actingAs($this->staff)
        ->get(route('admin.camps.registrations.index', $this->camp))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/camps/Registrations')
            ->where('camp.id', $this->camp->id)
            ->has('registrations.data', 3)
            ->has('registrations.data.0', fn ($row) => $row
                ->hasAll(['id', 'player_name', 'parent_name', 'email', 'phone', 'status', 'status_label', 'order_number', 'registered_at'])
                ->etc()
            )
        );
});

test('registrations can be searched and filtered by status', function () {
    CampRegistration::factory()->for($this->camp)->create(['player_first_name' => 'Zed', 'player_last_name' => 'Unique']);
    CampRegistration::factory()->for($this->camp)->pending()->create(['player_first_name' => 'Pending', 'player_last_name' => 'Person']);

    $this->actingAs($this->staff)
        ->get(route('admin.camps.registrations.index', ['camp' => $this->camp, 'q' => 'Zed']))
        ->assertInertia(fn ($page) => $page->has('registrations.data', 1)->where('registrations.data.0.player_name', 'Zed Unique'));

    $this->actingAs($this->staff)
        ->get(route('admin.camps.registrations.index', ['camp' => $this->camp, 'status' => RegistrationStatus::PendingPayment->value]))
        ->assertInertia(fn ($page) => $page->has('registrations.data', 1)->where('registrations.data.0.status', 'pending_payment'));
});

test('registrations can be exported as csv', function () {
    $order = Order::factory()->camp()->paid()->create();
    CampRegistration::factory()->for($this->camp)->create([
        'player_first_name' => 'Casey',
        'player_last_name' => 'Jones',
        'email' => 'casey@example.com',
        'order_id' => $order->id,
    ]);

    $response = $this->actingAs($this->staff)->get(route('admin.camps.registrations.export', $this->camp));

    $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Player,Birthdate,"Age group",Days,Parent/Guardian')
        ->toContain($this->camp->name.': '.$this->camp->starts_at->format('D, M j'))
        ->toContain('Casey Jones')
        ->toContain('casey@example.com')
        ->toContain($order->number);
});

test('staff can delete a registration', function () {
    $registration = CampRegistration::factory()->for($this->camp)->create();

    $this->actingAs($this->staff)
        ->from(route('admin.camps.registrations.index', $this->camp))
        ->delete(route('admin.camps.registrations.destroy', [$this->camp, $registration]))
        ->assertRedirect(route('admin.camps.registrations.index', $this->camp));

    $this->assertModelMissing($registration);
});

test('a registration cannot be deleted through another camp', function () {
    $registration = CampRegistration::factory()->create();

    $this->actingAs($this->staff)
        ->delete(route('admin.camps.registrations.destroy', [$this->camp, $registration]))
        ->assertNotFound();

    $this->assertModelExists($registration);
});

test('changing a camp order status in admin updates its registration', function (Closure $makeOrder, Closure $makeRegistration, string $orderStatus, RegistrationStatus $registrationStatus) {
    $order = $makeOrder();
    $registration = $makeRegistration(CampRegistration::factory()->for($this->camp))->create(['order_id' => $order->id]);

    $this->actingAs($this->staff)
        ->put(route('admin.orders.update', $order), ['status' => $orderStatus])
        ->assertSessionHasNoErrors();

    expect($registration->fresh()->status)->toBe($registrationStatus);
})->with([
    'pending order cancelled' => [fn () => Order::factory()->camp()->create(), fn ($factory) => $factory->pending(), 'cancelled', RegistrationStatus::Cancelled],
    'paid order refunded' => [fn () => Order::factory()->camp()->paid()->create(), fn ($factory) => $factory, 'refunded', RegistrationStatus::Refunded],
]);

test('deleting a registration held for payment closes its Stripe page and cancels the order', function () {
    $order = Order::factory()->camp()->create(['stripe_checkout_session_id' => 'cs_fake_held']);
    $registration = CampRegistration::factory()->for($this->camp)->pending()->create(['order_id' => $order->id]);

    $this->actingAs($this->staff)
        ->delete(route('admin.camps.registrations.destroy', [$this->camp, $registration]));

    $this->assertModelMissing($registration);
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and(app(PaymentGateway::class)->closedSessions)->toBe(['cs_fake_held']);
});

test('a registration is not deleted while its order is paid, or may just have been paid', function (Closure $makeOrder) {
    $order = $makeOrder();
    $registration = CampRegistration::factory()->for($this->camp)->pending()->create(['order_id' => $order->id]);
    app(PaymentGateway::class)->paidSessions[] = 'cs_fake_paid';

    $this->actingAs($this->staff)
        ->delete(route('admin.camps.registrations.destroy', [$this->camp, $registration]));

    $this->assertModelExists($registration);
    expect($order->fresh()->status)->toBe($order->status);
})->with([
    'paid' => [fn () => Order::factory()->camp()->paid()->create()],
    'paid at Stripe, not yet confirmed' => [fn () => Order::factory()->camp()->create(['stripe_checkout_session_id' => 'cs_fake_paid'])],
]);

test('staff cannot cancel a pending order that was just paid at Stripe', function () {
    $order = Order::factory()->camp()->create(['stripe_checkout_session_id' => 'cs_fake_paid']);
    $registration = CampRegistration::factory()->for($this->camp)->pending()->create(['order_id' => $order->id]);
    app(PaymentGateway::class)->paidSessions[] = 'cs_fake_paid';

    $this->actingAs($this->staff)->put(route('admin.orders.update', $order), ['status' => 'cancelled']);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($registration->fresh()->status)->toBe(RegistrationStatus::PendingPayment);
});
