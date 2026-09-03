<?php

namespace App\Support\Seo;

/**
 * Registry of static public pages whose SEO & Sharing meta is editable by route key.
 */
final class StaticPages
{
    /**
     * @return array<string, array{label: string, route: string, description: string}>
     */
    public static function all(): array
    {
        return [
            'home' => ['label' => 'Home', 'route' => 'home', 'description' => 'Landing page with hero, offerings, and about section.'],
            'teams.index' => ['label' => 'Teams', 'route' => 'teams.index', 'description' => 'All active teams grouped by division.'],
            'facility' => ['label' => 'Facility', 'route' => 'facility', 'description' => 'Training facility description and photo gallery.'],
            'coaches.index' => ['label' => 'Coaching Staff', 'route' => 'coaches.index', 'description' => 'Coach profiles.'],
            'camps.index' => ['label' => 'Camps', 'route' => 'camps.index', 'description' => 'Upcoming camps and clinics.'],
            'tryouts.index' => ['label' => 'Tryouts', 'route' => 'tryouts.index', 'description' => 'Upcoming tryouts and registration.'],
            'merch.index' => ['label' => 'Merch', 'route' => 'merch.index', 'description' => 'Team store.'],
            'contact' => ['label' => 'Contact', 'route' => 'contact', 'description' => 'Contact form and organization details.'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::all());
    }
}
