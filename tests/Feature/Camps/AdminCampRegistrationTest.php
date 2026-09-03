<?php

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

    expect($csv)->toContain('Player,Birthdate,Parent/Guardian')
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
