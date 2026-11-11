<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderSponsorsRequest;
use App\Http\Requests\Admin\StoreSponsorRequest;
use App\Http\Requests\Admin\UpdateSponsorRequest;
use App\Models\Sponsor;
use App\Services\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SponsorController extends Controller
{
    public function __construct(private ImageUploader $images) {}

    public function index(): Response
    {
        return Inertia::render('admin/sponsors/Index', [
            'sponsors' => Sponsor::query()
                ->ordered()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/sponsors/Create');
    }

    public function store(StoreSponsorRequest $request): RedirectResponse
    {
        $sponsor = new Sponsor($this->sponsorAttributes($request->validated()));
        $sponsor->sort_order = Sponsor::nextSortOrder();
        $sponsor->logo_path = $this->images->storeLogo($request->file('logo'), 'sponsors');
        $sponsor->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sponsor added.')]);

        return to_route('admin.sponsors.index');
    }

    public function edit(Sponsor $sponsor): Response
    {
        return Inertia::render('admin/sponsors/Edit', [
            'sponsor' => $sponsor,
        ]);
    }

    public function update(UpdateSponsorRequest $request, Sponsor $sponsor): RedirectResponse
    {
        $sponsor->fill($this->sponsorAttributes($request->validated()));

        if ($request->hasFile('logo')) {
            $this->images->delete($sponsor->logo_path);
            $sponsor->logo_path = $this->images->storeLogo($request->file('logo'), 'sponsors');
        }

        $sponsor->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sponsor updated.')]);

        return to_route('admin.sponsors.edit', $sponsor);
    }

    public function destroy(Sponsor $sponsor): RedirectResponse
    {
        $this->images->delete($sponsor->logo_path);

        $sponsor->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sponsor deleted.')]);

        return to_route('admin.sponsors.index');
    }

    public function reorder(ReorderSponsorsRequest $request): RedirectResponse
    {
        Sponsor::applyManualOrder($request->order());

        return back();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{name: string, website_url: string|null, is_active: bool}
     */
    private function sponsorAttributes(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'website_url' => $validated['website_url'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }
}
