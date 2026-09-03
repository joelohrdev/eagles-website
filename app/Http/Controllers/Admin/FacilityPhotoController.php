<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderFacilityPhotosRequest;
use App\Http\Requests\Admin\StoreFacilityPhotosRequest;
use App\Http\Requests\Admin\UpdateFacilityPhotoRequest;
use App\Models\FacilityPhoto;
use App\Services\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FacilityPhotoController extends Controller
{
    public function __construct(private ImageUploader $images) {}

    public function index(): Response
    {
        return Inertia::render('admin/facility-photos/Index', [
            'photos' => FacilityPhoto::query()->ordered()->get(),
        ]);
    }

    public function store(StoreFacilityPhotosRequest $request): RedirectResponse
    {
        $nextSort = ((int) FacilityPhoto::query()->max('sort_order')) + 1;

        foreach ($request->file('photos', []) as $file) {
            FacilityPhoto::query()->create([
                'image_path' => $this->images->store($file, 'facility'),
                'sort_order' => $nextSort++,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photos uploaded.')]);

        return to_route('admin.facility-photos.index');
    }

    public function update(UpdateFacilityPhotoRequest $request, FacilityPhoto $facilityPhoto): RedirectResponse
    {
        $facilityPhoto->update(['caption' => $request->validated('caption')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Caption saved.')]);

        return to_route('admin.facility-photos.index');
    }

    public function destroy(FacilityPhoto $facilityPhoto): RedirectResponse
    {
        $this->images->delete($facilityPhoto->image_path);
        $facilityPhoto->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photo deleted.')]);

        return to_route('admin.facility-photos.index');
    }

    public function reorder(ReorderFacilityPhotosRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('order') as $position => $id) {
                FacilityPhoto::query()->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return to_route('admin.facility-photos.index');
    }
}
