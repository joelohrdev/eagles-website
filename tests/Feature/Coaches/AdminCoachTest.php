<?php

use App\Models\Coach;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->staff = User::factory()->staff()->create();
});

test('guests are redirected from admin coaches', function () {
    $this->get(route('admin.coaches.index'))->assertRedirect(route('login'));
});

test('staff can view the coaches index, create, and edit pages', function () {
    $coach = Coach::factory()->create();

    $this->actingAs($this->staff)->get(route('admin.coaches.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/coaches/Index')->has('coaches.data', 1));
    $this->actingAs($this->staff)->get(route('admin.coaches.create'))->assertOk();
    $this->actingAs($this->staff)->get(route('admin.coaches.edit', $coach))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/coaches/Edit')->where('coach.id', $coach->id));
});

test('staff can create a coach with a photo', function () {
    $this->actingAs($this->staff)->post(route('admin.coaches.store'), [
        'name' => 'Jane Doe',
        'title' => 'Head Coach',
        'bio' => 'Bio here.',
        'email' => 'jane@example.com',
        'is_active' => 1,
        'photo' => UploadedFile::fake()->image('coach.jpg', 800, 800),
        'seo' => ['share_title' => 'Meet Coach Jane'],
    ])->assertRedirect(route('admin.coaches.index'))->assertSessionHasNoErrors();

    $coach = Coach::query()->where('name', 'Jane Doe')->firstOrFail();

    expect($coach->slug)->toBe('jane-doe')
        ->and($coach->photo_path)->toStartWith('coaches/')
        ->and($coach->seoMeta->share_title)->toBe('Meet Coach Jane');

    Storage::disk('public')->assertExists($coach->photo_path);
});

test('the edit page exposes a photo url that points at the stored file', function () {
    $coach = Coach::factory()->create();

    $this->actingAs($this->staff)->put(route('admin.coaches.update', $coach), [
        'name' => $coach->name,
        'is_active' => 1,
        'photo' => UploadedFile::fake()->image('coach.jpg', 800, 800),
    ])->assertRedirect(route('admin.coaches.edit', $coach))->assertSessionHasNoErrors();

    $coach->refresh();
    Storage::disk('public')->assertExists($coach->photo_path);

    $this->actingAs($this->staff)->get(route('admin.coaches.edit', $coach))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/coaches/Edit')
            ->where('coach.photo_url', "/storage/{$coach->photo_path}")
        );
});

test('creating a coach validates input', function () {
    $this->actingAs($this->staff)
        ->from(route('admin.coaches.create'))
        ->post(route('admin.coaches.store'), ['name' => '', 'email' => 'not-an-email'])
        ->assertRedirect(route('admin.coaches.create'))
        ->assertSessionHasErrors(['name', 'email']);
});

test('staff can update and delete a coach', function () {
    $coach = Coach::factory()->create(['photo_path' => 'coaches/old.webp']);
    Storage::disk('public')->put('coaches/old.webp', 'x');

    $this->actingAs($this->staff)->put(route('admin.coaches.update', $coach), [
        'name' => 'New Name',
        'is_active' => 0,
        'remove_photo' => 1,
    ])->assertSessionHasNoErrors();

    $coach->refresh();
    expect($coach->name)->toBe('New Name')->and($coach->is_active)->toBeFalse()->and($coach->photo_path)->toBeNull();
    Storage::disk('public')->assertMissing('coaches/old.webp');

    $this->actingAs($this->staff)->delete(route('admin.coaches.destroy', $coach))
        ->assertRedirect(route('admin.coaches.index'));
    $this->assertModelMissing($coach);
});
