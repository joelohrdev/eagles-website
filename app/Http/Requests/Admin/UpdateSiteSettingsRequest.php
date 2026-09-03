<?php

namespace App\Http\Requests\Admin;

use App\Services\PageVisibility;
use App\Services\SiteSettings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $image = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

        return match ($this->route('group')) {
            'organization' => [
                'org_name' => ['required', 'string', 'max:120'],
                'phone' => ['nullable', 'string', 'max:40'],
                'email' => ['nullable', 'email', 'max:255'],
                'address_line1' => ['nullable', 'string', 'max:255'],
                'address_city' => ['nullable', 'string', 'max:120'],
                'address_state' => ['nullable', 'string', 'max:40'],
                'address_postal_code' => ['nullable', 'string', 'max:20'],
                'geo_latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'geo_longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'founding_year' => ['nullable', 'digits:4'],
                'service_area' => ['nullable', 'string', 'max:255'],
                'social_facebook' => ['nullable', 'url', 'max:255'],
                'social_instagram' => ['nullable', 'url', 'max:255'],
                'social_twitter' => ['nullable', 'url', 'max:255'],
                'social_youtube' => ['nullable', 'url', 'max:255'],
                'social_tiktok' => ['nullable', 'url', 'max:255'],
            ],
            'home' => [
                'home_hero_headline' => ['required', 'string', 'max:160'],
                'home_hero_subheadline' => ['nullable', 'string', 'max:500'],
                'home_hero_cta_label' => ['nullable', 'string', 'max:60'],
                'home_hero_cta_url' => ['nullable', 'string', 'max:255'],
                'home_hero_secondary_cta_label' => ['nullable', 'string', 'max:60'],
                'home_hero_secondary_cta_url' => ['nullable', 'string', 'max:255'],
                'home_hero_image' => $image,
                'remove_home_hero_image' => ['nullable', 'boolean'],
                'home_intro' => ['nullable', 'string', 'max:1000'],
                'home_offerings' => ['nullable', 'array', 'max:6'],
                'home_offerings.*.title' => ['required', 'string', 'max:80'],
                'home_offerings.*.description' => ['nullable', 'string', 'max:300'],
                'home_about_heading' => ['nullable', 'string', 'max:120'],
                'home_about_body' => ['nullable', 'string', 'max:5000'],
                'home_about_image' => $image,
                'remove_home_about_image' => ['nullable', 'boolean'],
                'home_youtube_url' => ['nullable', 'url', 'max:255'],
            ],
            'facility' => [
                'facility_heading' => ['required', 'string', 'max:120'],
                'facility_description' => ['nullable', 'string', 'max:5000'],
                'facility_address' => ['nullable', 'string', 'max:255'],
                'facility_youtube_url' => ['nullable', 'url', 'max:255'],
            ],
            'contact' => [
                'contact_intro' => ['nullable', 'string', 'max:1000'],
            ],
            'pages' => collect(PageVisibility::PAGES)
                ->mapWithKeys(fn (array $page, string $key) => [PageVisibility::settingKey($key) => ['nullable', 'boolean']])
                ->all(),
            'seo' => [
                'seo_site_name' => ['required', 'string', 'max:120'],
                'seo_title_template' => ['required', 'string', 'max:120', 'regex:/%s/'],
                'seo_default_description' => ['nullable', 'string', 'max:320'],
                'seo_default_share_image' => [...$image, 'dimensions:min_width=600,min_height=315'],
                'remove_seo_default_share_image' => ['nullable', 'boolean'],
                'seo_google_site_verification' => ['nullable', 'string', 'max:255'],
                'seo_bing_site_verification' => ['nullable', 'string', 'max:255'],
                'seo_facebook_app_id' => ['nullable', 'string', 'max:64'],
                'seo_twitter_handle' => ['nullable', 'string', 'max:32', 'regex:/^@?[A-Za-z0-9_]{1,15}$/'],
                'seo_faq' => ['nullable', 'array', 'max:20'],
                'seo_faq.*.question' => ['required', 'string', 'max:255'],
                'seo_faq.*.answer' => ['required', 'string', 'max:2000'],
            ],
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'seo_title_template.regex' => 'The title template must contain %s where the page title goes.',
        ];
    }

    /**
     * Setting values to persist (excludes file inputs and remove_* flags).
     *
     * @return array<string, mixed>
     */
    public function settingValues(): array
    {
        $group = (string) $this->route('group');
        $keys = SiteSettings::GROUPS[$group] ?? [];
        $validated = $this->validated();
        $values = [];

        foreach ($keys as $key) {
            if (in_array($key, SiteSettings::IMAGE_KEYS, true)) {
                continue;
            }

            if (array_key_exists($key, $validated)) {
                $values[$key] = $this->normalize($key, $validated[$key]);
            }
        }

        return $values;
    }

    private function normalize(string $key, mixed $value): mixed
    {
        if ($key === 'home_offerings' || $key === 'seo_faq') {
            return array_values(array_map(fn (array $row) => array_map(fn ($v) => filled($v) ? $v : null, $row), $value ?? []));
        }

        if ($this->isPageToggle($key)) {
            return $this->boolean($key);
        }

        return filled($value) ? $value : null;
    }

    /**
     * Page toggles arrive as "0"/"1" from the switch inputs and must be stored as booleans.
     */
    private function isPageToggle(string $key): bool
    {
        $toggles = array_map(fn (string $page) => PageVisibility::settingKey($page), array_keys(PageVisibility::PAGES));

        return in_array($key, $toggles, true);
    }
}
