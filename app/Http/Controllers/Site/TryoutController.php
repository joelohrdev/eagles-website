<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Tryout;
use App\Services\SeoResolver;
use App\Support\Seo\Schema;
use Inertia\Inertia;
use Inertia\Response;

class TryoutController extends Controller
{
    public function __construct(private SeoResolver $seo) {}

    public function index(): Response
    {
        $tryouts = Tryout::query()
            ->published()
            ->upcoming()
            ->ordered()
            ->get()
            ->map(fn (Tryout $tryout) => self::present($tryout));

        return Inertia::render('site/Tryouts/Index', [
            'tryouts' => $tryouts,
            'seo' => $this->seo->forRoute('tryouts.index', [
                'title' => 'Tryouts',
                'description' => 'Upcoming tryout dates for Eagles Baseball Travel teams (9U–17U). See dates, locations, divisions, and register online.',
                'json_ld' => [
                    Schema::breadcrumbs([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Tryouts', 'url' => route('tryouts.index')],
                    ]),
                ],
            ])->toArray(),
        ]);
    }

    public function show(Tryout $tryout): Response
    {
        abort_unless($tryout->is_published, 404);

        return Inertia::render('site/Tryouts/Show', [
            'tryout' => self::present($tryout),
            'registered' => request()->boolean('registered'),
            'seo' => $this->seo->forModel($tryout, [
                'title' => $tryout->title,
                'description' => self::autoDescription($tryout),
                'share_image_path' => $tryout->image_path,
                'og_type' => 'article',
                'json_ld' => [
                    Schema::tryoutEvent($tryout),
                    Schema::breadcrumbs([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Tryouts', 'url' => route('tryouts.index')],
                        ['name' => $tryout->title, 'url' => route('tryouts.show', $tryout)],
                    ]),
                ],
            ])->toArray(),
        ]);
    }

    /**
     * Shape a tryout for the public frontend.
     *
     * @return array<string, mixed>
     */
    public static function present(Tryout $tryout): array
    {
        return [
            'id' => $tryout->id,
            'title' => $tryout->title,
            'slug' => $tryout->slug,
            'division' => $tryout->division,
            'location' => $tryout->location,
            'description' => $tryout->description,
            'event_at' => $tryout->event_at->toIso8601String(),
            'registration_opens_at' => $tryout->registration_opens_at?->toIso8601String(),
            'registration_closes_at' => $tryout->registration_closes_at?->toIso8601String(),
            'capacity' => $tryout->capacity,
            'image_url' => $tryout->image_url,
            'image_thumbnail_url' => $tryout->image_thumbnail_url,
            'registration_state' => $tryout->registrationState(),
            'spots_remaining' => $tryout->spotsRemaining(),
            'url' => route('tryouts.show', $tryout),
            'register_url' => route('tryouts.register', $tryout),
        ];
    }

    public static function autoDescription(Tryout $tryout): string
    {
        $when = $tryout->event_at->format('l, F j, Y \a\t g:i A');
        $where = $tryout->location ? " at {$tryout->location}" : '';

        $registration = match ($tryout->registrationState()) {
            'open' => $tryout->registration_closes_at
                ? 'Registration is open until '.$tryout->registration_closes_at->format('F j').'. Register now.'
                : 'Registration is open — register now.',
            'upcoming' => 'Registration opens '.$tryout->registration_opens_at?->format('F j').'.',
            'full' => 'Registration is full.',
            default => 'Registration is closed.',
        };

        return "Eagles Baseball Travel {$tryout->division} tryouts on {$when}{$where}. {$registration}";
    }
}
