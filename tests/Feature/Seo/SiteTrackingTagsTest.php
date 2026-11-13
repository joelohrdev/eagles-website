<?php

use App\Models\User;
use App\Services\SiteSettings;

test('guests get the google analytics tag once a measurement id is set', function () {
    app(SiteSettings::class)->set('seo_google_analytics_id', 'G-ABC123XYZ');

    $this->get('/')
        ->assertOk()
        ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-ABC123XYZ', false)
        ->assertSee("gtag('config', 'G-ABC123XYZ')", false);
});

test('no google analytics tag is rendered without a measurement id', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false);
});

test('signed-in staff are not tracked', function () {
    app(SiteSettings::class)->set('seo_google_analytics_id', 'G-ABC123XYZ');

    $this->actingAs(User::factory()->staff()->create())
        ->get('/')
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false);
});

test('admin can save a google analytics measurement id and bad ids are rejected', function () {
    $admin = User::factory()->admin()->create();
    $seo = ['seo_site_name' => 'Eagles', 'seo_title_template' => '%s | Eagles'];

    $this->actingAs($admin)
        ->put(route('admin.settings.update', 'seo'), [...$seo, 'seo_google_analytics_id' => 'UA-12345-1'])
        ->assertSessionHasErrors(['seo_google_analytics_id']);

    $this->actingAs($admin)
        ->put(route('admin.settings.update', 'seo'), [...$seo, 'seo_google_analytics_id' => 'G-ABC123XYZ'])
        ->assertSessionHasNoErrors();

    expect(app(SiteSettings::class)->get('seo_google_analytics_id'))->toBe('G-ABC123XYZ');
});

test('search console and bing verification tags are rendered once set', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('google-site-verification', false)
        ->assertDontSee('msvalidate.01', false);

    app(SiteSettings::class)->setMany([
        'seo_google_site_verification' => 'google-code-123',
        'seo_bing_site_verification' => 'BING456',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('<meta name="google-site-verification" content="google-code-123">', false)
        ->assertSee('<meta name="msvalidate.01" content="BING456">', false);
});

test('pasting a whole verification meta tag keeps only the code', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.update', 'seo'), [
            'seo_site_name' => 'Eagles',
            'seo_title_template' => '%s | Eagles',
            'seo_google_site_verification' => '<meta name="google-site-verification" content="google-code-123" />',
            'seo_bing_site_verification' => 'BING456',
        ])
        ->assertSessionHasNoErrors();

    $settings = app(SiteSettings::class);

    expect($settings->get('seo_google_site_verification'))->toBe('google-code-123')
        ->and($settings->get('seo_bing_site_verification'))->toBe('BING456');
});
