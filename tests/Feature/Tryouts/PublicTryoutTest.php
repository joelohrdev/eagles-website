<?php

use App\Models\Tryout;
use Inertia\Testing\AssertableInertia as Assert;

test('the tryouts index lists published upcoming tryouts only', function () {
    $visible = Tryout::factory()->create(['title' => 'Visible']);
    Tryout::factory()->unpublished()->create(['title' => 'Draft']);
    Tryout::factory()->past()->create(['title' => 'Past']);

    $this->get(route('tryouts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('site/Tryouts/Index')
            ->has('tryouts', 1)
            ->where('tryouts.0.id', $visible->id)
            ->where('tryouts.0.registration_state', 'open')
            ->has('seo.title')
            ->has('seo.json_ld')
        );
});

test('the tryouts index shows an empty state when nothing is scheduled', function () {
    $this->get(route('tryouts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('tryouts', 0));
});

test('a published tryout page renders with event schema and share meta', function () {
    $tryout = Tryout::factory()->create(['title' => '12U Tryouts', 'division' => '12U', 'location' => 'Main Field']);

    $this->get(route('tryouts.show', $tryout))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('site/Tryouts/Show')
            ->where('tryout.slug', $tryout->slug)
            ->where('registered', false)
            ->where('seo.og_type', 'article')
            ->where('seo.canonical_url', route('tryouts.show', $tryout))
            ->where('seo.share_title', '12U Tryouts')
            ->where('seo.json_ld.1.@type', 'SportsEvent')
        );
});

test('the tryout page reflects the registered flag', function () {
    $tryout = Tryout::factory()->create();

    $this->get(route('tryouts.show', ['tryout' => $tryout, 'registered' => 1]))
        ->assertInertia(fn (Assert $page) => $page->where('registered', true));
});

test('an unpublished tryout returns 404', function () {
    $tryout = Tryout::factory()->unpublished()->create();

    $this->get(route('tryouts.show', $tryout))->assertNotFound();
    $this->get(route('tryouts.register', $tryout))->assertNotFound();
});

test('stored seo meta overrides the auto-generated values', function () {
    $tryout = Tryout::factory()->create();
    $tryout->seoMeta()->create(['title' => 'Custom Title', 'share_description' => 'Custom share']);

    $this->get(route('tryouts.show', $tryout))
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', 'Custom Title | Eagles Baseball Travel')
            ->where('seo.share_description', 'Custom share')
        );
});
