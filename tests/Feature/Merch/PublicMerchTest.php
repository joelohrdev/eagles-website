<?php

use App\Models\Product;
use App\Models\ProductVariant;

test('merch index lists only active products', function () {
    $active = Product::factory()->create(['name' => 'Visible Tee']);
    Product::factory()->inactive()->create(['name' => 'Hidden Tee']);
    ProductVariant::factory()->for($active)->create();

    $this->get(route('merch.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('site/Merch/Index')
            ->has('products', 1)
            ->where('products.0.name', 'Visible Tee')
            ->where('seo.title', 'Merch | Eagles Baseball Travel'));
});

test('product page shows active variants and seo', function () {
    $product = Product::factory()->create(['name' => 'Eagles Snapback', 'price' => 2500]);
    ProductVariant::factory()->for($product)->create(['size' => 'OSFA', 'color' => 'Navy']);
    ProductVariant::factory()->for($product)->inactive()->create(['size' => 'OSFA', 'color' => 'White']);

    $this->get(route('merch.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('site/Merch/Show')
            ->where('product.name', 'Eagles Snapback')
            ->has('variants', 1)
            ->where('seo.share_title', 'Eagles Snapback')
            ->where('seo.og_type', 'product')
            ->where('seo.json_ld.1.@type', 'Product'));
});

test('inactive product pages return 404', function () {
    $product = Product::factory()->inactive()->create();

    $this->get(route('merch.show', $product))->assertNotFound();
});
