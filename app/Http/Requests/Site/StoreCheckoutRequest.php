<?php

namespace App\Http\Requests\Site;

use App\Enums\Fulfillment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'fulfillment' => ['required', Rule::enum(Fulfillment::class)],
            'shipping_address_line1' => ['nullable', 'required_if:fulfillment,shipping', 'string', 'max:255'],
            'shipping_address_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['nullable', 'required_if:fulfillment,shipping', 'string', 'max:255'],
            'shipping_state' => ['nullable', 'required_if:fulfillment,shipping', 'string', 'max:2'],
            'shipping_postal_code' => ['nullable', 'required_if:fulfillment,shipping', 'string', 'max:10'],
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'shipping_address_line1' => 'street address',
            'shipping_city' => 'city',
            'shipping_state' => 'state',
            'shipping_postal_code' => 'ZIP code',
        ];
    }

    public function isSpam(): bool
    {
        return filled($this->input('website'));
    }
}
