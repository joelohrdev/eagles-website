<?php

use App\Models\Sponsor;
use Inertia\Testing\AssertableInertia as Assert;

test('the homepage lists active sponsors in their manual order', function () {
    $second = Sponsor::factory()->create(['name' => 'Second', 'sort_order' => 2]);
    $first = Sponsor::factory()->create(['name' => 'First', 'sort_order' => 1]);
    Sponsor::factory()->inactive()->create(['sort_order' => 0]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('site/Home/Index')
            ->has('sponsors', 2)
            ->where('sponsors.0.id', $first->id)
            ->where('sponsors.0.logo_url', "/storage/{$first->logo_path}")
            ->where('sponsors.1.id', $second->id)
        );
});

test('the homepage passes no sponsors when there are none', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('sponsors', 0));
});
