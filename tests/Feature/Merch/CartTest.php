<?php

use App\Models\Product;
use App\Models\ProductVariant;

test('cart page renders empty', function () {
    $this->get(route('cart.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('site/Cart/Index')->where('cart.count', 0));
});

test('a variant can be added to the cart', function () {
    $variant = ProductVariant::factory()->create();

    $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 2])
        ->assertRedirect(route('cart.index'))
        ->assertSessionHas('cart', [$variant->id => 2]);

    $this->get(route('cart.index'))
        ->assertInertia(fn ($page) => $page
            ->where('cart.count', 2)
            ->where('cart.subtotal', $variant->price() * 2)
            ->where('cart.lines.0.variant_id', $variant->id));
});

test('adding again increases quantity up to the max', function () {
    $variant = ProductVariant::factory()->create();

    $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 8]);
    $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 8])
        ->assertSessionHas('cart', [$variant->id => 10]);
});

test('inactive or out-of-stock variants cannot be added', function () {
    $inactive = ProductVariant::factory()->inactive()->create();
    $inactiveProduct = ProductVariant::factory()->for(Product::factory()->inactive())->create();
    $outOfStock = ProductVariant::factory()->outOfStock()->create();

    $this->post(route('cart.items.store'), ['product_variant_id' => $inactive->id])->assertSessionHasErrors('product_variant_id');
    $this->post(route('cart.items.store'), ['product_variant_id' => $inactiveProduct->id])->assertSessionHasErrors('product_variant_id');
    $this->post(route('cart.items.store'), ['product_variant_id' => $outOfStock->id])->assertSessionHasErrors('quantity');
    $this->post(route('cart.items.store'), ['product_variant_id' => 999])->assertSessionHasErrors('product_variant_id');
});

test('quantity is limited by stock', function () {
    $variant = ProductVariant::factory()->create(['stock' => 2]);

    $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 3])
        ->assertSessionHasErrors('quantity');
});

test('cart quantities can be updated and items removed', function () {
    $variant = ProductVariant::factory()->create();
    $this->withSession(['cart' => [$variant->id => 1]])
        ->patch(route('cart.items.update', $variant), ['quantity' => 4])
        ->assertRedirect()
        ->assertSessionHas('cart', [$variant->id => 4]);

    $this->withSession(['cart' => [$variant->id => 4]])
        ->delete(route('cart.items.destroy', $variant))
        ->assertRedirect()
        ->assertSessionHas('cart', []);
});

test('lines for deactivated products are dropped from the cart', function () {
    $variant = ProductVariant::factory()->create();
    $variant->product->update(['is_active' => false]);

    $this->withSession(['cart' => [$variant->id => 1]])
        ->get(route('cart.index'))
        ->assertInertia(fn ($page) => $page->where('cart.count', 0));
});

test('product page reports how many of each variant are already in the cart', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->for($product)->create();

    $this->withSession(['cart' => [$variant->id => 3]])
        ->get(route('merch.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('variants.0.in_cart', 3));
});

test('adding a variant that is already in the cart increments and the toast states the total', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->for($product)->create();

    $this->withSession(['cart' => [$variant->id => 2]])
        ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1])
        ->assertRedirect(route('cart.index'));

    expect(session('cart')[$variant->id])->toBe(3);
});
