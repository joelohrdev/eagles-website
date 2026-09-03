<?php

use App\Models\Coach;

test('the coaches page lists active coaches ordered with seo data', function () {
    Coach::factory()->create(['name' => 'Second', 'sort_order' => 2]);
    Coach::factory()->create(['name' => 'First', 'sort_order' => 1]);
    Coach::factory()->inactive()->create(['name' => 'Hidden']);

    $this->get(route('coaches.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/Coaches/Index')
            ->has('coaches', 2)
            ->where('coaches.0.name', 'First')
            ->where('seo.title', 'Coaching Staff | Eagles Baseball Travel')
        );
});
