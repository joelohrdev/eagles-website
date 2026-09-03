<?php

namespace App\Support\Seo;

use App\Models\Camp;
use App\Models\Coach;
use App\Models\Product;
use App\Models\Team;
use App\Models\Tryout;
use App\Services\ImageUploader;

/**
 * Builders for schema.org JSON-LD objects.
 */
final class Schema
{
    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public static function organization(array $settings): array
    {
        $sameAs = array_values(array_filter([
            $settings['social_facebook'] ?? null,
            $settings['social_instagram'] ?? null,
            $settings['social_twitter'] ?? null,
            $settings['social_youtube'] ?? null,
            $settings['social_tiktok'] ?? null,
        ]));

        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'SportsOrganization',
            '@id' => url('/').'#organization',
            'name' => $settings['org_name'] ?? config('app.name'),
            'url' => url('/'),
            'sport' => 'Baseball',
            'telephone' => $settings['phone'] ?? null,
            'email' => $settings['email'] ?? null,
            'foundingDate' => $settings['founding_year'] ?? null,
            'areaServed' => $settings['service_area'] ?? null,
            'sameAs' => $sameAs ?: null,
            'logo' => ImageUploader::absoluteUrl($settings['seo_default_share_image'] ?? null),
        ], fn ($value) => $value !== null && $value !== '');

        if (! empty($settings['address_line1']) || ! empty($settings['address_city'])) {
            $schema['address'] = array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $settings['address_line1'] ?? null,
                'addressLocality' => $settings['address_city'] ?? null,
                'addressRegion' => $settings['address_state'] ?? null,
                'postalCode' => $settings['address_postal_code'] ?? null,
                'addressCountry' => 'US',
            ]);
        }

        if (! empty($settings['geo_latitude']) && ! empty($settings['geo_longitude'])) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $settings['geo_latitude'],
                'longitude' => (float) $settings['geo_longitude'],
            ];
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public static function website(array $settings): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $settings['seo_site_name'] ?? config('app.name'),
            'url' => url('/'),
            'publisher' => ['@id' => url('/').'#organization'],
        ];
    }

    /**
     * @param  list<array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function sportsTeam(Team $team): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'SportsTeam',
            'name' => $team->name,
            'sport' => 'Baseball',
            'description' => $team->description,
            'image' => ImageUploader::absoluteUrl($team->photo_path),
            'memberOf' => ['@id' => url('/').'#organization'],
            'coach' => $team->coach ? self::person($team->coach, withContext: false) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function person(Coach $coach, bool $withContext = true): array
    {
        return array_filter([
            '@context' => $withContext ? 'https://schema.org' : null,
            '@type' => 'Person',
            'name' => $coach->name,
            'jobTitle' => $coach->title,
            'description' => $coach->bio,
            'image' => ImageUploader::absoluteUrl($coach->photo_path),
            'worksFor' => ['@id' => url('/').'#organization'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function tryoutEvent(Tryout $tryout): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'SportsEvent',
            'name' => $tryout->title,
            'description' => $tryout->description,
            'startDate' => $tryout->event_at->toIso8601String(),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => $tryout->location ? ['@type' => 'Place', 'name' => $tryout->location] : null,
            'organizer' => ['@id' => url('/').'#organization'],
            'image' => ImageUploader::absoluteUrl($tryout->image_path),
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
                'availability' => $tryout->isRegistrationOpen() ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
                'url' => route('tryouts.show', $tryout),
                'validFrom' => $tryout->registration_opens_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function campEvent(Camp $camp): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'SportsEvent',
            'name' => $camp->name,
            'description' => $camp->description,
            'startDate' => $camp->starts_at->toIso8601String(),
            'endDate' => $camp->ends_at?->toIso8601String(),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => $camp->location ? ['@type' => 'Place', 'name' => $camp->location] : null,
            'organizer' => ['@id' => url('/').'#organization'],
            'image' => ImageUploader::absoluteUrl($camp->image_path),
            'offers' => [
                '@type' => 'Offer',
                'price' => number_format($camp->price / 100, 2, '.', ''),
                'priceCurrency' => 'USD',
                'availability' => $camp->isRegistrationOpen() ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
                'url' => route('camps.show', $camp),
                'validFrom' => $camp->registration_opens_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function product(Product $product): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->description,
            'image' => ImageUploader::absoluteUrl($product->image_path),
            'brand' => ['@type' => 'Brand', 'name' => 'Eagles Baseball Travel'],
            'offers' => [
                '@type' => 'Offer',
                'price' => number_format($product->price / 100, 2, '.', ''),
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock',
                'url' => route('merch.show', $product),
            ],
        ]);
    }

    /**
     * @param  list<array{question: string, answer: string}>  $faqs
     * @return array<string, mixed>|null
     */
    public static function faq(array $faqs): ?array
    {
        if ($faqs === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $faq) => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
            ], $faqs),
        ];
    }
}
