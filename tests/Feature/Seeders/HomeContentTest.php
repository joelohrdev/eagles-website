<?php

use App\Services\SiteSettings;
use Database\Seeders\HomeContentSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

test('home:export snapshots the home settings and their images', function () {
    Storage::fake('public');
    Storage::disk('public')->put('settings/hero.webp', 'hero');
    Storage::disk('public')->put('settings/thumbs/hero.webp', 'hero-thumb');

    app(SiteSettings::class)->setMany([
        'home_hero_headline' => 'Local headline',
        'home_hero_image' => 'settings/hero.webp',
        'home_about_image' => 'settings/missing.webp',
        'org_name' => 'Not a home setting',
    ]);

    $path = storage_path('framework/testing/home-export');
    File::deleteDirectory($path);

    $this->artisan('home:export', ['--path' => $path])
        ->expectsOutputToContain('Skipping home_about_image')
        ->assertSuccessful();

    $snapshot = File::json("{$path}/settings.json");

    expect(array_keys($snapshot))->toBe(SiteSettings::GROUPS['home'])
        ->and($snapshot['home_hero_headline'])->toBe('Local headline')
        ->and($snapshot['home_hero_image'])->toBe('settings/hero.webp')
        ->and($snapshot['home_about_image'])->toBeNull()
        ->and($snapshot['home_values'])->toBe(SiteSettings::DEFAULTS['home_values'])
        ->and(File::get("{$path}/images/settings/hero.webp"))->toBe('hero')
        ->and(File::get("{$path}/images/settings/thumbs/hero.webp"))->toBe('hero-thumb');

    File::deleteDirectory($path);
});

test('the home content seeder loads the committed snapshot and its images', function () {
    Storage::fake('public');

    $snapshot = File::json(HomeContentSeeder::contentPath().'/settings.json');

    $this->seed(HomeContentSeeder::class);
    $this->seed(HomeContentSeeder::class);

    expect(app(SiteSettings::class)->group('home'))->toBe($snapshot);

    foreach (['home_hero_image', 'home_about_image'] as $key) {
        if (filled($snapshot[$key])) {
            Storage::disk('public')->assertExists($snapshot[$key]);
        }
    }
});
