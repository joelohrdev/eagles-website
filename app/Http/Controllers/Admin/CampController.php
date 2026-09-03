<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Seo\SyncSeoMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCampRequest;
use App\Http\Requests\Admin\UpdateCampRequest;
use App\Models\Camp;
use App\Services\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CampController extends Controller
{
    public function __construct(
        private ImageUploader $images,
        private SyncSeoMeta $syncSeoMeta,
    ) {}

    public function index(): Response
    {
        $camps = Camp::query()
            ->withCount([
                'registrations as paid_registrations_count' => fn ($query) => $query->paid(),
                'registrations as active_registrations_count' => fn ($query) => $query->countsAgainstCapacity(),
            ])
            ->orderByDesc('starts_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Camp $camp) => [
                ...$camp->only(['id', 'name', 'slug', 'location', 'starts_at', 'ends_at', 'price', 'capacity', 'is_published', 'image_thumbnail_url', 'paid_registrations_count', 'active_registrations_count']),
                'registration_state' => $camp->registrationState(),
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
        $camp = Camp::query()->create([
            ...$request->campAttributes(),
            'image_path' => $request->hasFile('image') ? $this->images->store($request->file('image'), 'camps') : null,
        ]);

        $this->syncSeoMeta->forModel($camp, $request->validated('seo'), $request->file('seo_share_image'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Camp created.')]);

        return to_route('admin.camps.edit', $camp);
    }

    public function edit(Camp $camp): Response
    {
        $camp->load('seoMeta');

        return Inertia::render('admin/camps/Edit', [
            'camp' => [
                ...$camp->toArray(),
                'price_dollars' => number_format($camp->price / 100, 2, '.', ''),
                'registration_state' => $camp->registrationState(),
                'public_url' => route('camps.show', $camp),
            ],
            'seo' => $camp->seoMeta,
        ]);
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

        $camp->update($attributes);

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

        $camp->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Camp deleted.')]);

        return to_route('admin.camps.index');
    }
}
