<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Http\Requests\Admin\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProductVariantController extends Controller
{
    public function store(StoreProductVariantRequest $request, Product $product): RedirectResponse
    {
        $product->variants()->create($request->variantAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Variant added.')]);

        return to_route('admin.products.edit', $product);
    }

    public function update(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        $variant->update($request->variantAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Variant updated.')]);

        return to_route('admin.products.edit', $product);
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        $variant->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Variant removed.')]);

        return to_route('admin.products.edit', $product);
    }
}
