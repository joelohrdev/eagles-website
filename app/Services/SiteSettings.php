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
        'home_hero_headline' => 'Building More Than Ballplayers.',
        'home_hero_subheadline' => 'Eagles Baseball is a youth travel baseball organization founded in 2023 by Coach John Herrera. We develop athletes physically, mentally, socially, and interpersonally — on the field and beyond it.',
        'home_hero_cta_label' => 'View Tryouts',
        'home_hero_cta_url' => '/tryouts',
        'home_hero_secondary_cta_label' => 'Contact Us',
        'home_hero_secondary_cta_url' => '/contact',
        'home_hero_image' => null,
        'home_intro' => 'Eagles Baseball Travel is a year-round youth travel baseball program for players from 10U through high school, built on professional coaching, character, and development.',
        'home_mission_title' => 'Developing more than baseball players',
        'home_mission_body' => 'Our mission is to develop athletes physically, mentally, socially, and interpersonally. We prioritize each player\'s growth as an individual, a teammate, and a baseball player over wins, records, and accolades. Wins follow when the development is right.',
        'home_belief' => 'We are developing more than baseball players — we are developing young men who know how to lead, work hard, serve others, overcome challenges, and succeed both on and off the field.',
        'home_philosophy_title' => 'Our coaching philosophy',
        'home_philosophy_body' => 'Our coaches challenge every athlete on and off the field while creating a positive, fun, learning-focused environment. We hold high standards, teach leadership and teamwork, and expect discipline and character from every player who wears the Eagles uniform.',
        'home_values' => [
            ['title' => 'Discipline', 'description' => 'Show up prepared, on time, and ready to work — every practice, every game.'],
            ['title' => 'Leadership', 'description' => 'Set the example for teammates through effort, attitude, and how you treat others.'],
            ['title' => 'Selflessness', 'description' => 'Put the team first. Celebrate a teammate\'s success like it is your own.'],
            ['title' => 'Work Ethic', 'description' => 'Development is earned through repetition and effort, not talent alone.'],
            ['title' => 'Confidence', 'description' => 'Trust your preparation and compete without fear of failure.'],
            ['title' => 'Teamwork', 'description' => 'Nine players moving together will always beat nine individuals.'],
            ['title' => 'Character', 'description' => 'Do the right thing when no one is watching, on and off the field.'],
            ['title' => 'Accountability', 'description' => 'Own your mistakes, learn from them, and hold each other to the standard.'],
        ],
        'home_goals_title' => 'Our goals',
        'home_goals_body' => 'Every Eagles team works toward the same four goals each season.',
        'home_goals' => [
            ['title' => 'Building Relationships', 'description' => 'Coaches, players, and families who know, trust, and support one another — relationships that last well beyond a single season.'],
            ['title' => 'Player & Baseball Skill Development', 'description' => 'Measurable improvement in every player\'s fundamentals, athleticism, and baseball IQ from the first practice to the last game.'],
            ['title' => 'Team Cohesion & Sportsmanship', 'description' => 'Teams that play for each other, respect opponents and umpires, and represent the Eagles with class win or lose.'],
            ['title' => 'Positive Camaraderie', 'description' => 'A dugout and a program that players love being part of — fun, encouraging, and focused on growth.'],
        ],
        'home_development_title' => 'Our commitment to player development',
        'home_development_body' => 'Eagles Baseball runs on a professional coaching model. Every team, at every age level, is led by experienced coaches who follow a consistent development plan — we do not concentrate our best instruction on the top teams. A 10U player receives the same quality of coaching and the same commitment to development as a 14U player preparing for high school ball.',
        'home_development_tiers' => [
            ['title' => '10U – 11U', 'description' => 'Fundamentals first: throwing, fielding, hitting, and baserunning mechanics, baseball IQ, and the habits of work ethic, character, focus, and hard work.'],
            ['title' => '12U – 14U', 'description' => 'Increased competition, advanced baseball development, and elite athletic training that prepares players for the high school level.'],
            ['title' => 'High School', 'description' => 'A continued development path for Eagles players at the high school level — strength, skill, and the competitive reps needed to keep advancing.'],
        ],
        'home_year_round_title' => 'Year-round development',
        'home_year_round_body' => 'The Eagles are not a spring-and-summer organization. Development continues through the winter with structured indoor training so players return to the field ahead of where they left it.',
        'home_year_round_items' => [
            ['title' => 'Hitting & Pitching Instruction', 'description' => 'Small-group cage and mound work with Eagles coaches all winter long.'],
            ['title' => 'Position-Specific Defense', 'description' => 'Infield, outfield, and catching sessions focused on footwork, reads, and reps.'],
            ['title' => 'Strength, Speed & Agility', 'description' => 'Age-appropriate athletic training that builds strength, quickness, and durability.'],
            ['title' => 'Baseball IQ', 'description' => 'Situational baseball, game strategy, and the mental side of competing.'],
        ],
        'home_whats_new_title' => 'What\'s new for 2026–2027',
        'home_whats_new_body' => 'Big steps forward for current and prospective Eagles families this season.',
        'home_whats_new_items' => [
            ['title' => 'Youth Strength & Conditioning', 'description' => 'Age-appropriate strength programs for our youngest players, built on movement quality and injury prevention.'],
            ['title' => 'Speed & Agility', 'description' => 'Dedicated speed and agility sessions to build first-step quickness, baserunning speed, and defensive range.'],
            ['title' => 'New Eagles Baseball Facility — Coming Soon', 'description' => 'A new home for the Eagles with cages, mounds, and turf for year-round training.'],
            ['title' => 'Dedicated Weight Room for 14U+', 'description' => 'A dedicated weight room and structured lifting program to prepare 14U and older players for high school baseball.'],
            ['title' => 'High School Eagles Program', 'description' => 'Eagles baseball now continues past 14U with a program built for high school players.'],
        ],
        'home_offerings' => [
            ['title' => 'Competitive Teams', 'description' => 'Age-group teams from 10U through high school competing in top regional tournaments.'],
            ['title' => 'Professional Coaching', 'description' => 'Experienced coaches focused on player development on and off the field.'],
            ['title' => 'Camps & Clinics', 'description' => 'Seasonal skills camps for hitting, pitching, fielding, and catching.'],
            ['title' => 'Training Facility', 'description' => 'Indoor cages, mounds, and turf for year-round development.'],
        ],
        'home_about_heading' => 'Meet Coach John Herrera',
        'home_about_body' => 'Eagles Baseball was founded in 2023 by Coach John Herrera. Placeholder: add Coach John\'s baseball background and why he started the Eagles. This copy is editable in the admin under Site Settings.',
        'home_about_image' => null,
        'home_youtube_url' => null,
        'home_closing_title' => 'Stepping up to the plate',
        'home_closing_body' => 'Joining the Eagles means committing to sportsmanship, respect, and integrity — supporting your coaches and teammates and helping create a positive experience for everyone involved. If that sounds like your family, we would love to hear from you.',

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
        'home' => ['home_hero_headline', 'home_hero_subheadline', 'home_hero_cta_label', 'home_hero_cta_url', 'home_hero_secondary_cta_label', 'home_hero_secondary_cta_url', 'home_hero_image', 'home_intro', 'home_mission_title', 'home_mission_body', 'home_belief', 'home_philosophy_title', 'home_philosophy_body', 'home_values', 'home_goals_title', 'home_goals_body', 'home_goals', 'home_development_title', 'home_development_body', 'home_development_tiers', 'home_year_round_title', 'home_year_round_body', 'home_year_round_items', 'home_whats_new_title', 'home_whats_new_body', 'home_whats_new_items', 'home_offerings', 'home_about_heading', 'home_about_body', 'home_about_image', 'home_youtube_url', 'home_closing_title', 'home_closing_body'],
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
     * Keys that hold a list of title/description-style rows.
     *
     * @var list<string>
     */
    public const array LIST_KEYS = ['home_values', 'home_goals', 'home_development_tiers', 'home_year_round_items', 'home_whats_new_items', 'home_offerings', 'seo_faq'];

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
