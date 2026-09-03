<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\FacilityPhoto;
use App\Services\SeoResolver;
use App\Services\SiteSettings;
use App\Support\Seo\Schema;
use Inertia\Inertia;
use Inertia\Response;

class FacilityController extends Controller
{
    public function __construct(
        private SeoResolver $seo,
        private SiteSettings $settings,
    ) {}

    public function __invoke(): Response
    {
        $facility = $this->settings->group('facility');
        $photos = FacilityPhoto::query()->ordered()->get();

        return Inertia::render('site/Facility/Index', [
            'facility' => $facility,
            'photos' => $photos,
            'seo' => $this->seo->forRoute('facility', [
                'title' => $facility['facility_heading'] ?: 'Our Facility',
                'description' => $facility['facility_description'],
                'share_image_path' => $photos->first()?->image_path,
                'json_ld' => [
                    Schema::breadcrumbs([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Facility', 'url' => route('facility')],
                    ]),
                    array_filter([
                        '@context' => 'https://schema.org',
                        '@type' => 'SportsActivityLocation',
                        'name' => $facility['facility_heading'] ?: 'Eagles Training Facility',
                        'description' => $facility['facility_description'],
                        'address' => $facility['facility_address'],
                        'image' => $photos->map(fn (FacilityPhoto $photo) => $photo->image_url)->filter()->values()->all() ?: null,
                        'parentOrganization' => ['@id' => url('/').'#organization'],
                    ]),
                ],
            ])->toArray(),
        ]);
    }
}
