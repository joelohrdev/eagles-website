<?php

use App\Models\SeoMeta;
use App\Models\Team;
use App\Services\SeoResolver;
use App\Services\SiteSettings;

test('route seo falls back to controller defaults and site settings', function () {
    app(SiteSettings::class)->setMany([
        'seo_site_name' => 'Eagles',
        'seo_title_template' => '%s | Eagles',
        'seo_default_description' => 'Default description',
    ]);

    $seo = app(SeoResolver::class)->forRoute('teams.index', [
        'title' => 'Teams',
        'json_ld' => [['@type' => 'Thing']],
    ])->toArray();

    expect($seo['title'])->toBe('Teams | Eagles')
        ->and($seo['description'])->toBe('Default description')
        ->and($seo['share_title'])->toBe('Teams')
        ->and($seo['share_description'])->toBe('Default description')
        ->and($seo['robots'])->toBe('index,follow')
        ->and($seo['twitter_card'])->toBe('summary_large_image')
        ->and($seo['og_type'])->toBe('website')
        ->and($seo['site_name'])->toBe('Eagles')
        ->and($seo['json_ld'][0]['@type'])->toBe('SportsOrganization')
        ->and($seo['json_ld'][1]['@type'])->toBe('Thing');
});

test('title falls back to the site name when nothing is provided', function () {
    $seo = app(SeoResolver::class)->forRoute('home')->toArray();

    expect($seo['title'])->toBe(app(SiteSettings::class)->get('seo_site_name'));
});

test('stored seo meta overrides defaults for a route', function () {
    SeoMeta::factory()->forRoute('teams.index')->create([
        'title' => 'Custom Teams Title',
        'description' => 'Custom description',
        'share_title' => 'Share me',
        'share_description' => 'Share description',
        'robots' => 'noindex,follow',
        'canonical_url' => 'https://example.com/teams',
        'twitter_card' => 'summary',
        'json_ld' => ['@type' => 'FAQPage'],
    ]);

    $seo = app(SeoResolver::class)->forRoute('teams.index', ['title' => 'Teams'])->toArray();

    expect($seo['title'])->toContain('Custom Teams Title')
        ->and($seo['description'])->toBe('Custom description')
        ->and($seo['share_title'])->toBe('Share me')
        ->and($seo['share_description'])->toBe('Share description')
        ->and($seo['robots'])->toBe('noindex,follow')
        ->and($seo['canonical_url'])->toBe('https://example.com/teams')
        ->and($seo['twitter_card'])->toBe('summary')
        ->and(collect($seo['json_ld'])->pluck('@type')->all())->toContain('FAQPage');
});

test('share image falls back from meta to defaults to the site default', function () {
    $resolver = app(SeoResolver::class);
    $team = Team::factory()->create();

    // Nothing set anywhere: the generated default card, so a shared link is never bare.
    expect($resolver->forModel($team, ['title' => $team->name])->toArray()['share_image_url'])
        ->toBe(route('share-card'));

    // Site default.
    app(SiteSettings::class)->set('seo_default_share_image', 'settings/default.jpg');
    expect($resolver->forModel($team, ['title' => $team->name])->toArray()['share_image_url'])->toEndWith('/storage/settings/default.jpg');

    // Controller default (e.g. the record's own photo) beats site default.
    expect($resolver->forModel($team, ['title' => $team->name, 'share_image_path' => 'teams/photo.webp'])->toArray()['share_image_url'])
        ->toEndWith('/storage/teams/photo.webp');

    // Stored meta share image beats everything.
    $team->seoMeta()->create(['share_image_path' => 'share/custom.jpg', 'share_image_alt' => 'Custom alt']);
    $seo = $resolver->forModel($team->fresh(), ['title' => $team->name, 'share_image_path' => 'teams/photo.webp'])->toArray();

    expect($seo['share_image_url'])->toEndWith('/storage/share/custom.jpg')
        ->and($seo['share_image_alt'])->toBe('Custom alt')
        ->and($seo['share_image_width'])->toBe(1200)
        ->and($seo['share_image_height'])->toBe(630);
});

test('organization json-ld includes address and geo when configured', function () {
    app(SiteSettings::class)->setMany([
        'address_line1' => '123 Main St',
        'address_city' => 'Naperville',
        'geo_latitude' => '41.75',
        'geo_longitude' => '-88.15',
        'social_facebook' => 'https://facebook.com/eagles',
    ]);

    $org = app(SeoResolver::class)->forRoute('home')->toArray()['json_ld'][0];

    expect($org['address']['addressLocality'])->toBe('Naperville')
        ->and($org['geo']['latitude'])->toBe(41.75)
        ->and($org['sameAs'])->toBe(['https://facebook.com/eagles']);
});
