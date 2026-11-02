<?php

use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SiteSettings;
use App\Services\StripeSettings;

beforeEach(function () {
    config(['services.stripe.secret' => null, 'services.stripe.webhook_secret' => null]);
});

test('staff cannot open payment settings', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.payments.edit'))
        ->assertForbidden();
});

test('admin sees the webhook url and the events to subscribe to', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.payments.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/payments/Edit')
            ->where('webhookUrl', route('stripe.webhook'))
            ->where('webhookEvents', ['checkout.session.completed', 'checkout.session.expired', 'charge.refunded'])
            ->where('stripe.secretKey.source', null));
});

test('saved keys are encrypted at rest and shown masked', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.payments.update'), [
            'secret_key' => ' rk_live_abc123XYZ9 ',
            'webhook_secret' => 'whsec_secret5678',
        ])
        ->assertRedirect(route('admin.payments.edit'));

    $stored = SiteSetting::query()->where('key', StripeSettings::SECRET_KEY)->value('value');
    expect($stored)->not->toContain('rk_live_');

    $stripe = app(StripeSettings::class);
    expect($stripe->secretKey())->toBe('rk_live_abc123XYZ9')
        ->and($stripe->webhookSecret())->toBe('whsec_secret5678')
        ->and($stripe->isTestMode())->toBeFalse()
        ->and($stripe->summary()['secretKey'])->toBe(['masked' => 'rk_live_…XYZ9', 'source' => 'dashboard'])
        ->and($stripe->summary()['webhookSecret'])->toBe(['masked' => 'whsec_…5678', 'source' => 'dashboard']);
});

test('a blank field keeps the value already saved', function () {
    app(StripeSettings::class)->save('rk_test_first1111', 'whsec_first2222');

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.payments.update'), ['secret_key' => '', 'webhook_secret' => 'whsec_second3333'])
        ->assertSessionHasNoErrors();

    expect(app(StripeSettings::class)->secretKey())->toBe('rk_test_first1111')
        ->and(app(StripeSettings::class)->webhookSecret())->toBe('whsec_second3333');
});

test('a publishable key or malformed secret is rejected', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.payments.update'), ['secret_key' => 'pk_live_abc', 'webhook_secret' => 'sk_live_abc'])
        ->assertSessionHasErrors(['secret_key', 'webhook_secret']);

    expect(SiteSetting::query()->whereIn('key', StripeSettings::KEYS)->exists())->toBeFalse();
});

test('saved keys win over the env values, which remain the fallback', function () {
    config(['services.stripe.secret' => 'sk_test_fromenv0000']);

    expect(app(StripeSettings::class)->summary()['secretKey'])->toBe(['masked' => 'sk_test_…0000', 'source' => 'env']);

    app(StripeSettings::class)->save('rk_live_fromdash1111', null);

    expect(app(StripeSettings::class)->secretKey())->toBe('rk_live_fromdash1111');
});

test('stripe keys never appear in the general site settings', function () {
    app(StripeSettings::class)->save('rk_live_hidden1111', 'whsec_hidden2222');
    app(SiteSettings::class)->flush();

    expect(app(SiteSettings::class)->all())->not->toHaveKeys(StripeSettings::KEYS);
});

test('order payment links point at stripe test mode when the saved key is a test key', function () {
    app(StripeSettings::class)->save('rk_test_abc1111', null);
    $order = Order::factory()->create(['stripe_payment_intent_id' => 'pi_123']);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.orders.show', $order))
        ->assertInertia(fn ($page) => $page->where('stripeUrl', 'https://dashboard.stripe.com/test/payments/pi_123'));
});
