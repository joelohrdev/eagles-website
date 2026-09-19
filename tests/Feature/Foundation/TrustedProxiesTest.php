<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

function sendContactMessage(string $remoteAddress, ?string $forwardedFor = null): TestResponse
{
    return test()
        ->withServerVariables(['REMOTE_ADDR' => $remoteAddress])
        ->withHeaders($forwardedFor ? ['X-Forwarded-For' => $forwardedFor] : [])
        ->post(route('contact.store'), [
            'name' => 'Pat Parent',
            'email' => 'pat@example.com',
            'message' => 'When are tryouts?',
        ]);
}

beforeEach(fn () => Mail::fake());

test('visitors behind cloudflare are rate limited separately', function () {
    foreach (range(1, 5) as $attempt) {
        sendContactMessage('173.245.48.10', '203.0.113.1')->assertRedirect();
    }

    sendContactMessage('173.245.48.10', '203.0.113.1')->assertTooManyRequests();
    sendContactMessage('173.245.48.10', '203.0.113.2')->assertRedirect();
});

test('forwarded addresses from outside cloudflare are ignored', function () {
    foreach (range(1, 5) as $attempt) {
        sendContactMessage('198.51.100.7', "203.0.113.{$attempt}")->assertRedirect();
    }

    sendContactMessage('198.51.100.7', '203.0.113.99')->assertTooManyRequests();
});
