<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductVariantRequest extends FormRequest
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
        /** @var Product $product */
        $product = $this->route('product');
        /** @var ProductVariant|null $variant */
        $variant = $this->route('variant');

        $unique = Rule::unique('product_variants', 'size')
            ->where('product_id', $product->id)
            ->where('color', $this->input('color') ?: null);

        if ($variant) {
            $unique->ignore($variant->id);
        }

        return [
            'size' => ['nullable', 'string', 'max:50', $unique],
            'color' => ['nullable', 'string', 'max:50'],
            'sku' => ['nullable', 'string', 'max:100'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'price_override' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'size.unique' => __('A variant with this size and color already exists.'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function variantAttributes(): array
    {
        $validated = $this->validated();

        return [
            'size' => filled($validated['size'] ?? null) ? $validated['size'] : null,
            'color' => filled($validated['color'] ?? null) ? $validated['color'] : null,
            'sku' => filled($validated['sku'] ?? null) ? $validated['sku'] : null,
            'stock' => isset($validated['stock']) && $validated['stock'] !== '' ? (int) $validated['stock'] : null,
            'price_override' => isset($validated['price_override']) && $validated['price_override'] !== ''
                ? (int) round(((float) $validated['price_override']) * 100)
                : null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];
    }
}
