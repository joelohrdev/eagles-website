<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderNavigationRequest;
use App\Http\Requests\Admin\StoreNavigationItemRequest;
use App\Http\Requests\Admin\UpdateNavigationItemRequest;
use App\Http\Requests\Admin\UpdateNavigationSettingsRequest;
use App\Models\NavigationItem;
use App\Services\Navigation;
use App\Services\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class NavigationController extends Controller
{
    public function __construct(private Navigation $navigation, private SiteSettings $settings) {}

    public function index(): Response
    {
        foreach (NavigationItem::LOCATIONS as $location) {
            $this->navigation->seedDefaults($location);
        }

        $items = NavigationItem::query()->ordered()->get()->groupBy('location');

        return Inertia::render('admin/navigation/Index', [
            'menus' => collect(NavigationItem::LOCATIONS)->mapWithKeys(fn (string $location) => [
                $location => ($items[$location] ?? collect())->map(fn (NavigationItem $item) => [
                    'id' => $item->id,
                    'label' => $item->label,
                    'link_type' => $item->isPageLink() ? 'page' : 'custom',
                    'route_name' => $item->route_name,
                    'url' => $item->url,
                    'href' => $item->href(),
                    'opens_in_new_tab' => $item->opens_in_new_tab,
                    'is_visible' => $item->is_visible,
                ])->values(),
            ]),
            'pages' => $this->navigation->pageOptions(),
            'settings' => $this->settings->group('navigation'),
        ]);
    }

    public function store(StoreNavigationItemRequest $request): RedirectResponse
    {
        $location = $request->validated('location');
        $nextOrder = (int) NavigationItem::query()->location($location)->max('sort_order') + 1;

        NavigationItem::query()->create([
            'location' => $location,
            'sort_order' => $nextOrder,
            ...$request->itemAttributes(),
        ]);

        $this->navigation->flush();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Link added.')]);

        return back();
    }

    public function update(UpdateNavigationItemRequest $request, NavigationItem $item): RedirectResponse
    {
        $item->update($request->itemAttributes());

        $this->navigation->flush();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Link updated.')]);

        return back();
    }

    public function destroy(NavigationItem $item): RedirectResponse
    {
        $item->delete();

        $this->navigation->flush();

        Inertia::flash('toast', ['type' => 'info', 'message' => __('Link removed.')]);

        return back();
    }

    public function reorder(ReorderNavigationRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('order') as $position => $id) {
                NavigationItem::query()->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        $this->navigation->flush();

        return back();
    }

    public function updateSettings(UpdateNavigationSettingsRequest $request): RedirectResponse
    {
        $this->settings->setMany($request->settingValues());
        $this->navigation->flush();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Navigation settings saved.')]);

        return back();
    }
}
