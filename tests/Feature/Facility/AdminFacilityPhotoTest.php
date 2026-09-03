<?php

use App\Models\FacilityPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->staff = User::factory()->staff()->create();
});

test('guests are redirected from facility photos admin', function () {
    $this->get(route('admin.facility-photos.index'))->assertRedirect(route('login'));
});

test('staff can view the facility photos page', function () {
    FacilityPhoto::factory()->count(2)->create();

    $this->actingAs($this->staff)->get(route('admin.facility-photos.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/facility-photos/Index')->has('photos', 2));
});

test('staff can upload multiple photos', function () {
    $this->actingAs($this->staff)->post(route('admin.facility-photos.store'), [
        'photos' => [
            UploadedFile::fake()->image('a.jpg', 1200, 800),
            UploadedFile::fake()->image('b.png', 1000, 700),
        ],
    ])->assertRedirect(route('admin.facility-photos.index'))->assertSessionHasNoErrors();

    expect(FacilityPhoto::query()->count())->toBe(2);

    FacilityPhoto::query()->get()->each(function (FacilityPhoto $photo) {
        Storage::disk('public')->assertExists($photo->image_path);
        Storage::disk('public')->assertExists('facility/thumbs/'.basename($photo->image_path));
    });

    expect(FacilityPhoto::query()->orderBy('sort_order')->pluck('sort_order')->all())->toBe([1, 2]);
});

test('uploading requires at least one image', function () {
    $this->actingAs($this->staff)
        ->from(route('admin.facility-photos.index'))
        ->post(route('admin.facility-photos.store'), ['photos' => []])
        ->assertSessionHasErrors('photos');

    $this->actingAs($this->staff)
        ->post(route('admin.facility-photos.store'), ['photos' => [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')]])
        ->assertSessionHasErrors('photos.0');
});

test('staff can update a caption, reorder, and delete photos', function () {
    $first = FacilityPhoto::factory()->create(['sort_order' => 0]);
    $second = FacilityPhoto::factory()->create(['sort_order' => 1]);
    Storage::disk('public')->put($first->image_path, 'x');

    $this->actingAs($this->staff)->put(route('admin.facility-photos.update', $first), ['caption' => 'Batting cages'])
        ->assertRedirect(route('admin.facility-photos.index'));
    expect($first->refresh()->caption)->toBe('Batting cages');

    $this->actingAs($this->staff)->post(route('admin.facility-photos.reorder'), ['order' => [$second->id, $first->id]])
        ->assertRedirect(route('admin.facility-photos.index'));
    expect($second->refresh()->sort_order)->toBe(0)->and($first->refresh()->sort_order)->toBe(1);

    $this->actingAs($this->staff)->delete(route('admin.facility-photos.destroy', $first))
        ->assertRedirect(route('admin.facility-photos.index'));
    $this->assertModelMissing($first);
    Storage::disk('public')->assertMissing($first->image_path);
});
