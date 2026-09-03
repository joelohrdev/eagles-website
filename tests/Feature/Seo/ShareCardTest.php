<?php

use App\Services\ShareCard;
use App\Services\SiteSettings;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('the default share card renders the logo above the org name at card size', function () {
    $response = $this->get(route('share-card'))->assertOk();

    $response->assertHeader('content-type', 'image/png');

    $path = app(ShareCard::class)->path();
    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));

    expect($width)->toBe(ShareCard::WIDTH)
        ->and($height)->toBe(ShareCard::HEIGHT);
});

test('the rendered card is reused instead of drawn on every request', function () {
    $card = app(ShareCard::class);
    $path = $card->path();

    $before = Storage::disk('public')->get($path);

    $this->get(route('share-card'))->assertOk();

    expect($card->path())->toBe($path)
        ->and(Storage::disk('public')->get($path))->toBe($before)
        ->and(Storage::disk('public')->files(ShareCard::DIRECTORY))->toHaveCount(1);
});

test('changing the org name re-renders the card and drops the old one', function () {
    $card = app(ShareCard::class);
    $original = $card->path();

    app(SiteSettings::class)->set('org_name', 'Eagles Baseball Club');

    $updated = app(ShareCard::class)->path();

    expect($updated)->not->toBe($original);

    Storage::disk('public')->assertExists($updated);
    Storage::disk('public')->assertMissing($original);
    expect(Storage::disk('public')->files(ShareCard::DIRECTORY))->toHaveCount(1);
});

test('an uploaded site default wins over the generated card', function () {
    Storage::disk('public')->put('settings/default.jpg', 'x');
    app(SiteSettings::class)->set('seo_default_share_image', 'settings/default.jpg');

    $this->get(route('share-card'))
        ->assertRedirect(url('/storage/settings/default.jpg'));
});

test('pages share the generated card when nothing else is set', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('seo.share_image_url', route('share-card'))->etc());
});
