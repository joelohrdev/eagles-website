<?php

use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Models\Camp;
use App\Models\CampRegistration;
use App\Models\Order;
use Illuminate\Console\Scheduling\Schedule;

test('the expire command cancels expired pending registrations and their orders', function () {
    $camp = Camp::factory()->paid()->create(['capacity' => 5]);

    $expiredOrder = Order::factory()->camp()->create();
    $expired = CampRegistration::factory()->for($camp)->expiredPending()->create(['order_id' => $expiredOrder->id]);

    $activeOrder = Order::factory()->camp()->create();
    $active = CampRegistration::factory()->for($camp)->pending()->create(['order_id' => $activeOrder->id]);

    $paid = CampRegistration::factory()->for($camp)->create();

    $this->artisan('camps:expire-pending')
        ->expectsOutputToContain('Expired 1 pending camp registration(s).')
        ->assertSuccessful();

    expect($expired->refresh()->status)->toBe(RegistrationStatus::Cancelled)
        ->and($expiredOrder->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($active->refresh()->status)->toBe(RegistrationStatus::PendingPayment)
        ->and($activeOrder->refresh()->status)->toBe(OrderStatus::Pending)
        ->and($paid->refresh()->status)->toBe(RegistrationStatus::Paid)
        ->and($camp->fresh()->spotsRemaining())->toBe(3);
});

test('the expire command is scheduled', function () {
    $events = collect(app(Schedule::class)->events())
        ->map(fn ($event) => $event->command)
        ->filter(fn ($command) => str_contains((string) $command, 'camps:expire-pending'));

    expect($events)->not->toBeEmpty();
});
