<?php

use App\Models\Camp;
use App\Models\NavigationItem;
use App\Models\Product;
use App\Models\User;
use App\Services\PageVisibility;
use App\Services\SiteSettings;

function disablePage(string $page): void
{
    app(SiteSettings::class)->set(PageVisibility::settingKey($page), false);
}

test('every page is switched on by default', function () {
    expect(app(PageVisibility::class)->enabled())->toBe([
        'teams' => true,
        'facility' => true,
        'coaches' => true,
        'camps' => true,
        'merch' => true,
        'contact' => true,
    ]);
});

test('a switched off page is not found', function (string $page, string $route) {
    $this->get(route($route))->assertOk();

    disablePage($page);

    $this->get(route($route))->assertNotFound();
})->with([
    ['teams', 'teams.index'],
    ['facility', 'facility'],
    ['coaches', 'coaches.index'],
    ['camps', 'camps.index'],
    ['merch', 'merch.index'],
    ['contact', 'contact'],
]);

test('switching off camps closes the camp detail and registration pages', function () {
    $camp = Camp::factory()->create();

    disablePage('camps');

    $this->get(route('camps.show', $camp))->assertNotFound();
    $this->get(route('camps.register', $camp))->assertNotFound();
    $this->post(route('camps.register.store', $camp))->assertNotFound();
});

test('switching off merch closes the store, the cart, and checkout', function () {
    $product = Product::factory()->create();

    disablePage('merch');

    $this->get(route('merch.show', $product))->assertNotFound();
    $this->get(route('cart.index'))->assertNotFound();
    $this->post(route('cart.items.store'))->assertNotFound();
    $this->get(route('checkout.create'))->assertNotFound();
});

test('switching off merch hides the cart icon', function () {
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('navigation.settings.nav_show_cart', true));

    disablePage('merch');

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('navigation.settings.nav_show_cart', false));
});

test('menu items pointing at a switched off page are removed', function () {
    NavigationItem::factory()->header()->create(['label' => 'Store', 'route_name' => 'merch.index']);
    NavigationItem::factory()->header()->create(['label' => 'Coaches', 'route_name' => 'coaches.index']);
    NavigationItem::factory()->footer()->create(['label' => 'Store', 'route_name' => 'merch.index']);

    disablePage('merch');

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('navigation.menus.header', 1)
            ->where('navigation.menus.header.0.label', 'Coaches')
            ->has('navigation.menus.footer', 0));
});

test('a hero call to action pointing at a switched off page is blanked', function () {
    app(SiteSettings::class)->setMany([
        'home_hero_cta_label' => 'Shop',
        'home_hero_cta_url' => '/merch',
        'nav_cta_label' => 'Shop',
        'nav_cta_url' => '/merch',
    ]);

    disablePage('merch');

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('home.home_hero_cta_url', null)
            ->where('home.home_hero_cta_label', null)
            ->where('navigation.settings.nav_show_cta', false));
});

test('the home page camps section goes with the camps page', function () {
    Camp::factory()->create();

    $this->get(route('home'))->assertInertia(fn ($page) => $page->has('camps', 1));

    disablePage('camps');

    $this->get(route('home'))->assertInertia(fn ($page) => $page->has('camps', 0));
});

test('switched off pages are dropped from the sitemap and llms.txt', function () {
    disablePage('merch');
    Product::factory()->create();

    $sitemap = $this->get(route('sitemap'))->assertOk()->getContent();
    $llms = $this->get(route('llms'))->assertOk()->getContent();

    expect($sitemap)->not->toContain(route('merch.index'))
        ->and($sitemap)->toContain(route('teams.index'))
        ->and($llms)->not->toContain(route('merch.index'))
        ->and($llms)->toContain(route('teams.index'));
});

test('admin can switch a page off from site settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.settings.edit', 'pages'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/settings/Edit')
            ->has('pages', 6)
            ->where('settings.page_merch_enabled', true));

    $this->actingAs($admin)
        ->put(route('admin.settings.update', 'pages'), [
            'page_teams_enabled' => 1,
            'page_facility_enabled' => 1,
            'page_coaches_enabled' => 1,
            'page_camps_enabled' => 1,
            'page_merch_enabled' => 0,
            'page_contact_enabled' => 1,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.settings.edit', 'pages'));

    expect(app(PageVisibility::class)->isEnabled('merch'))->toBeFalse()
        ->and(app(PageVisibility::class)->isEnabled('teams'))->toBeTrue();

    $this->get(route('merch.index'))->assertNotFound();
});

test('staff cannot switch pages off', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.settings.edit', 'pages'))
        ->assertForbidden();
});
