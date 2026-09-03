<?php

use App\Models\NavigationItem;
use App\Models\User;
use App\Services\SiteSettings;

test('staff cannot access the navigation manager', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.navigation.index'))
        ->assertForbidden();
});

test('admin sees the navigation manager with defaults seeded into the database', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.navigation.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/navigation/Index')
            ->has('menus.header', 7)
            ->has('menus.footer', 7)
            ->has('menus.footer_bottom', 1)
            ->has('pages')
            ->where('settings.nav_cta_label', 'Tryouts'));

    expect(NavigationItem::query()->location('header')->count())->toBe(7);
});

test('admin can add a page link and a custom link', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.navigation.items.store'), [
            'location' => 'header',
            'label' => 'Schedule',
            'link_type' => 'page',
            'route_name' => 'camps.index',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->post(route('admin.navigation.items.store'), [
            'location' => 'footer_bottom',
            'label' => 'GameChanger',
            'link_type' => 'custom',
            'url' => 'https://gc.com/eagles',
            'opens_in_new_tab' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $page = NavigationItem::query()->where('label', 'Schedule')->firstOrFail();
    $custom = NavigationItem::query()->where('label', 'GameChanger')->firstOrFail();

    expect($page->route_name)->toBe('camps.index')
        ->and($page->href())->toBe(route('camps.index', absolute: false))
        ->and($custom->url)->toBe('https://gc.com/eagles')
        ->and($custom->opens_in_new_tab)->toBeTrue()
        ->and($custom->sort_order)->toBe(1);
});

test('links are validated', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.navigation.items.store'), [
            'location' => 'sidebar',
            'label' => '',
            'link_type' => 'page',
            'route_name' => 'admin.dashboard',
        ])
        ->assertSessionHasErrors(['location', 'label', 'route_name']);

    $this->actingAs($admin)
        ->post(route('admin.navigation.items.store'), [
            'location' => 'header',
            'label' => 'Bad',
            'link_type' => 'custom',
            'url' => 'javascript:alert(1)',
        ])
        ->assertSessionHasErrors(['url']);
});

test('admin can update, hide, and delete a link', function () {
    $admin = User::factory()->admin()->create();
    $item = NavigationItem::factory()->header()->create(['label' => 'Teams']);

    $this->actingAs($admin)
        ->patch(route('admin.navigation.items.update', $item), [
            'label' => 'Our Teams',
            'link_type' => 'page',
            'route_name' => 'teams.index',
            'is_visible' => 0,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($item->refresh())->label->toBe('Our Teams')->is_visible->toBeFalse();

    $this->actingAs($admin)
        ->delete(route('admin.navigation.items.destroy', $item))
        ->assertRedirect();

    $this->assertModelMissing($item);
});

test('admin can reorder a menu and the public site reflects it', function () {
    $admin = User::factory()->admin()->create();
    [$a, $b, $c] = NavigationItem::factory()->header()->count(3)->sequence(
        ['label' => 'A', 'sort_order' => 0],
        ['label' => 'B', 'sort_order' => 1],
        ['label' => 'C', 'sort_order' => 2],
    )->create();

    $this->actingAs($admin)
        ->post(route('admin.navigation.reorder'), ['location' => 'header', 'order' => [$c->id, $a->id, $b->id]])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(NavigationItem::query()->location('header')->ordered()->pluck('label')->all())->toBe(['C', 'A', 'B']);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('navigation.menus.header.0.label', 'C')
            ->where('navigation.menus.header.2.label', 'B'));
});

test('reorder rejects ids from another menu', function () {
    $admin = User::factory()->admin()->create();
    $header = NavigationItem::factory()->header()->create();
    $footer = NavigationItem::factory()->footer()->create();

    $this->actingAs($admin)
        ->post(route('admin.navigation.reorder'), ['location' => 'header', 'order' => [$header->id, $footer->id]])
        ->assertSessionHasErrors(['order.1']);
});

test('admin can update header and footer settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.navigation.settings.update'), [
            'nav_cta_label' => 'Register',
            'nav_cta_url' => '/tryouts',
            'nav_show_cta' => 1,
            'nav_show_cart' => 0,
            'footer_tagline' => 'Play hard.',
            'footer_links_heading' => 'Pages',
            'footer_contact_heading' => 'Reach us',
            'footer_show_contact' => 1,
            'footer_show_socials' => 0,
            'footer_show_address' => 1,
            'footer_copyright' => '© {year} {org}',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $settings = app(SiteSettings::class);

    expect($settings->get('nav_show_cart'))->toBeFalse()
        ->and($settings->get('footer_tagline'))->toBe('Play hard.');

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('navigation.settings.nav_show_cart', false)
            ->where('navigation.settings.footer_links_heading', 'Pages')
            ->where('navigation.settings.footer_copyright', '© '.now()->year.' '.$settings->get('org_name')));
});
