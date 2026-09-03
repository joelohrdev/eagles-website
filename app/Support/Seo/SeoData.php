<?php

namespace App\Support\Seo;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Resolved SEO + social sharing data for a single page, ready to be
 * passed to the frontend as the `seo` Inertia prop.
 *
 * @implements Arrayable<string, mixed>
 */
final class SeoData implements Arrayable
{
    /**
     * @param  list<array<string, mixed>>  $jsonLd
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public string $canonicalUrl,
        public string $robots,
        public string $siteName,
        public string $shareTitle,
        public ?string $shareDescription,
        public ?string $shareImageUrl,
        public ?string $shareImageAlt,
        public string $twitterCard,
        public string $ogType,
        public array $jsonLd,
        public ?string $twitterHandle = null,
        public ?string $facebookAppId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical_url' => $this->canonicalUrl,
            'robots' => $this->robots,
            'site_name' => $this->siteName,
            'share_title' => $this->shareTitle,
            'share_description' => $this->shareDescription,
            'share_image_url' => $this->shareImageUrl,
            'share_image_alt' => $this->shareImageAlt,
            'share_image_width' => $this->shareImageUrl ? 1200 : null,
            'share_image_height' => $this->shareImageUrl ? 630 : null,
            'twitter_card' => $this->twitterCard,
            'twitter_handle' => $this->twitterHandle,
            'facebook_app_id' => $this->facebookAppId,
            'og_type' => $this->ogType,
            'json_ld' => $this->jsonLd,
        ];
    }
}
