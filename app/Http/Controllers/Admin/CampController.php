<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Camps\QuoteCampSelection;
use App\Actions\Camps\SyncCampCoaches;
use App\Actions\Camps\SyncCampSchedule;
use App\Actions\Seo\SyncSeoMeta;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\CampController as SiteCampController;
use App\Http\Requests\Admin\StoreCampRequest;
use App\Http\Requests\Admin\UpdateCampRequest;
use App\Http\Requests\Site\QuoteCampRegistrationRequest;
use App\Models\Camp;
use App\Models\CampAgeGroup;
use App\Models\CampCoach;
use App\Models\CampDayTime;
use App\Models\CampSession;
use App\Models\CampSessionDay;
use App\Services\ImageUploader;
use App\Services\SeoResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Camp created.')]);

        return to_route('admin.camps.edit', $camp);
    }

    /**
     * The public camp page as it will look once published, for drafts too.
     * Staff-only (admin routes). Its register button leads to the preview of
     * the registration form rather than the real one.
     */
    public function preview(Camp $camp, SeoResolver $seo): Response
    {
        return Inertia::render('site/Camps/Show', [
            ...$this->previewProps($camp, $seo, 'Preview: '),
            'registered' => false,
        ]);
    }

    /**
     * The registration form for a camp, drafts too, so staff can try the
     * sessions, days, age groups and coupons and watch the total. It prices
     * selections through previewQuote() and can't be submitted.
     */
    public function previewRegister(Camp $camp, SeoResolver $seo): Response
    {
        return Inertia::render('site/Camps/Register', [
            ...$this->previewProps($camp, $seo, 'Preview registration: '),
            'quoteUrl' => route('admin.camps.preview.quote', $camp),
        ]);
    }

    /**
     * Price a selection on the preview registration form. Same rules as the
     * public quote, but works for drafts and never saves anything.
     */
    public function previewQuote(Request $request, Camp $camp, QuoteCampSelection $quoteCampSelection): JsonResponse
    {
        $validated = $request->validate(
            QuoteCampRegistrationRequest::selectionRules(),
            attributes: QuoteCampRegistrationRequest::selectionAttributes(),
        );

        return response()->json($quoteCampSelection->handle(
            $camp,
            array_map('intval', $validated['days']),
            $validated['camp_age_group_id'] ?? null,
            $validated['coupon_code'] ?? null,
        )->toArray());
    }

    /**
     * Props shared by the preview pages: the camp presented as if live (so the
     * registration state matches what families will see; never saved), the
     * preview banner's links, and noindex SEO.
     *
     * @return array<string, mixed>
     */
    private function previewProps(Camp $camp, SeoResolver $seo, string $titlePrefix): array
    {
        $camp->load('seoMeta');
        $isDraft = ! $camp->is_published;
        $camp->is_published = true;

        return [
            'camp' => SiteCampController::presentDetail($camp),
            'preview' => [
                'is_draft' => $isDraft,
                'edit_url' => route('admin.camps.edit', $camp),
                'camp_url' => route('admin.camps.preview', $camp),
                'register_url' => route('admin.camps.preview.register', $camp),
            ],
            'seo' => $seo->forModel($camp, [
                'title' => $titlePrefix.$camp->name,
                'description' => SiteCampController::autoDescription($camp),
                'robots' => 'noindex,nofollow',
            ])->toArray(),
        ];
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
                'preview_url' => route('admin.camps.preview', $camp),
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

        DB::transaction(function () use ($camp, $attributes, $request): void {
            $camp->update($attributes);
            $this->syncCampSchedule->handle($camp, $request->schedule());
        });

        $this->syncCampCoaches->handle($camp, $request->coaches());

        $this->syncSeoMeta->forModel($camp, $request->validated('seo'), $request->file('seo_share_image'));

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

        DB::transaction(function () use ($camp): void {
            /** Registrations go first: their booked days block deleting the camp's schedule. */
            $camp->registrations()->delete();
            $camp->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Camp deleted.')]);

        return to_route('admin.camps.index');
    }
}
