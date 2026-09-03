<?php

namespace App\Http\Requests\Site;

use App\Models\ProductVariant;
use App\Services\Cart;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
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
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.Cart::MAX_QUANTITY],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $variant = $this->variant();

                if (! $variant->is_active || ! $variant->product?->is_active) {
                    $validator->errors()->add('product_variant_id', __('That item is no longer available.'));

                    return;
                }

                $existing = app(Cart::class)->items()[$variant->id] ?? 0;

                if (! $variant->isInStock($existing + $this->quantity())) {
                    $validator->errors()->add('quantity', __('Only :stock left in stock.', ['stock' => $variant->stock]));
                }
            },
        ];
    }

    public function variant(): ProductVariant
    {
        return ProductVariant::query()->with('product')->findOrFail($this->integer('product_variant_id'));
    }

    public function quantity(): int
    {
        return max(1, $this->integer('quantity', 1));
    }
}
