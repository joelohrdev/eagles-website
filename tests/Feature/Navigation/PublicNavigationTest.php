<?php

use App\Models\NavigationItem;
use App\Models\Tryout;

test('public pages receive default menus when nothing is configured', function () {
    Tryout::factory()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('navigation.menus.header', 7)
            ->where('navigation.menus.header.0.label', 'Teams')
            ->where('navigation.menus.header.0.href', route('teams.index', absolute: false))
            ->has('navigation.menus.footer', 7)
            ->has('navigation.menus.footer_bottom', 1)
            ->where('navigation.settings.nav_show_cta', true));
});

test('hidden links are excluded and custom links are flagged external', function () {
    NavigationItem::factory()->header()->create(['label' => 'Shown', 'sort_order' => 0]);
    NavigationItem::factory()->header()->hidden()->create(['label' => 'Hidden', 'sort_order' => 1]);
    NavigationItem::factory()->header()->custom('https://gc.com/eagles')->create(['label' => 'Scores', 'sort_order' => 2, 'opens_in_new_tab' => true]);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('navigation.menus.header', 2)
            ->where('navigation.menus.header.0.label', 'Shown')
            ->where('navigation.menus.header.1.label', 'Scores')
            ->where('navigation.menus.header.1.external', true)
            ->where('navigation.menus.header.1.new_tab', true));
});

test('the rendered header and footer contain the configured links', function () {
    NavigationItem::factory()->header()->create(['label' => 'Header Link One', 'route_name' => 'camps.index']);
    NavigationItem::factory()->footer()->create(['label' => 'Footer Link One', 'route_name' => 'contact']);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('navigation.menus.header.0.label', 'Header Link One')
            ->where('navigation.menus.footer.0.label', 'Footer Link One'));
});
