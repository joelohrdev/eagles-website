<?php

use App\Models\Coach;
use App\Models\Team;

test('the teams page lists active teams with coaches and seo data', function () {
    $coach = Coach::factory()->create(['name' => 'Coach Smith']);
    Team::factory()->create(['name' => 'Eagles 12U Navy', 'coach_id' => $coach->id]);
    Team::factory()->inactive()->create(['name' => 'Hidden Team']);

    $this->get(route('teams.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/Teams/Index')
            ->has('teams', 1)
            ->where('teams.0.name', 'Eagles 12U Navy')
            ->where('teams.0.coach.name', 'Coach Smith')
            ->where('seo.title', 'Teams | Eagles Baseball Travel')
            ->has('seo.json_ld', 3) // organization, breadcrumbs, one SportsTeam
        );
});

test('the teams page renders with no teams', function () {
    $this->get(route('teams.index'))->assertOk()->assertInertia(fn ($page) => $page->has('teams', 0));
});
