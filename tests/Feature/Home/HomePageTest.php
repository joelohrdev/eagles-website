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
            ->has('home.home_mission_body')
            ->has('home.home_belief')
            ->has('home.home_values', 8)
            ->has('home.home_goals', 4)
            ->has('home.home_development_tiers', 3)
            ->has('home.home_year_round_items', 4)
            ->has('home.home_whats_new_items', 5)
            ->has('home.home_closing_title')
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

test('home page renders edited program sections from site settings', function () {
    app(SiteSettings::class)->setMany([
        'home_whats_new_title' => 'New for 2027',
        'home_whats_new_items' => [
            ['title' => 'Weight Room', 'description' => null],
        ],
        'home_goals' => [],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('home.home_whats_new_title', 'New for 2027')
            ->has('home.home_whats_new_items', 1)
            ->where('home.home_whats_new_items.0.title', 'Weight Room')
            ->has('home.home_goals', 0)
        );
});
