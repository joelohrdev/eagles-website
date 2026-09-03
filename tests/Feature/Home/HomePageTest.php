<?php

use App\Models\Camp;
use App\Models\Tryout;
use App\Services\SiteSettings;
use Inertia\Testing\AssertableInertia as Assert;

test('home page renders with settings, featured content, and seo', function () {
    Tryout::factory()->create();
    Tryout::factory()->unpublished()->create();
    Camp::factory()->create();
    Camp::factory()->past()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('site/Home/Index')
            ->has('home.home_hero_headline')
            ->has('home.home_offerings')
            ->has('tryouts', 1)
            ->has('camps', 1)
            ->has('seo', fn (Assert $seo) => $seo
                ->where('site_name', 'Eagles Baseball Travel')
                ->where('title', 'Youth Travel Baseball Teams, Tryouts & Camps | Eagles Baseball Travel')
                ->has('json_ld', 2)
                ->where('json_ld.0.@type', 'SportsOrganization')
                ->where('json_ld.1.@type', 'WebSite')
                ->etc()
            )
        );
});

test('home page includes faq schema when faqs are configured', function () {
    app(SiteSettings::class)->set('seo_faq', [
        ['question' => 'What ages?', 'answer' => '9U to 17U.'],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('faqs', 1)
            ->where('seo.json_ld.2.@type', 'FAQPage')
        );
});
