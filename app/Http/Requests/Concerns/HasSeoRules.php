<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

/**
 * Validation rules for the shared "SEO & Sharing" form section.
 * Fields arrive nested under `seo.*`; the share image arrives as `seo_share_image`.
 */
trait HasSeoRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function seoRules(): array
    {
        return [
            'seo' => ['nullable', 'array'],
            'seo.title' => ['nullable', 'string', 'max:70'],
            'seo.description' => ['nullable', 'string', 'max:320'],
            'seo.canonical_url' => ['nullable', 'url', 'max:255'],
            'seo.robots' => ['nullable', 'string', Rule::in(['index,follow', 'noindex,follow', 'noindex,nofollow', 'index,nofollow'])],
            'seo.share_title' => ['nullable', 'string', 'max:95'],
            'seo.share_description' => ['nullable', 'string', 'max:320'],
            'seo.share_image_alt' => ['nullable', 'string', 'max:255'],
            'seo.twitter_card' => ['nullable', 'string', Rule::in(['summary', 'summary_large_image'])],
            'seo.remove_share_image' => ['nullable', 'boolean'],
            'seo_share_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=600,min_height=315'],
        ];
    }
}
