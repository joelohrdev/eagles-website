<?php

namespace App\Services;

use App\Models\NavigationItem;
use App\Support\Seo\StaticPages;
use Illuminate\Support\Facades\Cache;

/**
 * Builds the header/footer menus shared with every Inertia page.
 * Falls back to sensible defaults when a menu has never been configured.
 */
class Navigation
{
    public const string CACHE_KEY = 'navigation.menus';

    /**
     * Default menu items per location, used until an admin creates their own.
     *
     * @var array<string, list<array{label: string, route_name: string}>>
     */
    public const array DEFAULTS = [
        NavigationItem::HEADER => [
            ['label' => 'Teams', 'route_name' => 'teams.index'],
            ['label' => 'Facility', 'route_name' => 'facility'],
            ['label' => 'Camps', 'route_name' => 'camps.index'],
            ['label' => 'Coaches', 'route_name' => 'coaches.index'],
            ['label' => 'Tryouts', 'route_name' => 'tryouts.index'],
            ['label' => 'Merch', 'route_name' => 'merch.index'],
            ['label' => 'Contact', 'route_name' => 'contact'],
        ],
        NavigationItem::FOOTER => [
            ['label' => 'Teams', 'route_name' => 'teams.index'],
            ['label' => 'Facility', 'route_name' => 'facility'],
            ['label' => 'Camps', 'route_name' => 'camps.index'],
            ['label' => 'Coaches', 'route_name' => 'coaches.index'],
            ['label' => 'Tryouts', 'route_name' => 'tryouts.index'],
            ['label' => 'Merch', 'route_name' => 'merch.index'],
            ['label' => 'Contact', 'route_name' => 'contact'],
        ],
        NavigationItem::FOOTER_BOTTOM => [
            ['label' => 'Contact us', 'route_name' => 'contact'],
        ],
    ];

    public function __construct(
        private SiteSettings $settings,
        private TryoutAvailability $tryouts,
        private PageVisibility $pages,
    ) {}

    /**
     * Menus + footer/header settings for the frontend.
     *
     * @return array<string, mixed>
     */
    public function forFrontend(): array
    {
        $menus = Cache::rememberForever(self::CACHE_KEY, fn () => collect(NavigationItem::LOCATIONS)
            ->mapWithKeys(fn (string $location) => [$location => $this->menu($location)])
            ->all());

        /**
         * Applied after the cache: tryout availability changes with the clock and
         * page toggles change whenever an admin saves Site Settings, so neither
         * may be baked into the cached menus.
         */
        $menus = collect($menus)
            ->map(fn (array $items) => collect($items)->reject(fn (array $item) => $this->hidesLink($item['href']))->values()->all())
            ->all();

        $settings = $this->settings->group('navigation');
        $settings['footer_copyright'] = strtr((string) $settings['footer_copyright'], [
            '{year}' => now()->year,
            '{org}' => $this->settings->get('org_name'),
        ]);

        if ($this->hidesLink($settings['nav_cta_url'] ?? null)) {
            $settings['nav_show_cta'] = false;
        }

        /** The cart belongs to the store: no Merch page, no cart icon. */
        if (! $this->pages->isEnabled('merch')) {
            $settings['nav_show_cart'] = false;
        }

        return ['menus' => $menus, 'settings' => $settings];
    }

    /**
     * Should a link to this href stay off the site — either because it points at
     * a page switched off in Site Settings, or because no tryout is open?
     */
    private function hidesLink(?string $href): bool
    {
        return $this->tryouts->hidesLink($href) || $this->pages->hidesLink($href);
    }

    /**
     * Visible items for a location, resolved to hrefs. Uses defaults when the location
     * has never been configured (no rows at all — hidden rows count as configured).
     *
     * @return list<array{id: int|null, label: string, href: string, external: bool, new_tab: bool}>
     */
    public function menu(string $location): array
    {
        $items = NavigationItem::query()->location($location)->ordered()->get();

        if ($items->isEmpty()) {
            return array_values(collect(self::DEFAULTS[$location] ?? [])->map(fn (array $item) => [
                'id' => null,
                'label' => $item['label'],
                'href' => route($item['route_name'], absolute: false),
                'external' => false,
                'new_tab' => false,
            ])->all());
        }

        return array_values($items->filter->is_visible->map(fn (NavigationItem $item) => [
            'id' => $item->id,
            'label' => $item->label,
            'href' => $item->href(),
            'external' => ! $item->isPageLink() && ! str_starts_with((string) $item->url, '/'),
            'new_tab' => $item->opens_in_new_tab,
        ])->all());
    }

    /**
     * Copy the defaults for a location into the database (used the first time an admin edits a menu).
     */
    public function seedDefaults(string $location): void
    {
        if (NavigationItem::query()->location($location)->exists()) {
            return;
        }

        foreach (self::DEFAULTS[$location] ?? [] as $index => $item) {
            NavigationItem::query()->create([
                'location' => $location,
                'label' => $item['label'],
                'route_name' => $item['route_name'],
                'sort_order' => $index,
            ]);
        }

        $this->flush();
    }

    /**
     * Pages an item can link to.
     *
     * @return list<array{value: string, label: string}>
     */
    public function pageOptions(): array
    {
        $pages = collect(StaticPages::all())
            ->map(fn (array $page, string $key) => ['value' => $key, 'label' => $page['label']])
            ->values();

        return array_values($pages->push(['value' => 'cart.index', 'label' => 'Cart'])->all());
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
