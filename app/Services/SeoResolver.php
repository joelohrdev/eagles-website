<?php

namespace App\Services;

use App\Models\SeoMeta;
use App\Support\Seo\Schema;
use App\Support\Seo\SeoData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Resolves the SEO/share data for a page from three layers, most specific first:
 *   1. the stored SeoMeta row (per route key or per model record),
 *   2. the auto-generated defaults supplied by the controller,
 *   3. sitewide defaults from site settings.
 */
class SeoResolver
{
    public function __construct(private SiteSettings $settings) {}

    /**
     * Resolve SEO data for a static page identified by its route name.
     *
     * @param  array<string, mixed>  $defaults  Keys: title, description, share_title, share_description,
     *                                          share_image_path, canonical_url, robots, og_type, json_ld (list)
     */
    public function forRoute(string $routeKey, array $defaults = []): SeoData
    {
        $meta = SeoMeta::query()->where('route_key', $routeKey)->first();

        return $this->build($meta, $defaults);
    }

    /**
     * Resolve SEO data for a model record (Team, Camp, Tryout, Product, Coach).
     *
     * @param  array<string, mixed>  $defaults
     */
    public function forModel(Model $model, array $defaults = []): SeoData
    {
        $meta = $model->relationLoaded('seoMeta') ? $model->getRelation('seoMeta') : $model->seoMeta()->first();

        return $this->build($meta, $defaults);
    }

    /**
     * @param  array<string, mixed>  $defaults
     */
    private function build(?SeoMeta $meta, array $defaults): SeoData
    {
        $settings = $this->settings->all();
        $siteName = $settings['seo_site_name'] ?: config('app.name');

        $rawTitle = $meta?->title ?: ($defaults['title'] ?? null);
        $title = $rawTitle
            ? sprintf($settings['seo_title_template'] ?: '%s', $rawTitle)
            : $siteName;

        $description = $meta?->description ?: ($defaults['description'] ?? null) ?: $settings['seo_default_description'];
        $description = $description ? Str::limit(strip_tags((string) $description), 300, '') : null;

        $shareTitle = $meta?->share_title ?: ($defaults['share_title'] ?? null) ?: ($rawTitle ?: $siteName);
        $shareDescription = $meta?->share_description ?: ($defaults['share_description'] ?? null) ?: $description;

        $shareImagePath = $meta?->share_image_path
            ?: ($defaults['share_image_path'] ?? null)
            ?: $settings['seo_default_share_image'];

        $jsonLd = array_values(array_filter([
            Schema::organization($settings),
            ...($defaults['json_ld'] ?? []),
        ]));

        if ($meta?->json_ld) {
            $jsonLd[] = $meta->json_ld;
        }

        return new SeoData(
            title: $title,
            description: $description,
            canonicalUrl: $meta?->canonical_url ?: ($defaults['canonical_url'] ?? url()->current()),
            robots: $meta?->robots ?: ($defaults['robots'] ?? 'index,follow'),
            siteName: $siteName,
            shareTitle: $shareTitle,
            shareDescription: $shareDescription ? Str::limit(strip_tags((string) $shareDescription), 300, '') : null,
            /** Never leave a shared link without a card — fall back to the generated one. */
            shareImageUrl: ImageUploader::absoluteUrl($shareImagePath) ?? route('share-card'),
            shareImageAlt: $meta?->share_image_alt ?: ($defaults['share_image_alt'] ?? $shareTitle),
            twitterCard: $meta?->twitter_card ?: 'summary_large_image',
            ogType: $defaults['og_type'] ?? 'website',
            jsonLd: $jsonLd,
            twitterHandle: $settings['seo_twitter_handle'] ?: null,
            facebookAppId: $settings['seo_facebook_app_id'] ?: null,
        );
    }
}
