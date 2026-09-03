<?php

namespace App\Http\Requests\Admin;

use App\Models\NavigationItem;
use App\Support\Seo\StaticPages;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNavigationItemRequest extends FormRequest
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
            'location' => ['required', Rule::in(NavigationItem::LOCATIONS)],
            'label' => ['required', 'string', 'max:40'],
            'link_type' => ['required', Rule::in(['page', 'custom'])],
            'route_name' => ['required_if:link_type,page', 'nullable', Rule::in([...StaticPages::keys(), 'cart.index'])],
            'url' => ['required_if:link_type,custom', 'nullable', 'string', 'max:2048', 'regex:#^(https?://|/|mailto:|tel:)#i'],
            'opens_in_new_tab' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.regex' => 'Enter a full URL (https://…), a site path (/teams), or a mailto:/tel: link.',
        ];
    }

    /**
     * Attributes for creating/updating a NavigationItem.
     *
     * @return array<string, mixed>
     */
    public function itemAttributes(): array
    {
        $isPage = $this->validated('link_type') === 'page';

        return [
            'label' => $this->validated('label'),
            'route_name' => $isPage ? $this->validated('route_name') : null,
            'url' => $isPage ? null : $this->validated('url'),
            'opens_in_new_tab' => $this->boolean('opens_in_new_tab'),
            'is_visible' => $this->has('is_visible') ? $this->boolean('is_visible') : true,
        ];
    }
}
