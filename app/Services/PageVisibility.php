<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Which public pages are switched on in Site Settings.
 *
 * Pages that are not finished yet can be turned off by an admin: the page
 * itself 404s, every menu item or CTA pointing at it disappears, and it drops
 * out of the sitemap and llms.txt. Merch owns the cart and checkout, so
 * turning it off hides the cart icon too.
 *
 * Tryouts are deliberately absent. Their links already hide themselves while
 * no tryout is open for registration (see TryoutAvailability) and must never
 * gain a manual toggle.
 */
class PageVisibility
{
    /**
     * Toggleable pages, keyed by the slug used in `page_{key}_enabled` and the
     * `page:{key}` middleware. `routes` lists the route names whose paths (and
     * anything nested under them) belong to the page.
     *
     * @var array<string, array{label: string, description: string, routes: list<string>}>
     */
    public const array PAGES = [
        'teams' => [
            'label' => 'Teams',
            'description' => 'The teams roster page.',
            'routes' => ['teams.index'],
        ],
        'facility' => [
            'label' => 'Facility',
            'description' => 'The training facility page and its photo gallery.',
            'routes' => ['facility'],
        ],
        'coaches' => [
            'label' => 'Coaching Staff',
            'description' => 'Coach profiles.',
            'routes' => ['coaches.index'],
        ],
        'camps' => [
            'label' => 'Camps',
            'description' => 'Camp listings, camp detail pages, and camp registration.',
            'routes' => ['camps.index'],
        ],
        'merch' => [
            'label' => 'Merch',
            'description' => 'The team store, the cart icon, and checkout.',
            'routes' => ['merch.index', 'cart.index', 'checkout.create'],
        ],
        'contact' => [
            'label' => 'Contact',
            'description' => 'The contact page and its form.',
            'routes' => ['contact'],
        ],
    ];

    public function __construct(private SiteSettings $settings) {}

    public static function settingKey(string $page): string
    {
        return "page_{$page}_enabled";
    }

    /**
     * Is this page switched on? Unknown pages are always on.
     */
    public function isEnabled(string $page): bool
    {
        if (! array_key_exists($page, self::PAGES)) {
            return true;
        }

        return (bool) $this->settings->get(self::settingKey($page));
    }

    /**
     * Every page keyed by whether it is switched on.
     *
     * @return array<string, bool>
     */
    public function enabled(): array
    {
        return collect(self::PAGES)->map(fn (array $page, string $key) => $this->isEnabled($key))->all();
    }

    /**
     * The page a public route name belongs to, if any.
     */
    public function pageForRoute(string $routeName): ?string
    {
        foreach (self::PAGES as $key => $page) {
            foreach ($page['routes'] as $route) {
                if ($routeName === $route || Str::startsWith($routeName, Str::beforeLast($route, '.').'.')) {
                    return $key;
                }
            }
        }

        return null;
    }

    /**
     * The page an href points at, if any.
     */
    public function pageForLink(?string $href): ?string
    {
        if (blank($href)) {
            return null;
        }

        $path = '/'.trim((string) parse_url($href, PHP_URL_PATH), '/');

        foreach (self::PAGES as $key => $page) {
            foreach ($page['routes'] as $route) {
                $base = route($route, absolute: false);

                if ($path === $base || Str::startsWith($path, $base.'/')) {
                    return $key;
                }
            }
        }

        return null;
    }

    /**
     * Should a link to this href be hidden from the site?
     */
    public function hidesLink(?string $href): bool
    {
        $page = $this->pageForLink($href);

        return $page !== null && ! $this->isEnabled($page);
    }

    /**
     * Is the page behind this route name switched on?
     */
    public function allowsRoute(string $routeName): bool
    {
        $page = $this->pageForRoute($routeName);

        return $page === null || $this->isEnabled($page);
    }
}
