<?php

use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->staff = User::factory()->staff()->create();
});

test('guests are redirected from admin sponsors', function () {
    $this->get(route('admin.sponsors.index'))->assertRedirect(route('login'));
});

test('staff can view the sponsors index, create, and edit pages', function () {
    $sponsor = Sponsor::factory()->create();

    $this->actingAs($this->staff)->get(route('admin.sponsors.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/sponsors/Index')->has('sponsors.data', 1));
    $this->actingAs($this->staff)->get(route('admin.sponsors.create'))->assertOk();
    $this->actingAs($this->staff)->get(route('admin.sponsors.edit', $sponsor))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/sponsors/Edit')->where('sponsor.id', $sponsor->id));
});

test('staff can add a sponsor with a logo, placed at the end of the list', function () {
    Sponsor::factory()->create(['sort_order' => 5]);

    $this->actingAs($this->staff)->post(route('admin.sponsors.store'), [
        'name' => 'Smith Family Dental',
        'website_url' => 'https://smithdental.example',
        'is_active' => 1,
        'logo' => UploadedFile::fake()->image('logo.png', 1200, 400),
    ])->assertRedirect(route('admin.sponsors.index'))->assertSessionHasNoErrors();

    $sponsor = Sponsor::query()->where('name', 'Smith Family Dental')->firstOrFail();

    expect($sponsor->website_url)->toBe('https://smithdental.example')
        ->and($sponsor->logo_path)->toStartWith('sponsors/')
        ->and($sponsor->sort_order)->toBe(6);

    Storage::disk('public')->assertExists($sponsor->logo_path);
});

test('logos are scaled to fit without being cropped', function () {
    $this->actingAs($this->staff)->post(route('admin.sponsors.store'), [
        'name' => 'Wide Logo Co',
        'logo' => UploadedFile::fake()->image('logo.png', 1200, 300),
    ])->assertSessionHasNoErrors();

    $path = Sponsor::query()->firstOrFail()->logo_path;
    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));

    expect([$width, $height])->toBe([600, 150]);
});

test('adding a sponsor requires a name and logo, and the website must be a url', function () {
    $this->actingAs($this->staff)
        ->from(route('admin.sponsors.create'))
        ->post(route('admin.sponsors.store'), ['name' => '', 'website_url' => 'javascript:alert(1)'])
        ->assertRedirect(route('admin.sponsors.create'))
        ->assertSessionHasErrors(['name', 'logo', 'website_url']);
});

test('updating a sponsor keeps the logo unless a new one is uploaded', function () {
    $sponsor = Sponsor::factory()->create(['logo_path' => 'sponsors/old.webp']);
    Storage::disk('public')->put('sponsors/old.webp', 'x');

    $this->actingAs($this->staff)->put(route('admin.sponsors.update', $sponsor), [
        'name' => 'Renamed',
        'website_url' => '',
        'is_active' => 0,
    ])->assertRedirect(route('admin.sponsors.edit', $sponsor))->assertSessionHasNoErrors();

    $sponsor->refresh();
    expect($sponsor->name)->toBe('Renamed')
        ->and($sponsor->website_url)->toBeNull()
        ->and($sponsor->is_active)->toBeFalse()
        ->and($sponsor->logo_path)->toBe('sponsors/old.webp');

    $this->actingAs($this->staff)->put(route('admin.sponsors.update', $sponsor), [
        'name' => 'Renamed',
        'logo' => UploadedFile::fake()->image('new.png', 400, 200),
    ])->assertSessionHasNoErrors();

    $sponsor->refresh();
    expect($sponsor->logo_path)->not->toBe('sponsors/old.webp');
    Storage::disk('public')->assertMissing('sponsors/old.webp');
    Storage::disk('public')->assertExists($sponsor->logo_path);
});

test('deleting a sponsor removes its logo', function () {
    $sponsor = Sponsor::factory()->create(['logo_path' => 'sponsors/gone.webp']);
    Storage::disk('public')->put('sponsors/gone.webp', 'x');

    $this->actingAs($this->staff)->delete(route('admin.sponsors.destroy', $sponsor))
        ->assertRedirect(route('admin.sponsors.index'));

    $this->assertModelMissing($sponsor);
    Storage::disk('public')->assertMissing('sponsors/gone.webp');
});
