<?php

use App\Models\Camp;
use App\Models\CampRegistration;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->staff = User::factory()->staff()->create();
});

test('guests are redirected from admin camps', function () {
    $this->get(route('admin.camps.index'))->assertRedirect(route('login'));
});

test('staff can view the camps index', function () {
    Camp::factory()->count(3)->create();

    $this->actingAs($this->staff)
        ->get(route('admin.camps.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/camps/Index')
            ->has('camps.data', 3)
            ->has('camps.data.0', fn ($camp) => $camp
                ->hasAll(['id', 'name', 'slug', 'starts_at', 'price', 'is_published', 'registration_state', 'paid_registrations_count', 'active_registrations_count', 'spots_remaining'])
                ->etc()
            )
        );
});

test('staff can view the create and edit pages', function () {
    $camp = Camp::factory()->create();

    $this->actingAs($this->staff)->get(route('admin.camps.create'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/camps/Create'));

    $this->actingAs($this->staff)->get(route('admin.camps.edit', $camp))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/camps/Edit')
            ->where('camp.id', $camp->id)
            ->where('camp.schedule.sessions.0.price', number_format($camp->price / 100, 2, '.', ''))
            ->has('seo')
        );
});

test('staff can create a camp with an image and seo meta', function () {
    Storage::fake('public');

    $response = $this->actingAs($this->staff)->post(route('admin.camps.store'), [
        'name' => 'Winter Hitting Camp',
        'description' => 'Two hours of hitting.',
        'location' => 'Eagles Facility',
        'age_range' => '9U–12U',
        'sessions' => [[
            'name' => 'Hitting',
            'price' => '125.50',
            'days' => [['date' => now()->addMonth()->toDateString(), 'times' => [['starts_at' => '09:00', 'ends_at' => '11:00']]]],
        ]],
        'capacity' => 20,
        'registration_opens_at' => now()->format('Y-m-d\TH:i'),
        'registration_closes_at' => now()->addMonth()->subDay()->format('Y-m-d\TH:i'),
        'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
        'is_published' => 1,
        'image' => UploadedFile::fake()->image('camp.jpg', 1200, 800),
        'seo' => [
            'title' => 'Winter Hitting Camp 2026',
            'description' => 'Best hitting camp around.',
            'share_title' => 'Join the Winter Hitting Camp',
        ],
        'seo_share_image' => UploadedFile::fake()->image('share.jpg', 1200, 630),
    ]);

    $camp = Camp::query()->where('name', 'Winter Hitting Camp')->firstOrFail();

    $response->assertRedirect(route('admin.camps.edit', $camp))->assertSessionHasNoErrors();

    expect($camp->price)->toBe(12550)
        ->and($camp->slug)->toBe('winter-hitting-camp')
        ->and($camp->is_published)->toBeTrue()
        ->and($camp->capacity)->toBe(20)
        ->and($camp->image_path)->not->toBeNull()
        ->and($camp->seoMeta->title)->toBe('Winter Hitting Camp 2026')
        ->and($camp->seoMeta->share_title)->toBe('Join the Winter Hitting Camp')
        ->and($camp->seoMeta->share_image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($camp->image_path);
    Storage::disk('public')->assertExists($camp->seoMeta->share_image_path);
});

test('creating a camp validates required fields and date ordering', function () {
    $this->actingAs($this->staff)->post(route('admin.camps.store'), [
        'name' => '',
        'sessions' => [[
            'name' => 'Hitting',
            'price' => '-5',
            'days' => [['date' => now()->addMonth()->toDateString(), 'times' => [['starts_at' => '11:00', 'ends_at' => '09:00']]]],
        ]],
        'registration_opens_at' => now()->addWeek()->format('Y-m-d\TH:i'),
        'registration_closes_at' => now()->format('Y-m-d\TH:i'),
        'youtube_url' => 'not-a-url',
    ])->assertSessionHasErrors(['name', 'sessions.0.price', 'registration_closes_at', 'youtube_url']);
});

test('staff can update a camp and remove its image', function () {
    Storage::fake('public');
    Storage::disk('public')->put('camps/old.webp', 'x');
    Storage::disk('public')->put('camps/thumbs/old.webp', 'x');

    $camp = Camp::factory()->create(['image_path' => 'camps/old.webp', 'price' => 5000]);

    $this->actingAs($this->staff)->put(route('admin.camps.update', $camp), [
        'name' => 'Renamed Camp',
        'sessions' => [[
            'name' => 'Renamed Camp',
            'price' => '0',
            'days' => [['date' => now()->addMonth()->toDateString(), 'times' => [['starts_at' => '09:00']]]],
        ]],
        'is_published' => 0,
        'remove_image' => 1,
    ])->assertRedirect(route('admin.camps.edit', $camp))->assertSessionHasNoErrors();

    $camp->refresh();

    expect($camp->name)->toBe('Renamed Camp')
        ->and($camp->price)->toBe(0)
        ->and($camp->is_published)->toBeFalse()
        ->and($camp->image_path)->toBeNull();

    Storage::disk('public')->assertMissing('camps/old.webp');
    Storage::disk('public')->assertMissing('camps/thumbs/old.webp');
});

test('staff can delete a camp, along with its registrations', function () {
    $camp = Camp::factory()->create();
    CampRegistration::factory()->for($camp)->create();

    $this->actingAs($this->staff)->delete(route('admin.camps.destroy', $camp))
        ->assertRedirect(route('admin.camps.index'));

    $this->assertModelMissing($camp);
    expect(CampRegistration::count())->toBe(0);
});
