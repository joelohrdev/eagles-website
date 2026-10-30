<?php

use App\Mail\ContactSubmissionReceived;
use App\Models\ContactSubmission;
use App\Services\Turnstile;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

test('contact page renders', function () {
    $this->get(route('contact'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('site/Contact/Index')
            ->where('org.phone', '630-767-9208')
            ->where('sent', false)
            ->has('seo')
        );
});

test('a visitor can send a message and the org is emailed', function () {
    Mail::fake();

    $response = $this->post(route('contact.store'), [
        'name' => 'Pat Parent',
        'email' => 'pat@example.com',
        'phone' => '555-123-4567',
        'subject' => '12U tryouts',
        'message' => 'When are tryouts?',
    ]);

    $response->assertRedirect(route('contact', ['sent' => 1]));

    $submission = ContactSubmission::query()->first();
    expect($submission)->not->toBeNull()
        ->and($submission->email)->toBe('pat@example.com')
        ->and($submission->read_at)->toBeNull();

    Mail::assertQueued(ContactSubmissionReceived::class, fn (ContactSubmissionReceived $mail) => $mail->hasTo('eaglesbaseballtravel@gmail.com')
        && $mail->submission->is($submission)
        && $mail->hasReplyTo('pat@example.com'));

    $this->get(route('contact', ['sent' => 1]))->assertInertia(fn (Assert $page) => $page->where('sent', true));
});

test('contact form validates required fields', function () {
    $this->from(route('contact'))
        ->post(route('contact.store'), ['name' => '', 'email' => 'nope', 'message' => ''])
        ->assertRedirect(route('contact'))
        ->assertSessionHasErrors(['name', 'email', 'message']);

    expect(ContactSubmission::count())->toBe(0);
});

test('contact phone numbers are stored as 999-999-9999', function (string $typed) {
    Mail::fake();

    $this->post(route('contact.store'), [
        'name' => 'Pat Parent',
        'email' => 'pat@example.com',
        'phone' => $typed,
        'message' => 'When are tryouts?',
    ])->assertSessionHasNoErrors();

    expect(ContactSubmission::query()->value('phone'))->toBe('555-123-4567');
})->with(['555-123-4567', '(555) 123-4567', '555.123.4567', '5551234567', '+1 555 123 4567']);

test('contact phone numbers must have ten digits', function (string $typed) {
    $this->from(route('contact'))
        ->post(route('contact.store'), [
            'name' => 'Pat Parent',
            'email' => 'pat@example.com',
            'phone' => $typed,
            'message' => 'When are tryouts?',
        ])
        ->assertSessionHasErrors('phone');

    expect(ContactSubmission::count())->toBe(0);
})->with(['555-1234', '555-123-45678', 'call me']);

test('honeypot submissions are rejected', function () {
    Mail::fake();

    $this->post(route('contact.store'), [
        'name' => 'Bot',
        'email' => 'bot@example.com',
        'message' => 'spam',
        'website' => 'http://spam.example',
    ])->assertSessionHasErrors('website');

    expect(ContactSubmission::count())->toBe(0);
    Mail::assertNothingQueued();
});

function enableTurnstile(): void
{
    config([
        'services.turnstile.site_key' => 'test-site-key',
        'services.turnstile.secret_key' => 'test-secret-key',
    ]);
}

/**
 * @return array<string, string>
 */
function contactMessage(array $overrides = []): array
{
    return [
        'name' => 'Pat Parent',
        'email' => 'pat@example.com',
        'message' => 'When are tryouts?',
        ...$overrides,
    ];
}

test('the turnstile site key reaches the contact page only once both keys are set', function () {
    $this->get(route('contact'))
        ->assertInertia(fn (Assert $page) => $page->where('turnstileSiteKey', null));

    config(['services.turnstile.site_key' => 'test-site-key']);

    $this->get(route('contact'))
        ->assertInertia(fn (Assert $page) => $page->where('turnstileSiteKey', null));

    enableTurnstile();

    $this->get(route('contact'))
        ->assertInertia(fn (Assert $page) => $page->where('turnstileSiteKey', 'test-site-key'));
});

test('a message with a valid turnstile token is sent', function () {
    Mail::fake();
    enableTurnstile();
    Http::fake([Turnstile::VERIFY_URL => Http::response(['success' => true])]);

    $this->post(route('contact.store'), contactMessage([Turnstile::RESPONSE_FIELD => 'good-token']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('contact', ['sent' => 1]));

    expect(ContactSubmission::count())->toBe(1);
    Http::assertSent(fn (Request $request) => $request['secret'] === 'test-secret-key'
        && $request['response'] === 'good-token'
        && $request['remoteip'] === '127.0.0.1');
});

test('a message is rejected when turnstile fails', function (array $fakeResponse) {
    Mail::fake();
    enableTurnstile();
    Http::fake([Turnstile::VERIFY_URL => Http::response(...$fakeResponse)]);

    $this->post(route('contact.store'), contactMessage([Turnstile::RESPONSE_FIELD => 'bad-token']))
        ->assertSessionHasErrors(Turnstile::RESPONSE_FIELD);

    expect(ContactSubmission::count())->toBe(0);
    Mail::assertNothingQueued();
})->with([
    'token rejected' => [[['success' => false, 'error-codes' => ['invalid-input-response']]]],
    'cloudflare error' => [[[], 500]],
]);

test('a message without a turnstile token is rejected without calling cloudflare', function () {
    enableTurnstile();
    Http::fake();

    $this->post(route('contact.store'), contactMessage())
        ->assertSessionHasErrors(Turnstile::RESPONSE_FIELD);

    expect(ContactSubmission::count())->toBe(0);
    Http::assertNothingSent();
});

test('a message is rejected when cloudflare cannot be reached', function () {
    enableTurnstile();
    Http::fake(fn () => throw new ConnectionException('timed out'));

    $this->post(route('contact.store'), contactMessage([Turnstile::RESPONSE_FIELD => 'good-token']))
        ->assertSessionHasErrors(Turnstile::RESPONSE_FIELD);

    expect(ContactSubmission::count())->toBe(0);
});

test('turnstile is skipped while its keys are not set', function () {
    Mail::fake();
    Http::fake();

    $this->post(route('contact.store'), contactMessage())
        ->assertSessionHasNoErrors();

    expect(ContactSubmission::count())->toBe(1);
    Http::assertNothingSent();
});
