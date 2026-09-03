<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Cart;
use App\Services\SeoResolver;
use App\Support\Seo\Schema;
use Inertia\Inertia;
use Inertia\Response;

class MerchController extends Controller
{
    public function __construct(private SeoResolver $seo, private Cart $cart) {}

    public function index(): Response
    {
        $products = Product::query()
            ->active()
            ->ordered()
            ->with(['variants' => fn ($q) => $q->active()])
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'image_url' => $product->image_url,
                'image_thumbnail_url' => $product->image_thumbnail_url,
                'has_variants' => $product->variants->isNotEmpty(),
                'in_stock' => $product->variants->contains(fn ($v) => $v->isInStock()),
            ]);

        return Inertia::render('site/Merch/Index', [
            'products' => $products,
            'seo' => $this->seo->forRoute('merch.index', [
                'title' => 'Merch',
                'description' => 'Official Eagles Baseball Travel gear — tees, hoodies, hats, and more. Order online and pick up locally.',
                'json_ld' => [
                    Schema::breadcrumbs([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Merch', 'url' => route('merch.index')],
                    ]),
                ],
            ])->toArray(),
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load(['variants' => fn ($q) => $q->active()->orderBy('size')->orderBy('color'), 'seoMeta']);

        $inCart = $this->cart->items();

        $variants = $product->variants->map(fn ($variant) => [
            'id' => $variant->id,
            'size' => $variant->size,
            'color' => $variant->color,
            'label' => $variant->label(),
            'price' => $variant->price(),
            'stock' => $variant->stock,
            'in_stock' => $variant->isInStock(),
            'in_cart' => $inCart[$variant->id] ?? 0,
        ])->values();

        return Inertia::render('site/Merch/Show', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => $product->price,
                'image_url' => $product->image_url,
                'url' => route('merch.show', $product),
            ],
            'variants' => $variants,
            'seo' => $this->seo->forModel($product, [
                'title' => $product->name,
                'description' => $product->description
                    ? str($product->description)->limit(155)->toString()
                    : "{$product->name} — official Eagles Baseball Travel merch.",
                'share_image_path' => $product->image_path,
                'og_type' => 'product',
                'json_ld' => [
                    Schema::product($product),
                    Schema::breadcrumbs([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Merch', 'url' => route('merch.index')],
                        ['name' => $product->name, 'url' => route('merch.show', $product)],
                    ]),
                ],
            ])->toArray(),
        ]);
    }
}
