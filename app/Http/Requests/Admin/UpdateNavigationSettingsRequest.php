<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNavigationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nav_cta_label' => ['nullable', 'string', 'max:30'],
            'nav_cta_url' => ['nullable', 'string', 'max:2048', 'regex:#^(https?://|/)#i'],
            'nav_show_cta' => ['nullable', 'boolean'],
            'nav_show_cart' => ['nullable', 'boolean'],
            'footer_tagline' => ['nullable', 'string', 'max:255'],
            'footer_links_heading' => ['nullable', 'string', 'max:40'],
            'footer_contact_heading' => ['nullable', 'string', 'max:40'],
            'footer_show_contact' => ['nullable', 'boolean'],
            'footer_show_socials' => ['nullable', 'boolean'],
            'footer_show_address' => ['nullable', 'boolean'],
            'footer_copyright' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settingValues(): array
    {
        $values = $this->validated();

        foreach (['nav_show_cta', 'nav_show_cart', 'footer_show_contact', 'footer_show_socials', 'footer_show_address'] as $flag) {
            $values[$flag] = $this->boolean($flag);
        }

        return array_map(fn ($value) => is_string($value) && trim($value) === '' ? null : $value, $values);
    }
}
