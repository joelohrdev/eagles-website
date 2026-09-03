<?php

use App\Mail\TryoutRegistrationConfirmation;
use App\Mail\TryoutRegistrationReceived;
use App\Models\Tryout;
use App\Models\TryoutRegistration;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

function validRegistration(array $overrides = []): array
{
    return array_merge([
        'player_first_name' => 'Jordan',
        'player_last_name' => 'Smith',
        'player_birthdate' => now()->subYears(12)->toDateString(),
        'parent_name' => 'Pat Smith',
        'email' => 'pat@example.com',
        'phone' => '630-555-0100',
        'current_team' => 'Local Sluggers',
        'primary_position' => 'SS',
        'notes' => 'Excited!',
        'website' => '',
    ], $overrides);
}

test('the registration page renders the form when open', function () {
    $tryout = Tryout::factory()->create();

    $this->get(route('tryouts.register', $tryout))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('site/Tryouts/Register')
            ->where('tryout.registration_state', 'open')
            ->has('positions')
            ->where('seo.robots', 'noindex,follow')
        );
});

test('the registration page renders a closed state when registration is closed', function () {
    $tryout = Tryout::factory()->registrationClosed()->create();

    $this->get(route('tryouts.register', $tryout))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('tryout.registration_state', 'closed'));
});

test('a family can register for an open tryout and emails are queued', function () {
    Mail::fake();
    $tryout = Tryout::factory()->create(['capacity' => 10]);

    $response = $this->post(route('tryouts.register.store', $tryout), validRegistration());

    $response->assertRedirect(route('tryouts.show', $tryout).'?registered=1');
    $response->assertSessionHasNoErrors();

    $registration = TryoutRegistration::first();

    expect($registration)->not->toBeNull()
        ->and($registration->tryout_id)->toBe($tryout->id)
        ->and($registration->player_first_name)->toBe('Jordan')
        ->and($registration->primary_position)->toBe('SS')
        ->and($registration->registered_at)->not->toBeNull();

    Mail::assertQueued(TryoutRegistrationConfirmation::class, fn ($mail) => $mail->hasTo('pat@example.com'));
    Mail::assertQueued(TryoutRegistrationReceived::class, fn ($mail) => $mail->hasTo('eaglesbaseballtravel@gmail.com'));
});

test('registration validates required fields', function () {
    $tryout = Tryout::factory()->create();

    $this->from(route('tryouts.register', $tryout))
        ->post(route('tryouts.register.store', $tryout), [
            'player_birthdate' => now()->addDay()->toDateString(),
            'primary_position' => 'DH',
            'email' => 'nope',
        ])
        ->assertRedirect(route('tryouts.register', $tryout))
        ->assertSessionHasErrors(['player_first_name', 'player_last_name', 'player_birthdate', 'parent_name', 'email', 'phone', 'primary_position']);

    expect(TryoutRegistration::count())->toBe(0);
});

test('registration is rejected when the window is closed', function () {
    Mail::fake();
    $tryout = Tryout::factory()->registrationClosed()->create();

    $this->from(route('tryouts.register', $tryout))
        ->post(route('tryouts.register.store', $tryout), validRegistration())
        ->assertRedirect(route('tryouts.register', $tryout))
        ->assertSessionHasErrors('registration');

    expect(TryoutRegistration::count())->toBe(0);
    Mail::assertNothingQueued();
});

test('registration is rejected before the window opens', function () {
    $tryout = Tryout::factory()->registrationUpcoming()->create();

    $this->post(route('tryouts.register.store', $tryout), validRegistration())
        ->assertSessionHasErrors('registration');

    expect(TryoutRegistration::count())->toBe(0);
});

test('registration is rejected when the tryout is full', function () {
    $tryout = Tryout::factory()->create(['capacity' => 1]);
    TryoutRegistration::factory()->for($tryout)->create();

    $this->post(route('tryouts.register.store', $tryout), validRegistration())
        ->assertSessionHasErrors('registration');

    expect($tryout->registrations()->count())->toBe(1)
        ->and($tryout->registrationState())->toBe('full');
});

test('registration for an unpublished tryout returns 404', function () {
    $tryout = Tryout::factory()->unpublished()->create();

    $this->post(route('tryouts.register.store', $tryout), validRegistration())->assertNotFound();
});

test('the honeypot silently rejects bot submissions', function () {
    Mail::fake();
    $tryout = Tryout::factory()->create();

    $this->from(route('tryouts.register', $tryout))
        ->post(route('tryouts.register.store', $tryout), validRegistration(['website' => 'http://spam.example']))
        ->assertRedirect(route('tryouts.register', $tryout));

    expect(TryoutRegistration::count())->toBe(0);
    Mail::assertNothingQueued();
});
