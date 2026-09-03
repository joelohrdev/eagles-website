<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\SitemapController;
use App\Http\Requests\Admin\UpdateSiteSettingsRequest;
use App\Services\ImageUploader;
use App\Services\PageVisibility;
use App\Services\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingController extends Controller
{
    /**
     * @var array<string, string>
     */
    public const array GROUP_LABELS = [
        'organization' => 'Organization',
        'home' => 'Home page',
        'facility' => 'Facility page',
        'contact' => 'Contact page',
        'pages' => 'Pages',
        'seo' => 'SEO defaults',
    ];

    public function __construct(private SiteSettings $settings, private ImageUploader $images) {}

    public function edit(string $group): Response
    {
        $settings = $this->settings->group($group);

        return Inertia::render('admin/settings/Edit', [
            'group' => $group,
            'groups' => collect(self::GROUP_LABELS)->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])->values(),
            'pages' => collect(PageVisibility::PAGES)->map(fn (array $page, string $key) => [
                'key' => $key,
                'label' => $page['label'],
                'description' => $page['description'],
                'setting' => PageVisibility::settingKey($key),
            ])->values(),
            'settings' => $settings,
            'imageUrls' => collect(SiteSettings::IMAGE_KEYS)
                ->filter(fn (string $key) => array_key_exists($key, $settings))
                ->mapWithKeys(fn (string $key) => [$key => ImageUploader::url($settings[$key] ?? null)]),
        ]);
    }

    public function update(UpdateSiteSettingsRequest $request, string $group): RedirectResponse
    {
        $values = $request->settingValues();

        foreach (SiteSettings::IMAGE_KEYS as $key) {
            if (! in_array($key, SiteSettings::GROUPS[$group], true)) {
                continue;
            }

            $existing = $this->settings->get($key);
            $isShare = $key === 'seo_default_share_image';

            if ($request->boolean("remove_{$key}") && $existing) {
                $isShare ? $this->images->deleteShareImage($existing) : $this->images->delete($existing);
                $values[$key] = null;
                $existing = null;
            }

            if ($file = $request->file($key)) {
                $values[$key] = $isShare
                    ? $this->images->replaceShareImage($file, $existing, 'settings')
                    : $this->images->replace($file, 'settings', $existing);
            }
        }

        $this->settings->setMany($values);

        /** Switched-off pages drop out of the sitemap, which is cached for an hour. */
        if ($group === 'pages') {
            Cache::forget(SitemapController::CACHE_KEY);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Settings saved.')]);

        return to_route('admin.settings.edit', $group);
    }
}
