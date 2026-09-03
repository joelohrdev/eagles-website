<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->staff = User::factory()->staff()->create();
});

test('guests are redirected from admin products', function () {
    $this->get(route('admin.products.index'))->assertRedirect(route('login'));
});

test('staff can view the products index', function () {
    Product::factory()->count(3)->create();

    $this->actingAs($this->staff)
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/products/Index')->has('products.data', 3));
});

test('staff can view the create page', function () {
    $this->actingAs($this->staff)
        ->get(route('admin.products.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/products/Create'));
});

test('staff can create a product with an image and seo', function () {
    Storage::fake('public');

    $response = $this->actingAs($this->staff)->post(route('admin.products.store'), [
        'name' => 'Eagles Hoodie',
        'description' => 'Warm and cozy.',
        'price' => '45.50',
        'is_active' => 1,
        'image' => UploadedFile::fake()->image('hoodie.jpg', 1200, 800),
        'seo' => ['title' => 'Eagles Hoodie', 'share_title' => 'Get the hoodie'],
        'seo_share_image' => UploadedFile::fake()->image('share.jpg', 1200, 630),
    ]);

    $product = Product::query()->where('name', 'Eagles Hoodie')->firstOrFail();

    $response->assertRedirect(route('admin.products.edit', $product));
    expect($product->price)->toBe(4550)
        ->and($product->slug)->toBe('eagles-hoodie')
        ->and($product->is_active)->toBeTrue()
        ->and($product->image_path)->toStartWith('products/');
    Storage::disk('public')->assertExists($product->image_path);
    expect($product->seoMeta->title)->toBe('Eagles Hoodie')
        ->and($product->seoMeta->share_image_path)->not->toBeNull();
});

test('product creation validates required fields', function () {
    $this->actingAs($this->staff)
        ->from(route('admin.products.create'))
        ->post(route('admin.products.store'), ['name' => '', 'price' => 'abc'])
        ->assertSessionHasErrors(['name', 'price']);
});

test('staff can view the edit page', function () {
    $product = Product::factory()->create();

    $this->actingAs($this->staff)
        ->get(route('admin.products.edit', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/products/Edit')->where('product.id', $product->id));
});

test('staff can update a product and remove its image', function () {
    Storage::fake('public');
    Storage::disk('public')->put('products/old.webp', 'x');
    Storage::disk('public')->put('products/thumbs/old.webp', 'x');
    $product = Product::factory()->create(['image_path' => 'products/old.webp', 'price' => 1000]);

    $this->actingAs($this->staff)
        ->put(route('admin.products.update', $product), [
            'name' => 'Renamed Tee',
            'price' => '12.00',
            'is_active' => 0,
            'remove_image' => 1,
        ])
        ->assertRedirect(route('admin.products.edit', $product->fresh()));

    $product->refresh();
    expect($product->name)->toBe('Renamed Tee')
        ->and($product->price)->toBe(1200)
        ->and($product->is_active)->toBeFalse()
        ->and($product->image_path)->toBeNull();
    Storage::disk('public')->assertMissing('products/old.webp');
});

test('staff can delete a product', function () {
    $product = Product::factory()->create();

    $this->actingAs($this->staff)
        ->delete(route('admin.products.destroy', $product))
        ->assertRedirect(route('admin.products.index'));

    $this->assertModelMissing($product);
});
