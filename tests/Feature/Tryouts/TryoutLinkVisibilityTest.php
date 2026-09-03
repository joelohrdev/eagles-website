<?php

use App\Models\NavigationItem;
use App\Models\Tryout;
use App\Models\TryoutRegistration;
use App\Services\SiteSettings;
use App\Services\TryoutAvailability;

/**
 * A header menu with two tryout links (one by route name, one typed by hand)
 * and one unrelated link.
 */
function seedTryoutMenu(): void
{
    NavigationItem::factory()->header()->create(['label' => 'Tryouts', 'route_name' => 'tryouts.index', 'sort_order' => 0]);
    NavigationItem::factory()->header()->custom('/tryouts/9u-tryouts')->create(['label' => 'Try out for 9U', 'sort_order' => 1]);
    NavigationItem::factory()->header()->create(['label' => 'Camps', 'route_name' => 'camps.index', 'sort_order' => 2]);
}

test('tryout links are hidden everywhere while no tryout is open', function () {
    Tryout::factory()->unpublished()->create();
    Tryout::factory()->past()->create();
    Tryout::factory()->registrationClosed()->create();
    Tryout::factory()->registrationUpcoming()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('tryoutsOpen', false)
            ->where('navigation.settings.nav_show_cta', false)
            ->where('home.home_hero_cta_label', null)
            ->where('home.home_hero_cta_url', null)
            ->has('tryouts', 0)
            ->where('navigation.menus.header', fn ($items) => collect($items)->doesntContain('href', route('tryouts.index', absolute: false)))
            ->where('navigation.menus.footer', fn ($items) => collect($items)->doesntContain('href', route('tryouts.index', absolute: false)))
            ->etc());
});

test('tryout links come back as soon as one tryout is open', function () {
    Tryout::factory()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('tryoutsOpen', true)
            ->where('navigation.settings.nav_show_cta', true)
            ->where('home.home_hero_cta_url', '/tryouts')
            ->has('tryouts', 1)
            ->where('navigation.menus.header', fn ($items) => collect($items)->contains('href', route('tryouts.index', absolute: false)))
            ->etc());
});

test('admin configured tryout links are hidden too, by route name or typed url', function () {
    seedTryoutMenu();

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('navigation.menus.header', 1)
            ->where('navigation.menus.header.0.label', 'Camps')
            ->etc());
});

test('admin configured tryout links show again once a tryout is open', function () {
    seedTryoutMenu();
    Tryout::factory()->create();

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->has('navigation.menus.header', 3)->etc());
});

test('a header cta pointing somewhere other than tryouts is left alone', function () {
    app(SiteSettings::class)->set('nav_cta_url', '/camps');

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('tryoutsOpen', false)
            ->where('navigation.settings.nav_show_cta', true)
            ->etc());
});

test('a tryout at capacity does not count as open', function () {
    $tryout = Tryout::factory()->create(['capacity' => 2]);
    TryoutRegistration::factory()->count(2)->create(['tryout_id' => $tryout->id]);

    expect((new TryoutAvailability)->isOpen())->toBeFalse();

    $tryout->update(['capacity' => 3]);

    expect((new TryoutAvailability)->isOpen())->toBeTrue();
});

test('the open scope matches isRegistrationOpen for every tryout state', function (string $state) {
    $tryout = Tryout::factory()->{$state}()->create();

    expect(Tryout::query()->openForRegistration()->whereKey($tryout)->exists())
        ->toBe($tryout->isRegistrationOpen());
})->with(['unpublished', 'past', 'registrationClosed', 'registrationUpcoming']);

test('the tryouts page itself stays reachable with no open tryouts', function () {
    $this->get(route('tryouts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('site/Tryouts/Index')->has('tryouts', 0)->etc());
});
