<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Typed access to key/value site settings with defaults and caching.
 */
class SiteSettings
{
    public const string CACHE_KEY = 'site_settings.all';

    /**
     * Default values for every known setting.
     *
     * @var array<string, mixed>
     */
    public const array DEFAULTS = [
        // Organization / contact
        'org_name' => 'Eagles Baseball Travel',
        'phone' => '630-767-9208',
        'email' => 'eaglesbaseballtravel@gmail.com',
        'address_line1' => null,
        'address_city' => null,
        'address_state' => 'IL',
        'address_postal_code' => null,
        'geo_latitude' => null,
        'geo_longitude' => null,
        'service_area' => null,
        'founding_year' => null,
        'social_facebook' => null,
        'social_instagram' => null,
        'social_twitter' => null,
        'social_youtube' => null,
        'social_tiktok' => null,

        // Home page
        'home_hero_headline' => 'Elite Travel Baseball. Built on Fundamentals.',
        'home_hero_subheadline' => 'Competitive teams, professional coaching, and a first-class facility for players who want to take their game to the next level.',
        'home_hero_cta_label' => 'View Tryouts',
        'home_hero_cta_url' => '/tryouts',
        'home_hero_secondary_cta_label' => 'Contact Us',
        'home_hero_secondary_cta_url' => '/contact',
        'home_hero_image' => null,
        'home_intro' => 'Eagles Baseball Travel is a youth travel baseball organization offering competitive teams, skills camps, and year-round training for players ages 9U–17U.',
        'home_offerings' => [
            ['title' => 'Competitive Teams', 'description' => 'Age-group teams from 9U to 17U competing in top regional tournaments.'],
            ['title' => 'Professional Coaching', 'description' => 'Experienced coaches focused on player development on and off the field.'],
            ['title' => 'Camps & Clinics', 'description' => 'Seasonal skills camps for hitting, pitching, fielding, and catching.'],
            ['title' => 'Training Facility', 'description' => 'Indoor cages, mounds, and turf for year-round development.'],
        ],
        'home_about_heading' => 'About the Eagles',
        'home_about_body' => 'Placeholder: tell families who you are, what you stand for, and why players choose the Eagles. This copy is editable in the admin under Site Settings.',
        'home_about_image' => null,
        'home_youtube_url' => null,

        // Facility page
        'facility_heading' => 'Our Facility',
        'facility_description' => 'Placeholder: describe the training facility — cages, mounds, turf, hours, and address. Editable in Site Settings.',
        'facility_address' => null,
        'facility_youtube_url' => null,

        // Contact page
        'contact_intro' => 'Have a question about teams, tryouts, or camps? Send us a message and we will get back to you soon.',

        // Page visibility
        'page_teams_enabled' => true,
        'page_facility_enabled' => true,
        'page_coaches_enabled' => true,
        'page_camps_enabled' => true,
        'page_merch_enabled' => true,
        'page_contact_enabled' => true,

        // Navigation & footer
        'nav_cta_label' => 'Tryouts',
        'nav_cta_url' => '/tryouts',
        'nav_show_cta' => true,
        'nav_show_cart' => true,
        'footer_tagline' => 'Youth travel baseball teams, tryouts, camps, and year-round training.',
        'footer_links_heading' => 'Explore',
        'footer_contact_heading' => 'Contact',
        'footer_show_contact' => true,
        'footer_show_socials' => true,
        'footer_show_address' => true,
        'footer_copyright' => '© {year} {org}. All rights reserved.',

        // SEO defaults
        'seo_site_name' => 'Eagles Baseball Travel',
        'seo_title_template' => '%s | Eagles Baseball Travel',
        'seo_default_description' => 'Eagles Baseball Travel — youth travel baseball teams, tryouts, camps, and training in Illinois.',
        'seo_default_share_image' => null,
        'seo_google_site_verification' => null,
        'seo_bing_site_verification' => null,
        'seo_facebook_app_id' => null,
        'seo_twitter_handle' => null,
        'seo_faq' => [],
    ];

    /**
     * Setting keys grouped by the admin form section that edits them.
     *
     * @var array<string, list<string>>
     */
    public const array GROUPS = [
        'organization' => ['org_name', 'phone', 'email', 'address_line1', 'address_city', 'address_state', 'address_postal_code', 'geo_latitude', 'geo_longitude', 'service_area', 'founding_year', 'social_facebook', 'social_instagram', 'social_twitter', 'social_youtube', 'social_tiktok'],
        'home' => ['home_hero_headline', 'home_hero_subheadline', 'home_hero_cta_label', 'home_hero_cta_url', 'home_hero_secondary_cta_label', 'home_hero_secondary_cta_url', 'home_hero_image', 'home_intro', 'home_offerings', 'home_about_heading', 'home_about_body', 'home_about_image', 'home_youtube_url'],
        'facility' => ['facility_heading', 'facility_description', 'facility_address', 'facility_youtube_url'],
        'contact' => ['contact_intro'],
        'pages' => ['page_teams_enabled', 'page_facility_enabled', 'page_coaches_enabled', 'page_camps_enabled', 'page_merch_enabled', 'page_contact_enabled'],
        'navigation' => ['nav_cta_label', 'nav_cta_url', 'nav_show_cta', 'nav_show_cart', 'footer_tagline', 'footer_links_heading', 'footer_contact_heading', 'footer_show_contact', 'footer_show_socials', 'footer_show_address', 'footer_copyright'],
        'seo' => ['seo_site_name', 'seo_title_template', 'seo_default_description', 'seo_default_share_image', 'seo_google_site_verification', 'seo_bing_site_verification', 'seo_facebook_app_id', 'seo_twitter_handle', 'seo_faq'],
    ];

    /**
     * Keys that hold uploaded image paths.
     *
     * @var list<string>
     */
    public const array IMAGE_KEYS = ['home_hero_image', 'home_about_image', 'seo_default_share_image'];

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => SiteSetting::query()->pluck('value', 'key')->all());

        return array_merge(self::DEFAULTS, array_filter($stored, fn ($value) => $value !== null));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    /**
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        return $this->only(self::GROUPS[$group] ?? []);
    }

    /**
     * FAQ entries that have both a question and an answer, ready for JSON-LD.
     *
     * @return list<array{question: string, answer: string}>
     */
    public function faqs(): array
    {
        $faqs = [];

        foreach ((array) $this->get('seo_faq') as $faq) {
            if (! is_array($faq)) {
                continue;
            }

            $question = $faq['question'] ?? null;
            $answer = $faq['answer'] ?? null;

            if (filled($question) && filled($answer)) {
                $faqs[] = ['question' => (string) $question, 'answer' => (string) $answer];
            }
        }

        return $faqs;
    }

    public function set(string $key, mixed $value): void
    {
        SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        $this->flush();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
