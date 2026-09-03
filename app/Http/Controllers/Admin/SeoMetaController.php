<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Seo\SyncSeoMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSeoMetaRequest;
use App\Models\SeoMeta;
use App\Services\ImageUploader;
use App\Services\SiteSettings;
use App\Support\Seo\StaticPages;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SeoMetaController extends Controller
{
    public function __construct(private SiteSettings $settings, private SyncSeoMeta $syncSeoMeta) {}

    public function index(): Response
    {
        $metas = SeoMeta::query()->whereIn('route_key', StaticPages::keys())->get()->keyBy('route_key');

        $pages = collect(StaticPages::all())->map(fn (array $page, string $key) => [
            'key' => $key,
            'label' => $page['label'],
            'description' => $page['description'],
            'url' => route($page['route']),
            'meta' => $metas->get($key),
        ])->values();

        return Inertia::render('admin/seo/Index', [
            'pages' => $pages,
        ]);
    }

    public function edit(string $routeKey): Response
    {
        abort_unless(StaticPages::has($routeKey), 404);

        $page = StaticPages::all()[$routeKey];

        return Inertia::render('admin/seo/Edit', [
            'routeKey' => $routeKey,
            'page' => [
                'label' => $page['label'],
                'url' => route($page['route']),
            ],
            'seo' => SeoMeta::query()->where('route_key', $routeKey)->first(),
            'fallback' => [
                'title' => $page['label'],
                'description' => $this->settings->get('seo_default_description'),
                'image_url' => ImageUploader::url($this->settings->get('seo_default_share_image')),
                'url' => route($page['route']),
            ],
            'siteName' => $this->settings->get('seo_site_name'),
        ]);
    }

    public function update(UpdateSeoMetaRequest $request, string $routeKey): RedirectResponse
    {
        abort_unless(StaticPages::has($routeKey), 404);

        $this->syncSeoMeta->forRoute($routeKey, $request->validated('seo'), $request->file('seo_share_image'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('SEO settings saved.')]);

        return to_route('admin.seo.edit', $routeKey);
    }
}
