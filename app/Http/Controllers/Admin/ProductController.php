<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Seo\SyncSeoMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderProductsRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Services\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private ImageUploader $images,
        private SyncSeoMeta $syncSeoMeta,
    ) {}

    public function index(): Response
    {
        $products = Product::query()
            ->withCount('variants')
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/products/Index', [
            'products' => $products,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/products/Create');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $attributes = $request->productAttributes();
        $attributes['sort_order'] = Product::nextSortOrder();

        if ($request->hasFile('image')) {
            $attributes['image_path'] = $this->images->store($request->file('image'), 'products');
        }

        $product = Product::query()->create($attributes);

        $this->syncSeoMeta->forModel($product, $request->validated('seo'), $request->file('seo_share_image'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product created.')]);

        return to_route('admin.products.edit', $product);
    }

    public function edit(Product $product): Response
    {
        $product->load(['variants' => fn ($q) => $q->orderBy('size')->orderBy('color'), 'seoMeta']);

        return Inertia::render('admin/products/Edit', [
            'product' => $product,
            'seo' => $product->seoMeta,
            'publicUrl' => route('merch.show', $product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $attributes = $request->productAttributes();

        if ($request->boolean('remove_image') && $product->image_path) {
            $this->images->delete($product->image_path);
            $attributes['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            $attributes['image_path'] = $this->images->replace($request->file('image'), 'products', $product->image_path);
        }

        $product->update($attributes);

        $this->syncSeoMeta->forModel($product, $request->validated('seo'), $request->file('seo_share_image'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product updated.')]);

        return to_route('admin.products.edit', $product);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->images->delete($product->image_path);
        $this->images->deleteShareImage($product->seoMeta?->share_image_path);

        $product->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product deleted.')]);

        return to_route('admin.products.index');
    }

    public function reorder(ReorderProductsRequest $request): RedirectResponse
    {
        Product::applyManualOrder($request->order());

        return back();
    }
}
