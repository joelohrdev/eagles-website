<?php

use App\Models\SeoMeta;
use App\Models\User;
use App\Services\SeoResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('seo index lists static pages', function () {
    SeoMeta::factory()->forRoute('home')->create(['title' => 'Custom home']);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.seo.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/seo/Index')
            ->has('pages', 8)
            ->where('pages.0.key', 'home')
            ->where('pages.0.meta.title', 'Custom home')
            ->where('pages.1.meta', null)
        );
});

test('seo edit page renders for known keys and 404s otherwise', function () {
    $user = User::factory()->staff()->create();

    $this->actingAs($user)->get(route('admin.seo.edit', 'tryouts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/seo/Edit')
            ->where('routeKey', 'tryouts.index')
            ->where('fallback.title', 'Tryouts')
        );

    $this->actingAs($user)->get(route('admin.seo.edit', 'nope'))->assertNotFound();
});

test('seo meta can be updated with a share image', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->staff()->create())
        ->put(route('admin.seo.update', 'camps.index'), [
            'seo' => [
                'title' => 'Baseball Camps in Illinois',
                'description' => 'Skills camps for 9U–17U.',
                'robots' => 'index,follow',
                'share_title' => 'Eagles Camps',
                'twitter_card' => 'summary_large_image',
            ],
            'seo_share_image' => UploadedFile::fake()->image('share.jpg', 1400, 800),
        ])
        ->assertRedirect(route('admin.seo.edit', 'camps.index'));

    $meta = SeoMeta::query()->where('route_key', 'camps.index')->first();

    expect($meta)->not->toBeNull()
        ->and($meta->title)->toBe('Baseball Camps in Illinois')
        ->and($meta->share_title)->toBe('Eagles Camps')
        ->and($meta->share_image_path)->toStartWith('share/');

    Storage::disk('public')->assertExists($meta->share_image_path);

    // The resolver now prefers the stored meta over defaults.
    $resolved = app(SeoResolver::class)->forRoute('camps.index', ['title' => 'Camps']);

    expect($resolved->title)->toBe('Baseball Camps in Illinois | Eagles Baseball Travel')
        ->and($resolved->shareTitle)->toBe('Eagles Camps')
        ->and($resolved->shareImageUrl)->toContain($meta->share_image_path);
});

test('seo update rejects an unknown page key and invalid values', function () {
    $user = User::factory()->staff()->create();

    $this->actingAs($user)->put(route('admin.seo.update', 'nope'), ['seo' => ['title' => 'x']])->assertNotFound();

    $this->actingAs($user)->from(route('admin.seo.edit', 'home'))
        ->put(route('admin.seo.update', 'home'), ['seo' => ['title' => str_repeat('a', 71), 'robots' => 'bogus']])
        ->assertSessionHasErrors(['seo.title', 'seo.robots']);
});
