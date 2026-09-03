<?php

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests are redirected from settings', function () {
    $this->get(route('admin.settings.edit', 'organization'))->assertRedirect(route('login'));
});

test('staff cannot access settings', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.settings.edit', 'organization'))
        ->assertForbidden();
});

test('admin can view each settings group', function (string $group) {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.settings.edit', $group))
        ->assertOk();
})->with(['organization', 'home', 'facility', 'contact', 'pages', 'seo']);

test('unknown settings group is not found', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/settings/nope')
        ->assertNotFound();
});

test('admin can update organization settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update', 'organization'), [
            'org_name' => 'Eagles Baseball Travel',
            'phone' => '630-767-9208',
            'email' => 'eaglesbaseballtravel@gmail.com',
            'address_city' => 'Naperville',
            'address_state' => 'IL',
            'social_facebook' => 'https://facebook.com/eagles',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.settings.edit', 'organization'));

    expect(app(SiteSettings::class)->get('address_city'))->toBe('Naperville')
        ->and(SiteSetting::query()->where('key', 'social_facebook')->value('value'))->toBe('https://facebook.com/eagles');
});

test('organization settings are validated', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.update', 'organization'), [
            'org_name' => '',
            'email' => 'not-an-email',
            'social_instagram' => 'nope',
            'geo_latitude' => 200,
        ])
        ->assertSessionHasErrors(['org_name', 'email', 'social_instagram', 'geo_latitude']);
});

test('admin can update home settings with offerings and images', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.update', 'home'), [
            'home_hero_headline' => 'Play for the Eagles',
            'home_offerings' => [
                ['title' => 'Teams', 'description' => 'Competitive teams'],
                ['title' => 'Camps', 'description' => ''],
            ],
            'home_hero_image' => UploadedFile::fake()->image('hero.jpg', 1600, 900),
        ])
        ->assertSessionHasNoErrors();

    $settings = app(SiteSettings::class);

    expect($settings->get('home_hero_headline'))->toBe('Play for the Eagles')
        ->and($settings->get('home_offerings'))->toHaveCount(2)
        ->and($settings->get('home_offerings')[1]['description'])->toBeNull()
        ->and($settings->get('home_hero_image'))->toStartWith('settings/');

    Storage::disk('public')->assertExists($settings->get('home_hero_image'));
});

test('home settings validate offering rows', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.update', 'home'), [
            'home_hero_headline' => 'x',
            'home_offerings' => [['title' => '']],
        ])
        ->assertSessionHasErrors(['home_offerings.0.title']);
});

test('admin can remove a settings image', function () {
    Storage::fake('public');
    Storage::disk('public')->put('settings/old.webp', 'x');
    Storage::disk('public')->put('settings/thumbs/old.webp', 'x');
    app(SiteSettings::class)->set('home_hero_image', 'settings/old.webp');

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.update', 'home'), [
            'home_hero_headline' => 'Headline',
            'remove_home_hero_image' => 1,
        ])
        ->assertSessionHasNoErrors();

    expect(app(SiteSettings::class)->get('home_hero_image'))->toBeNull();
    Storage::disk('public')->assertMissing('settings/old.webp');
    Storage::disk('public')->assertMissing('settings/thumbs/old.webp');
});

test('seo settings require a title template placeholder and accept a share image and faq', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update', 'seo'), [
            'seo_site_name' => 'Eagles',
            'seo_title_template' => 'Eagles',
        ])
        ->assertSessionHasErrors(['seo_title_template']);

    $this->actingAs($admin)
        ->put(route('admin.settings.update', 'seo'), [
            'seo_site_name' => 'Eagles',
            'seo_title_template' => '%s | Eagles',
            'seo_twitter_handle' => '@eagles',
            'seo_faq' => [['question' => 'What ages?', 'answer' => '9U to 17U.']],
            'seo_default_share_image' => UploadedFile::fake()->image('share.png', 1200, 630),
        ])
        ->assertSessionHasNoErrors();

    $settings = app(SiteSettings::class);

    expect($settings->get('seo_faq'))->toBe([['question' => 'What ages?', 'answer' => '9U to 17U.']])
        ->and($settings->get('seo_default_share_image'))->toStartWith('settings/');

    Storage::disk('public')->assertExists($settings->get('seo_default_share_image'));
});

test('facility and contact settings can be updated', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update', 'facility'), [
            'facility_heading' => 'The Nest',
            'facility_youtube_url' => 'https://www.youtube.com/watch?v=abc123',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->put(route('admin.settings.update', 'contact'), ['contact_intro' => 'Say hi'])
        ->assertSessionHasNoErrors();

    $settings = app(SiteSettings::class);

    expect($settings->get('facility_heading'))->toBe('The Nest')
        ->and($settings->get('contact_intro'))->toBe('Say hi');
});
