<?php

namespace App\Http\Requests\Site;

use App\Enums\Fulfillment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A coupon code typed on the merch checkout page, priced against the cart.
 */
class QuoteCheckoutRequest extends FormRequest
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
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'fulfillment' => ['nullable', Rule::enum(Fulfillment::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['coupon_code' => 'coupon code'];
    }
}
