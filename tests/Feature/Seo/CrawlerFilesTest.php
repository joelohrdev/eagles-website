<?php

use App\Models\Camp;
use App\Models\Product;
use App\Models\Tryout;
use Illuminate\Support\Facades\Cache;

beforeEach(fn () => Cache::flush());

test('sitemap lists static pages and published records only', function () {
    $tryout = Tryout::factory()->create();
    $hiddenTryout = Tryout::factory()->unpublished()->create();
    $camp = Camp::factory()->create();
    $hiddenCamp = Camp::factory()->unpublished()->create();
    $product = Product::factory()->create();
    $hiddenProduct = Product::factory()->inactive()->create();

    $response = $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $xml = $response->getContent();

    expect($xml)->toContain('<urlset')
        ->toContain('<loc>'.route('home').'</loc>')
        ->toContain('<loc>'.route('tryouts.index').'</loc>')
        ->toContain('<loc>'.route('tryouts.show', $tryout).'</loc>')
        ->toContain('<loc>'.route('camps.show', $camp).'</loc>')
        ->toContain('<loc>'.route('merch.show', $product).'</loc>')
        ->not->toContain(route('tryouts.show', $hiddenTryout))
        ->not->toContain(route('camps.show', $hiddenCamp))
        ->not->toContain(route('merch.show', $hiddenProduct));
});

test('robots.txt allows crawlers and links the sitemap', function () {
    $content = $this->get(route('robots'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->getContent();

    expect($content)->toContain('User-agent: *')
        ->toContain('Disallow: /admin')
        ->toContain('User-agent: GPTBot')
        ->toContain('User-agent: ClaudeBot')
        ->toContain('Sitemap: '.route('sitemap'));
});

test('llms.txt summarizes the organization and programs', function () {
    $tryout = Tryout::factory()->create(['title' => '13U Tryouts']);
    Camp::factory()->create(['name' => 'Winter Hitting Camp']);

    $content = $this->get(route('llms'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->getContent();

    expect($content)->toContain('# Eagles Baseball Travel')
        ->toContain('630-767-9208')
        ->toContain('13U Tryouts')
        ->toContain(route('tryouts.show', $tryout))
        ->toContain('Winter Hitting Camp');
});
