<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

beforeEach(function () {
    $this->staff = User::factory()->staff()->create();
    $this->product = Product::factory()->create();
});

test('staff can add a variant', function () {
    $this->actingAs($this->staff)
        ->post(route('admin.products.variants.store', $this->product), [
            'size' => 'M',
            'color' => 'Navy',
            'stock' => 5,
            'price_override' => '30.00',
            'is_active' => 1,
        ])
        ->assertRedirect(route('admin.products.edit', $this->product));

    $variant = $this->product->variants()->firstOrFail();
    expect($variant->size)->toBe('M')
        ->and($variant->stock)->toBe(5)
        ->and($variant->price_override)->toBe(3000)
        ->and($variant->price())->toBe(3000);
});

test('duplicate size and color combination is rejected', function () {
    ProductVariant::factory()->for($this->product)->create(['size' => 'M', 'color' => 'Navy']);

    $this->actingAs($this->staff)
        ->from(route('admin.products.edit', $this->product))
        ->post(route('admin.products.variants.store', $this->product), ['size' => 'M', 'color' => 'Navy'])
        ->assertSessionHasErrors('size');
});

test('staff can update a variant', function () {
    $variant = ProductVariant::factory()->for($this->product)->create(['size' => 'M', 'stock' => 3]);

    $this->actingAs($this->staff)
        ->put(route('admin.products.variants.update', [$this->product, $variant]), [
            'size' => 'L',
            'color' => 'Navy',
            'stock' => '',
            'is_active' => 0,
        ])
        ->assertRedirect(route('admin.products.edit', $this->product));

    $variant->refresh();
    expect($variant->size)->toBe('L')
        ->and($variant->stock)->toBeNull()
        ->and($variant->is_active)->toBeFalse();
});

test('a variant cannot be updated through another product', function () {
    $other = Product::factory()->create();
    $variant = ProductVariant::factory()->for($other)->create();

    $this->actingAs($this->staff)
        ->put(route('admin.products.variants.update', [$this->product, $variant]), ['size' => 'XL'])
        ->assertNotFound();
});

test('staff can delete a variant', function () {
    $variant = ProductVariant::factory()->for($this->product)->create();

    $this->actingAs($this->staff)
        ->delete(route('admin.products.variants.destroy', [$this->product, $variant]))
        ->assertRedirect(route('admin.products.edit', $this->product));

    $this->assertModelMissing($variant);
});
