<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Camps\SyncCampCoaches;
use App\Actions\Camps\SyncCampSchedule;
use App\Actions\Seo\SyncSeoMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCampRequest;
use App\Http\Requests\Admin\UpdateCampRequest;
use App\Models\Camp;
use App\Models\CampAgeGroup;
use App\Models\CampCoach;
use App\Models\CampDayTime;
use App\Models\CampSession;
use App\Models\CampSessionDay;
use App\Services\ActivityLogger;
use App\Services\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CampController extends Controller
{
    public function __construct(
        private ImageUploader $images,
        private SyncSeoMeta $syncSeoMeta,
        private SyncCampSchedule $syncCampSchedule,
        private SyncCampCoaches $syncCampCoaches,
        private ActivityLogger $activity,
    ) {}

    public function index(): Response
    {
        $camps = Camp::query()
            ->with(['ageGroups', 'sessions.days.times'])
            ->withCount([
                'registrations as paid_registrations_count' => fn ($query) => $query->paid(),
                'registrations as active_registrations_count' => fn ($query) => $query->countsAgainstCapacity(),
            ])
            ->orderByDesc('starts_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Camp $camp) => [
                ...$camp->only(['id', 'name', 'slug', 'location', 'starts_at', 'ends_at', 'price', 'capacity', 'is_published', 'image_thumbnail_url', 'paid_registrations_count', 'active_registrations_count']),
                'registration_state' => $camp->is_published ? $camp->registrationState() : 'draft',
                'spots_remaining' => $camp->spotsRemaining(),
            ]);

        return Inertia::render('admin/camps/Index', [
            'camps' => $camps,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/camps/Create');
    }

    public function store(StoreCampRequest $request): RedirectResponse
    {
        $camp = DB::transaction(fn (): Camp => $this->syncCampSchedule->handle(
            Camp::query()->create([
                ...$request->campAttributes(),
                'starts_at' => $request->scheduleStartsAt(),
            ]),
            $request->schedule(),
        ));

        if ($request->hasFile('image')) {
            $camp->update(['image_path' => $this->images->store($request->file('image'), 'camps')]);
        }

        $this->syncCampCoaches->handle($camp, $request->coaches());

        $this->syncSeoMeta->forModel($camp, $request->validated('seo'), $request->file('seo_share_image'));

        $this->activity->log('camp.created', "Camp {$camp->name} created".($camp->is_published ? ' and published' : ' as a draft'), $camp, [
            'published' => $camp->is_published,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Camp created.')]);

        return to_route('admin.camps.edit', $camp);
    }

    public function edit(Camp $camp): Response
    {
        $camp->load('seoMeta')->loadSchedule();

        return Inertia::render('admin/camps/Edit', [
            'camp' => [
                ...$camp->only(['id', 'name', 'slug', 'description', 'location', 'address', 'age_range', 'starts_at', 'ends_at', 'price', 'capacity', 'registration_opens_at', 'registration_closes_at', 'image_url', 'image_thumbnail_url', 'youtube_url', 'is_published']),
                'bundle_price_dollars' => self::dollars($camp->bundle_price),
                'schedule' => self::scheduleForm($camp),
                'coaches' => $camp->coaches()->get()->map(fn (CampCoach $coach): array => $coach->only(['id', 'name', 'title', 'bio', 'photo_url']))->all(),
                'registration_state' => $camp->registrationState(),
                'public_url' => route('camps.show', $camp),
                'preview_url' => route('camps.preview', $camp),
                'preview_passcode' => $camp->preview_passcode,
            ],
            'seo' => $camp->seoMeta,
        ]);
    }

    /**
     * The schedule in the admin form's shape. Age groups are keyed so times can
     * point at groups that don't have an id yet.
     *
     * @return array<string, mixed>
     */
    private static function scheduleForm(Camp $camp): array
    {
        return [
            'age_groups' => $camp->ageGroups->map(fn (CampAgeGroup $group): array => [
                'key' => "group-{$group->id}",
                'id' => $group->id,
                'name' => $group->name,
                'capacity' => $group->capacity,
            ])->all(),
            'sessions' => $camp->sessions->map(fn (CampSession $session): array => [
                'id' => $session->id,
                'name' => $session->name,
                'description' => $session->description,
                'price' => self::dollars($session->price),
                'day_price' => self::dollars($session->day_price),
                'days' => $session->days->map(fn (CampSessionDay $day): array => [
                    'id' => $day->id,
                    'date' => $day->date?->toDateString(),
                    'label' => $day->label,
                    'times' => $day->times->map(fn (CampDayTime $time): array => [
                        'age_group_key' => $time->camp_age_group_id ? "group-{$time->camp_age_group_id}" : null,
                        'starts_at' => $time->starts_at ? substr($time->starts_at, 0, 5) : null,
                        'ends_at' => $time->ends_at ? substr($time->ends_at, 0, 5) : null,
                    ])->all(),
                ])->all(),
            ])->all(),
        ];
    }

    private static function dollars(?int $cents): ?string
    {
        return $cents === null ? null : number_format($cents / 100, 2, '.', '');
    }

    public function update(UpdateCampRequest $request, Camp $camp): RedirectResponse
    {
        $attributes = $request->campAttributes();

        if ($request->boolean('remove_image') && $camp->image_path) {
            $this->images->delete($camp->image_path);
            $attributes['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            $attributes['image_path'] = $this->images->replace($request->file('image'), 'camps', $camp->image_path);
        }

        $wasPublished = $camp->is_published;
        $changed = [];

        DB::transaction(function () use ($camp, $attributes, $request, &$changed): void {
            $camp->update($attributes);
            /** Field names only: values can be long text, and the passcode must not end up in the log. */
            $changed = array_values(array_diff(array_keys($camp->getChanges()), ['updated_at', 'preview_passcode']));
            if ($camp->wasChanged('preview_passcode')) {
                $changed[] = 'preview passcode';
            }
            $this->syncCampSchedule->handle($camp, $request->schedule());
        });

        $this->syncCampCoaches->handle($camp, $request->coaches());

        $this->syncSeoMeta->forModel($camp, $request->validated('seo'), $request->file('seo_share_image'));

        $this->activity->log('camp.updated', match (true) {
            ! $wasPublished && $camp->is_published => "Camp {$camp->name} published",
            $wasPublished && ! $camp->is_published => "Camp {$camp->name} moved to drafts",
            default => "Camp {$camp->name} updated",
        }, $camp, ['changed_fields' => $changed]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Camp updated.')]);

        return to_route('admin.camps.edit', $camp);
    }

    public function destroy(Camp $camp): RedirectResponse
    {
        $this->images->delete($camp->image_path);

        if ($camp->seoMeta?->share_image_path) {
            $this->images->deleteShareImage($camp->seoMeta->share_image_path);
        }

        $this->syncCampCoaches->delete($camp->coaches()->get());

        $this->activity->log('camp.deleted', "Camp {$camp->name} deleted", $camp, [
            'registrations_deleted' => $camp->registrations()->count(),
        ]);

        DB::transaction(function () use ($camp): void {
            /** Registrations go first: their booked days block deleting the camp's schedule. */
            $camp->registrations()->delete();
            $camp->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Camp deleted.')]);

        return to_route('admin.camps.index');
    }
}
