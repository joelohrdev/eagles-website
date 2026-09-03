<?php

namespace App\Http\Requests\Site;

use App\Models\ProductVariant;
use App\Services\Cart;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
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
            'quantity' => ['required', 'integer', 'min:0', 'max:'.Cart::MAX_QUANTITY],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var ProductVariant $variant */
                $variant = $this->route('variant');

                if ($validator->errors()->isEmpty() && ! $variant->isInStock($this->integer('quantity'))) {
                    $validator->errors()->add('quantity', __('Only :stock left in stock.', ['stock' => $variant->stock]));
                }
            },
        ];
    }
}
