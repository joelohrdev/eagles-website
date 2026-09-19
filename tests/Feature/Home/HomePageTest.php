<?php

use App\Models\Camp;
use App\Models\CampRegistration;
use App\Models\Tryout;
use App\Models\TryoutRegistration;
use App\Services\SiteSettings;
use Illuminate\Support\Facades\DB;
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

test('home page counts registrations without a query per card', function () {
    $camp = Camp::factory()->create(['capacity' => 10, 'starts_at' => now()->addDay()]);
    CampRegistration::factory()->create(['camp_id' => $camp->id]);
    CampRegistration::factory()->pending()->create(['camp_id' => $camp->id]);
    CampRegistration::factory()->cancelled()->create(['camp_id' => $camp->id]);
    Camp::factory()->count(2)->create(['capacity' => 10, 'starts_at' => now()->addMonth()]);

    $tryout = Tryout::factory()->create(['capacity' => 5, 'event_at' => now()->addDay()]);
    TryoutRegistration::factory()->count(2)->create(['tryout_id' => $tryout->id]);
    Tryout::factory()->count(2)->create(['capacity' => 5, 'event_at' => now()->addMonth()]);

    DB::enableQueryLog();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('camps.0.spots_remaining', 8)
            ->where('tryouts.0.spots_remaining', 3)
        );

    $registrationCounts = collect(DB::getQueryLog())
        ->filter(fn (array $query) => preg_match('/^select count\(\*\) as "aggregate" from "(camp|tryout)_registrations"/', $query['query']));

    expect($registrationCounts)->toBeEmpty();
});
