<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Camp;
use App\Models\Tryout;
use App\Services\ImageUploader;
use App\Services\PageVisibility;
use App\Services\SeoResolver;
use App\Services\SiteSettings;
use App\Services\TryoutAvailability;
use App\Support\Seo\Schema;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private SiteSettings $settings,
        private SeoResolver $seo,
        private TryoutAvailability $tryoutAvailability,
        private PageVisibility $pages,
    ) {}

    public function __invoke(): Response
    {
        $home = $this->settings->group('home');

        foreach (['home_hero_cta', 'home_hero_secondary_cta'] as $cta) {
            $url = $home["{$cta}_url"] ?? null;

            if ($this->tryoutAvailability->hidesLink($url) || $this->pages->hidesLink($url)) {
                $home["{$cta}_label"] = null;
                $home["{$cta}_url"] = null;
            }
        }

        $all = $this->settings->all();
        $faqs = collect($all['seo_faq'] ?? [])->filter(fn ($faq) => filled($faq['question'] ?? null) && filled($faq['answer'] ?? null))->values()->all();

        /** The whole section — cards and the "All tryouts" button — stays off until one is open. */
        $tryouts = $this->tryoutAvailability->isOpen()
            ? Tryout::query()->published()->upcoming()->ordered()->take(3)->get()
            : collect();

        $tryouts = $tryouts
            ->map(fn (Tryout $tryout) => [
                'id' => $tryout->id,
                'slug' => $tryout->slug,
                'title' => $tryout->title,
                'division' => $tryout->division,
                'location' => $tryout->location,
                'event_at' => $tryout->event_at,
                'registration_state' => $tryout->registrationState(),
                'spots_remaining' => $tryout->spotsRemaining(),
            ]);

        /** The camps section — cards and the "All camps" button — goes with the page. */
        $camps = $this->pages->isEnabled('camps')
            ? Camp::query()->published()->upcoming()->ordered()->take(3)->get()
            : collect();

        $camps = $camps
            ->map(fn (Camp $camp) => [
                'id' => $camp->id,
                'slug' => $camp->slug,
                'name' => $camp->name,
                'location' => $camp->location,
                'age_range' => $camp->age_range,
                'starts_at' => $camp->starts_at,
                'ends_at' => $camp->ends_at,
                'price' => $camp->price,
                'image_thumbnail_url' => $camp->image_thumbnail_url,
                'registration_state' => $camp->registrationState(),
                'spots_remaining' => $camp->spotsRemaining(),
            ]);

        return Inertia::render('site/Home/Index', [
            'home' => [
                ...$home,
                'home_hero_image_url' => ImageUploader::url($home['home_hero_image'] ?? null),
                'home_about_image_url' => ImageUploader::url($home['home_about_image'] ?? null),
            ],
            'faqs' => $faqs,
            'tryouts' => $tryouts,
            'camps' => $camps,
            'seo' => $this->seo->forRoute('home', [
                'title' => 'Youth Travel Baseball Teams, Tryouts & Camps',
                'description' => $home['home_intro'] ?? null,
                'share_image_path' => $home['home_hero_image'] ?? null,
                'json_ld' => array_values(array_filter([
                    Schema::website($all),
                    Schema::faq($faqs),
                ])),
            ])->toArray(),
        ]);
    }
}
