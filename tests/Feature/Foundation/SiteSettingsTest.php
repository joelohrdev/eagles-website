<?php

use App\Models\SiteSetting;
use App\Services\SiteSettings;
use Illuminate\Support\Facades\Cache;

test('defaults are returned when nothing is stored', function () {
    $settings = app(SiteSettings::class);

    expect($settings->get('phone'))->toBe('630-767-9208')
        ->and($settings->get('email'))->toBe('eaglesbaseballtravel@gmail.com')
        ->and($settings->get('missing_key', 'fallback'))->toBe('fallback')
        ->and($settings->all())->toHaveKey('home_offerings');
});

test('setMany persists values and flushes the cache', function () {
    $settings = app(SiteSettings::class);

    $settings->all();
    expect(Cache::has(SiteSettings::CACHE_KEY))->toBeTrue();

    $settings->setMany(['phone' => '555-0100', 'home_offerings' => [['title' => 'A']]]);

    expect(Cache::has(SiteSettings::CACHE_KEY))->toBeFalse()
        ->and(SiteSetting::query()->count())->toBe(2)
        ->and($settings->get('phone'))->toBe('555-0100')
        ->and($settings->get('home_offerings'))->toBe([['title' => 'A']]);
});

test('group returns only that group\'s keys', function () {
    $group = app(SiteSettings::class)->group('contact');

    expect(array_keys($group))->toBe(['contact_intro']);
});

test('null stored values fall back to defaults', function () {
    SiteSetting::query()->create(['key' => 'phone', 'value' => null]);

    expect(app(SiteSettings::class)->get('phone'))->toBe('630-767-9208');
});
