<?php

use App\Models\FacilityPhoto;
use App\Services\SiteSettings;

test('the facility page shows settings and ordered photos with seo data', function () {
    app(SiteSettings::class)->setMany([
        'facility_heading' => 'Eagles Training Center',
        'facility_description' => 'Indoor cages and turf.',
    ]);
    FacilityPhoto::factory()->create(['sort_order' => 2, 'caption' => 'Second']);
    FacilityPhoto::factory()->create(['sort_order' => 1, 'caption' => 'First']);

    $this->get(route('facility'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/Facility/Index')
            ->where('facility.facility_heading', 'Eagles Training Center')
            ->has('photos', 2)
            ->where('photos.0.caption', 'First')
            ->where('seo.title', 'Eagles Training Center | Eagles Baseball Travel')
            ->where('seo.description', 'Indoor cages and turf.')
        );
});

test('the facility page renders with defaults and no photos', function () {
    $this->get(route('facility'))->assertOk()
        ->assertInertia(fn ($page) => $page->has('photos', 0)->where('facility.facility_heading', 'Our Facility'));
});
