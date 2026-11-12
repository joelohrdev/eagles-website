<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Camp;
use App\Models\CampAgeGroup;
use App\Models\CampCoach;
use App\Models\CampDayTime;
use App\Models\CampSession;
use App\Models\CampSessionDay;
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
            ->with(['ageGroups', 'sessions.days.times'])
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
            ...$camp->only(['id', 'name', 'slug', 'location', 'address', 'age_range', 'starts_at', 'ends_at', 'price', 'capacity', 'image_url', 'image_thumbnail_url']),
            'map_url' => $camp->mapUrl(),
            'is_free' => $camp->isFree(),
            'has_pricing_options' => $camp->hasPricingOptions(),
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
            'coaches' => $camp->coaches()->get()->map(fn (CampCoach $coach): array => $coach->only(['id', 'name', 'title', 'bio', 'photo_url']))->all(),
            ...self::presentSchedule($camp),
        ];
    }

    /**
     * Sessions, days and per-age-group times, with the places left in each
     * (day, age group) slot so full ones can be shown and disabled.
     *
     * @return array{bundle_price: int|null, age_groups: list<array{id: int, name: string}>, sessions: list<array<string, mixed>>}
     */
    public static function presentSchedule(Camp $camp): array
    {
        $camp->loadSchedule();
        $slots = collect($camp->slots())->groupBy('day_id');

        return [
            'bundle_price' => $camp->sessions->count() > 1 ? $camp->bundle_price : null,
            'age_groups' => $camp->ageGroups->map(fn (CampAgeGroup $group): array => ['id' => $group->id, 'name' => $group->name])->all(),
            'sessions' => $camp->sessions->map(fn (CampSession $session): array => [
                'id' => $session->id,
                'name' => $session->name,
                'description' => $session->description,
                'price' => $session->price,
                'day_price' => $session->day_price,
                'days' => $session->days->map(fn (CampSessionDay $day): array => [
                    'id' => $day->id,
                    'date' => $day->date?->toDateString(),
                    'title' => $day->title(),
                    'label' => $day->label,
                    'times' => $day->times->map(fn (CampDayTime $time): array => [
                        'age_group_id' => $time->camp_age_group_id,
                        'range' => $time->range(),
                    ])->all(),
                    'remaining' => $slots->get($day->id, collect())->map(fn (array $slot): array => [
                        'age_group_id' => $slot['age_group_id'],
                        'remaining' => $slot['remaining'],
                    ])->values()->all(),
                ])->all(),
            ])->all(),
        ];
    }

    public static function autoDescription(Camp $camp): string
    {
        $when = $camp->starts_at?->format('M j, Y');
        $where = $camp->location ? " at {$camp->location}" : '';
        $ages = $camp->age_range ? " for ages {$camp->age_range}" : '';
        $price = $camp->isFree() ? 'Free' : ($camp->hasPricingOptions() ? 'From $' : '$').number_format($camp->price / 100, 2);

        $registration = match ($camp->registrationState()) {
            'open' => $camp->registration_closes_at ? 'Registration open until '.$camp->registration_closes_at->format('M j').'.' : 'Registration open now.',
            'upcoming' => 'Registration opens '.$camp->registration_opens_at?->format('M j').'.',
            'full' => 'This camp is full.',
            default => 'Registration is closed.',
        };

        $on = $when ? " on {$when}" : '';

        return "{$camp->name}{$on}{$where}{$ages}. {$price}. {$registration}";
    }
}
