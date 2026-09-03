<?php

use App\Models\Camp;

test('camps index lists published upcoming camps only', function () {
    $published = Camp::factory()->create(['name' => 'Visible Camp']);
    Camp::factory()->unpublished()->create(['name' => 'Draft Camp']);
    Camp::factory()->past()->create(['name' => 'Past Camp']);

    $this->get(route('camps.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/Camps/Index')
            ->has('camps', 1)
            ->where('camps.0.name', 'Visible Camp')
            ->where('camps.0.url', route('camps.show', $published))
            ->hasAll(['camps.0.registration_state', 'camps.0.spots_remaining', 'camps.0.is_free'])
            ->where('seo.title', 'Baseball Camps & Clinics | Eagles Baseball Travel')
            ->has('seo.json_ld')
        );
});

test('camp show page renders with seo, event schema, and share data', function () {
    $camp = Camp::factory()->create(['name' => 'Summer Skills Camp', 'location' => 'Eagles Facility', 'price' => 12500]);

    $this->get(route('camps.show', $camp))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/Camps/Show')
            ->where('camp.name', 'Summer Skills Camp')
            ->where('camp.registration_state', 'open')
            ->where('registered', false)
            ->where('seo.title', 'Summer Skills Camp | Eagles Baseball Travel')
            ->where('seo.og_type', 'article')
            ->where('seo.canonical_url', route('camps.show', $camp))
            ->where('seo.json_ld.1.@type', 'SportsEvent')
            ->where('seo.json_ld.1.offers.price', '125.00')
        );
});

test('camp show page uses stored seo meta when present', function () {
    $camp = Camp::factory()->create();
    $camp->seoMeta()->create(['title' => 'Custom Camp Title', 'share_title' => 'Custom Share']);

    $this->get(route('camps.show', $camp))
        ->assertInertia(fn ($page) => $page
            ->where('seo.title', 'Custom Camp Title | Eagles Baseball Travel')
            ->where('seo.share_title', 'Custom Share')
        );
});

test('unpublished camps return 404 on show and register pages', function () {
    $camp = Camp::factory()->unpublished()->create();

    $this->get(route('camps.show', $camp))->assertNotFound();
    $this->get(route('camps.register', $camp))->assertNotFound();
    $this->post(route('camps.register.store', $camp), [])->assertNotFound();
});

test('register page renders the form when open and a closed state otherwise', function () {
    $open = Camp::factory()->create();
    $closed = Camp::factory()->registrationClosed()->create();

    $this->get(route('camps.register', $open))->assertOk()
        ->assertInertia(fn ($page) => $page->component('site/Camps/Register')->where('camp.registration_state', 'open')->where('seo.robots', 'noindex,follow'));

    $this->get(route('camps.register', $closed))->assertOk()
        ->assertInertia(fn ($page) => $page->where('camp.registration_state', 'closed'));
});
