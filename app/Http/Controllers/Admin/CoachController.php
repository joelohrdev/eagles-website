<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Seo\SyncSeoMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderCoachesRequest;
use App\Http\Requests\Admin\StoreCoachRequest;
use App\Http\Requests\Admin\UpdateCoachRequest;
use App\Models\Coach;
use App\Services\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CoachController extends Controller
{
    public function __construct(
        private ImageUploader $images,
        private SyncSeoMeta $syncSeoMeta,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/coaches/Index', [
            'coaches' => Coach::query()
                ->withCount('teams')
                ->ordered()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/coaches/Create');
    }

    public function store(StoreCoachRequest $request): RedirectResponse
    {
        $coach = new Coach($this->coachAttributes($request->validated()));
        $coach->sort_order = Coach::nextSortOrder();

        if ($request->hasFile('photo')) {
            $coach->photo_path = $this->images->store($request->file('photo'), 'coaches');
        }

        $coach->save();

        $this->syncSeoMeta->forModel($coach, $request->validated('seo'), $request->file('seo_share_image'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Coach created.')]);

        return to_route('admin.coaches.index');
    }

    public function edit(Coach $coach): Response
    {
        $coach->load('seoMeta');

        return Inertia::render('admin/coaches/Edit', [
            'coach' => $coach,
            'seo' => $coach->seoMeta,
        ]);
    }

    public function update(UpdateCoachRequest $request, Coach $coach): RedirectResponse
    {
        $coach->fill($this->coachAttributes($request->validated()));

        if ($request->boolean('remove_photo') && $coach->photo_path) {
            $this->images->delete($coach->photo_path);
            $coach->photo_path = null;
        }

        if ($request->hasFile('photo')) {
            $coach->photo_path = $this->images->replace($request->file('photo'), 'coaches', $coach->photo_path);
        }

        $coach->save();

        $this->syncSeoMeta->forModel($coach, $request->validated('seo'), $request->file('seo_share_image'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Coach updated.')]);

        return to_route('admin.coaches.edit', $coach);
    }

    public function destroy(Coach $coach): RedirectResponse
    {
        $this->images->delete($coach->photo_path);

        if ($coach->seoMeta) {
            $this->images->deleteShareImage($coach->seoMeta->share_image_path);
        }

        $coach->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Coach deleted.')]);

        return to_route('admin.coaches.index');
    }

    public function reorder(ReorderCoachesRequest $request): RedirectResponse
    {
        Coach::applyManualOrder($request->order());

        return back();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function coachAttributes(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }
}
