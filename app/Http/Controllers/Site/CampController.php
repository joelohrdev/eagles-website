<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Camp;
use App\Services\SeoResolver;
use App\Support\Seo\Schema;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampController extends Controller
{
    public function __construct(private SeoResolver $seo) {}

    public function index(): Response
    {
        $camps = Camp::query()
            ->published()
            ->upcoming()
            ->ordered()
            ->get()
            ->map(fn (Camp $camp) => self::presentSummary($camp));

        $seo = $this->seo->forRoute('camps.index', [
            'title' => 'Baseball Camps & Clinics',
            'description' => 'Youth baseball camps and clinics from Eagles Baseball Travel — hitting, pitching, fielding, and skills camps for players 9U–17U. Register online.',
            'json_ld' => [
                Schema::breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Camps', 'url' => route('camps.index')],
                ]),
            ],
        ]);

        return Inertia::render('site/Camps/Index', [
            'camps' => $camps,
            'seo' => $seo->toArray(),
        ]);
    }

    public function show(Request $request, Camp $camp): Response
    {
        abort_unless($camp->is_published, 404);

        $camp->load('seoMeta');

        $seo = $this->seo->forModel($camp, [
            'title' => $camp->name,
            'description' => self::autoDescription($camp),
            'share_image_path' => $camp->image_path,
            'og_type' => 'article',
            'json_ld' => [
                Schema::campEvent($camp),
                Schema::breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Camps', 'url' => route('camps.index')],
                    ['name' => $camp->name, 'url' => route('camps.show', $camp)],
                ]),
            ],
        ]);

        return Inertia::render('site/Camps/Show', [
            'camp' => self::presentDetail($camp),
            'registered' => $request->boolean('registered'),
            'seo' => $seo->toArray(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function presentSummary(Camp $camp): array
    {
        return [
            ...$camp->only(['id', 'name', 'slug', 'location', 'age_range', 'starts_at', 'ends_at', 'price', 'capacity', 'image_url', 'image_thumbnail_url']),
            'is_free' => $camp->isFree(),
            'registration_state' => $camp->registrationState(),
            'spots_remaining' => $camp->spotsRemaining(),
            'registration_opens_at' => $camp->registration_opens_at,
            'registration_closes_at' => $camp->registration_closes_at,
            'url' => route('camps.show', $camp),
            'register_url' => route('camps.register', $camp),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function presentDetail(Camp $camp): array
    {
        return [
            ...self::presentSummary($camp),
            'description' => $camp->description,
            'youtube_url' => $camp->youtube_url,
        ];
    }

    public static function autoDescription(Camp $camp): string
    {
        $when = $camp->starts_at->format('M j, Y');
        $where = $camp->location ? " at {$camp->location}" : '';
        $ages = $camp->age_range ? " for ages {$camp->age_range}" : '';
        $price = $camp->isFree() ? 'Free' : '$'.number_format($camp->price / 100, 2);

        $registration = match ($camp->registrationState()) {
            'open' => $camp->registration_closes_at ? 'Registration open until '.$camp->registration_closes_at->format('M j').'.' : 'Registration open now.',
            'upcoming' => 'Registration opens '.$camp->registration_opens_at?->format('M j').'.',
            'full' => 'This camp is full.',
            default => 'Registration is closed.',
        };

        return "{$camp->name} on {$when}{$where}{$ages}. {$price}. {$registration}";
    }
}
