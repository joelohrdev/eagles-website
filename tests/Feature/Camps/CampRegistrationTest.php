<?php

use App\Actions\Orders\MarkOrderPaid;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\RegistrationStatus;
use App\Mail\CampRegistrationConfirmation;
use App\Mail\CampRegistrationReceived;
use App\Mail\OrderReceipt;
use App\Models\Camp;
use App\Models\CampRegistration;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

function validCampRegistration(array $overrides = []): array
{
    return array_merge([
        'player_first_name' => 'Jamie',
        'player_last_name' => 'Rivera',
        'player_birthdate' => now()->subYears(12)->toDateString(),
        'parent_name' => 'Alex Rivera',
        'email' => 'alex@example.com',
        'phone' => '630-555-0100',
        'emergency_contact_name' => 'Sam Rivera',
        'emergency_contact_phone' => '630-555-0101',
        'medical_notes' => 'Peanut allergy',
    ], $overrides);
}

test('registering for a free camp confirms immediately and sends emails', function () {
    Mail::fake();
    $camp = Camp::factory()->free()->create();

    $this->post(route('camps.register.store', $camp), validCampRegistration())
        ->assertRedirect(route('camps.show', ['camp' => $camp, 'registered' => 1]))
        ->assertSessionHasNoErrors();

    $registration = CampRegistration::query()->firstOrFail();

    expect($registration->camp_id)->toBe($camp->id)
        ->and($registration->status)->toBe(RegistrationStatus::Paid)
        ->and($registration->order_id)->toBeNull()
        ->and($registration->expires_at)->toBeNull()
        ->and($registration->medical_notes)->toBe('Peanut allergy')
        ->and($registration->registered_at)->not->toBeNull()
        ->and(Order::count())->toBe(0);

    Mail::assertQueued(CampRegistrationConfirmation::class, fn ($mail) => $mail->hasTo('alex@example.com'));
    Mail::assertQueued(CampRegistrationReceived::class, fn ($mail) => $mail->hasTo('eaglesbaseballtravel@gmail.com'));
});

test('registering for a paid camp creates a pending order and redirects to checkout', function () {
    Mail::fake();
    $camp = Camp::factory()->paid(9900)->create();

    $response = $this->post(route('camps.register.store', $camp), validCampRegistration());

    $order = Order::query()->firstOrFail();
    $registration = CampRegistration::query()->firstOrFail();

    // Inertia::location for a non-Inertia request is a plain redirect to the gateway URL.
    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/checkout/'.$order->number.'/success');

    expect($order->type)->toBe(OrderType::Camp)
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->total)->toBe(9900)
        ->and($order->email)->toBe('alex@example.com')
        ->and($order->name)->toBe('Alex Rivera')
        ->and($order->stripe_checkout_session_id)->toStartWith('cs_fake_')
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->description)->toBe("{$camp->name} — Jamie Rivera")
        ->and($order->items->first()->unit_price)->toBe(9900)
        ->and($registration->order_id)->toBe($order->id)
        ->and($registration->status)->toBe(RegistrationStatus::PendingPayment)
        ->and($registration->expires_at)->not->toBeNull();

    Mail::assertNothingQueued();
});

test('paid camp registration through inertia returns a 409 location response', function () {
    $camp = Camp::factory()->paid()->create();

    $this->withHeaders(['X-Inertia' => 'true'])
        ->post(route('camps.register.store', $camp), validCampRegistration())
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location');
});

test('webhook payment marks the camp registration paid and emails a receipt', function () {
    Mail::fake();
    $camp = Camp::factory()->paid()->create();

    $this->post(route('camps.register.store', $camp), validCampRegistration());
    $order = Order::query()->firstOrFail();

    $this->postJson(route('stripe.webhook'), [
        'type' => 'checkout.session.completed',
        'session_id' => $order->stripe_checkout_session_id,
        'payment_intent' => 'pi_test_123',
        'payment_status' => 'paid',
    ], ['Stripe-Signature' => 'fake'])->assertOk();

    $order->refresh();
    $registration = CampRegistration::query()->firstOrFail();

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->stripe_payment_intent_id)->toBe('pi_test_123')
        ->and($registration->status)->toBe(RegistrationStatus::Paid)
        ->and($registration->expires_at)->toBeNull();

    Mail::assertQueued(OrderReceipt::class, fn ($mail) => $mail->hasTo('alex@example.com'));
});

test('registration is rejected when the camp is closed, upcoming, or full', function (string $state) {
    $camp = match ($state) {
        'closed' => Camp::factory()->registrationClosed()->create(),
        'upcoming' => Camp::factory()->registrationUpcoming()->create(),
        'full' => tap(Camp::factory()->create(['capacity' => 1]), fn (Camp $c) => CampRegistration::factory()->for($c)->create()),
    };

    $this->from(route('camps.register', $camp))
        ->post(route('camps.register.store', $camp), validCampRegistration())
        ->assertRedirect(route('camps.register', $camp))
        ->assertSessionHasErrors('registration');

    expect(CampRegistration::query()->where('email', 'alex@example.com')->exists())->toBeFalse();
})->with(['closed', 'upcoming', 'full']);

test('capacity counts paid and unexpired pending registrations but not expired ones', function () {
    $camp = Camp::factory()->create(['capacity' => 2]);
    CampRegistration::factory()->for($camp)->create();          // paid
    CampRegistration::factory()->for($camp)->expiredPending()->create();

    expect($camp->spotsRemaining())->toBe(1)->and($camp->isRegistrationOpen())->toBeTrue();

    CampRegistration::factory()->for($camp)->pending()->create();

    expect($camp->fresh()->spotsRemaining())->toBe(0)
        ->and($camp->fresh()->isRegistrationOpen())->toBeFalse()
        ->and($camp->fresh()->registrationState())->toBe('full');
});

test('registration validates required fields', function () {
    $camp = Camp::factory()->create();

    $this->post(route('camps.register.store', $camp), [
        'player_birthdate' => now()->addDay()->toDateString(),
        'email' => 'nope',
    ])->assertSessionHasErrors([
        'player_first_name', 'player_last_name', 'player_birthdate', 'parent_name', 'email', 'phone',
        'emergency_contact_name', 'emergency_contact_phone',
    ]);

    expect(CampRegistration::count())->toBe(0);
});

test('honeypot submissions are silently discarded', function () {
    Mail::fake();
    $camp = Camp::factory()->free()->create();

    $this->post(route('camps.register.store', $camp), validCampRegistration(['website' => 'http://spam.example']))
        ->assertRedirect(route('camps.show', $camp));

    expect(CampRegistration::count())->toBe(0);
    Mail::assertNothingQueued();
});

test('mark order paid transitions a camp registration without a webhook', function () {
    Mail::fake();
    $camp = Camp::factory()->paid()->create();
    $this->post(route('camps.register.store', $camp), validCampRegistration());
    $order = Order::query()->firstOrFail();

    app(MarkOrderPaid::class)->handle($order);

    expect(CampRegistration::query()->firstOrFail()->status)->toBe(RegistrationStatus::Paid);
});
