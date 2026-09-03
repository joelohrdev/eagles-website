<?php

use App\Mail\ContactSubmissionReceived;
use App\Models\ContactSubmission;
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
        'phone' => '555-1234',
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
