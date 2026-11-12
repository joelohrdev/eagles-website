<?php

use App\Models\Camp;
use App\Models\CampCoach;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->staff = User::factory()->staff()->create();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function campWithCoachesForm(array $coaches, array $overrides = []): array
{
    return [
        'name' => 'Thanksgiving Camp',
        'is_published' => 0,
        'coaches' => $coaches,
        ...$overrides,
    ];
}

test('staff can add coaches with photos to a camp, in order', function () {
    $this->actingAs($this->staff)->post(route('admin.camps.store'), campWithCoachesForm([
        ['name' => 'Jordan Lee', 'title' => 'Pitching Instructor', 'bio' => 'Former D1 pitcher.', 'photo' => UploadedFile::fake()->image('jordan.jpg', 600, 600)],
        ['name' => 'Sam Ortiz', 'bio' => 'Hitting coach.'],
    ]))->assertSessionHasNoErrors();

    $camp = Camp::query()->sole();
    $coaches = $camp->coaches()->get();

    expect($coaches->pluck('name')->all())->toBe(['Jordan Lee', 'Sam Ortiz'])
        ->and($coaches->first()->title)->toBe('Pitching Instructor')
        ->and($coaches->first()->photo_path)->not->toBeNull()
        ->and($coaches->last()->photo_path)->toBeNull();

    Storage::disk('public')->assertExists($coaches->first()->photo_path);

    $this->actingAs($this->staff)->get(route('admin.camps.edit', $camp))
        ->assertInertia(fn ($page) => $page
            ->where('camp.coaches.0.name', 'Jordan Lee')
            ->where('camp.coaches.0.photo_url', $coaches->first()->photo_url)
            ->where('camp.coaches.1.name', 'Sam Ortiz'));
});

test('saving reorders and edits coaches, and removed coaches lose their photos', function () {
    $camp = Camp::factory()->unpublished()->create();
    Storage::disk('public')->put('camp-coaches/old.webp', 'x');
    $first = CampCoach::factory()->for($camp)->create(['name' => 'First', 'position' => 0, 'photo_path' => 'camp-coaches/old.webp']);
    $second = CampCoach::factory()->for($camp)->create(['name' => 'Second', 'position' => 1]);

    $this->actingAs($this->staff)->put(route('admin.camps.update', $camp), campWithCoachesForm([
        ['id' => $second->id, 'name' => 'Second (renamed)'],
        ['name' => 'Brand New', 'photo' => UploadedFile::fake()->image('new.png', 400, 400)],
    ], ['name' => $camp->name]))->assertSessionHasNoErrors();

    expect($camp->coaches()->pluck('name')->all())->toBe(['Second (renamed)', 'Brand New']);
    $this->assertModelMissing($first);
    Storage::disk('public')->assertMissing('camp-coaches/old.webp');
});

test('a coach photo can be removed', function () {
    $camp = Camp::factory()->unpublished()->create();
    Storage::disk('public')->put('camp-coaches/face.webp', 'x');
    $coach = CampCoach::factory()->for($camp)->create(['photo_path' => 'camp-coaches/face.webp']);

    $this->actingAs($this->staff)->put(route('admin.camps.update', $camp), campWithCoachesForm([
        ['id' => $coach->id, 'name' => $coach->name, 'remove_photo' => 1],
    ], ['name' => $camp->name]))->assertSessionHasNoErrors();

    expect($coach->refresh()->photo_path)->toBeNull();
    Storage::disk('public')->assertMissing('camp-coaches/face.webp');
});

test('a coach needs a name once anything is entered, and blank rows are ignored', function () {
    $this->actingAs($this->staff)->post(route('admin.camps.store'), campWithCoachesForm([
        ['name' => '', 'title' => '', 'bio' => ''],
        ['name' => '', 'bio' => 'Has a bio but no name.'],
    ]))->assertSessionHasErrors(['coaches.1.name'])->assertSessionDoesntHaveErrors(['coaches.0.name']);
});

test('camp coaches show on the camp page', function () {
    $camp = Camp::factory()->create();
    CampCoach::factory()->for($camp)->create(['name' => 'Jordan Lee', 'title' => 'Pitching Instructor', 'bio' => 'Former D1 pitcher.']);

    $this->get(route('camps.show', $camp))
        ->assertInertia(fn ($page) => $page
            ->where('camp.coaches.0.name', 'Jordan Lee')
            ->where('camp.coaches.0.title', 'Pitching Instructor')
            ->where('camp.coaches.0.bio', 'Former D1 pitcher.'));
});

test('deleting a camp deletes its coaches photos', function () {
    $camp = Camp::factory()->create();
    Storage::disk('public')->put('camp-coaches/face.webp', 'x');
    CampCoach::factory()->for($camp)->create(['photo_path' => 'camp-coaches/face.webp']);

    $this->actingAs($this->staff)->delete(route('admin.camps.destroy', $camp));

    expect(CampCoach::count())->toBe(0);
    Storage::disk('public')->assertMissing('camp-coaches/face.webp');
});
