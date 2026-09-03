<?php

use App\Models\Coach;
use App\Models\SeoMeta;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->staff = User::factory()->staff()->create();
});

test('guests are redirected from admin teams', function () {
    $this->get(route('admin.teams.index'))->assertRedirect(route('login'));
});

test('staff can view the teams index', function () {
    Team::factory()->count(3)->create();

    $this->actingAs($this->staff)
        ->get(route('admin.teams.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/teams/Index')->has('teams.data', 3));
});

test('staff can view the create and edit pages', function () {
    $team = Team::factory()->create();

    $this->actingAs($this->staff)->get(route('admin.teams.create'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/teams/Create')->has('coaches'));

    $this->actingAs($this->staff)->get(route('admin.teams.edit', $team))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/teams/Edit')->where('team.id', $team->id));
});

test('staff can create a team with a photo and seo meta', function () {
    $coach = Coach::factory()->create();

    $response = $this->actingAs($this->staff)->post(route('admin.teams.store'), [
        'name' => 'Eagles 12U Navy',
        'division' => '12U',
        'season' => '2026',
        'description' => 'A great team.',
        'coach_id' => $coach->id,
        'is_active' => 1,
        'photo' => UploadedFile::fake()->image('team.jpg', 1200, 800),
        'seo' => [
            'title' => 'Eagles 12U Navy Travel Team',
            'description' => 'Custom description.',
        ],
        'seo_share_image' => UploadedFile::fake()->image('share.jpg', 1200, 630),
    ]);

    $response->assertRedirect(route('admin.teams.index'))->assertSessionHasNoErrors();

    $team = Team::query()->where('name', 'Eagles 12U Navy')->firstOrFail();

    expect($team->slug)->toBe('eagles-12u-navy')
        ->and($team->coach_id)->toBe($coach->id)
        ->and($team->is_active)->toBeTrue()
        ->and($team->photo_path)->toStartWith('teams/');

    Storage::disk('public')->assertExists($team->photo_path);
    Storage::disk('public')->assertExists('teams/thumbs/'.basename($team->photo_path));

    $meta = $team->seoMeta;
    expect($meta)->toBeInstanceOf(SeoMeta::class)
        ->and($meta->title)->toBe('Eagles 12U Navy Travel Team')
        ->and($meta->share_image_path)->toStartWith('share/');
});

test('creating a team validates required fields', function () {
    $this->actingAs($this->staff)
        ->from(route('admin.teams.create'))
        ->post(route('admin.teams.store'), ['name' => '', 'division' => '', 'coach_id' => 999])
        ->assertRedirect(route('admin.teams.create'))
        ->assertSessionHasErrors(['name', 'division', 'coach_id']);
});

test('staff can update a team and remove its photo', function () {
    $team = Team::factory()->create(['photo_path' => 'teams/old.webp']);
    Storage::disk('public')->put('teams/old.webp', 'x');
    Storage::disk('public')->put('teams/thumbs/old.webp', 'x');

    $this->actingAs($this->staff)->put(route('admin.teams.update', $team), [
        'name' => 'Renamed Team',
        'division' => '13U',
        'is_active' => 0,
        'remove_photo' => 1,
    ])->assertRedirect(route('admin.teams.edit', $team->fresh()))->assertSessionHasNoErrors();

    $team->refresh();

    expect($team->name)->toBe('Renamed Team')
        ->and($team->division)->toBe('13U')
        ->and($team->is_active)->toBeFalse()
        ->and($team->photo_path)->toBeNull();

    Storage::disk('public')->assertMissing('teams/old.webp');
    Storage::disk('public')->assertMissing('teams/thumbs/old.webp');
});

test('staff can delete a team and its files', function () {
    $team = Team::factory()->create(['photo_path' => 'teams/gone.webp']);
    Storage::disk('public')->put('teams/gone.webp', 'x');
    $team->seoMeta()->create(['title' => 'x']);

    $this->actingAs($this->staff)->delete(route('admin.teams.destroy', $team))
        ->assertRedirect(route('admin.teams.index'));

    $this->assertModelMissing($team);
    Storage::disk('public')->assertMissing('teams/gone.webp');
    expect(SeoMeta::query()->count())->toBe(0);
});
