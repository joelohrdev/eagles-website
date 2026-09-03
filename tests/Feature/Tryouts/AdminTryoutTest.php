<?php

use App\Models\SeoMeta;
use App\Models\Tryout;
use App\Models\TryoutRegistration;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->staff = User::factory()->staff()->create();
});

test('guests are redirected from the admin tryouts index', function () {
    $this->get(route('admin.tryouts.index'))->assertRedirect(route('login'));
});

test('staff can view the tryouts index with registration counts', function () {
    $tryout = Tryout::factory()->create();
    TryoutRegistration::factory()->count(3)->for($tryout)->create();

    $this->actingAs($this->staff)
        ->get(route('admin.tryouts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/tryouts/Index')
            ->has('tryouts.data', 1)
            ->where('tryouts.data.0.registrations_count', 3)
            ->where('tryouts.data.0.registration_state', 'open')
        );
});

test('staff can view the create page', function () {
    $this->actingAs($this->staff)
        ->get(route('admin.tryouts.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/tryouts/Create'));
});

test('staff can create a tryout with an image and seo meta', function () {
    Storage::fake('public');

    $response = $this->actingAs($this->staff)->post(route('admin.tryouts.store'), [
        'title' => '13U Tryouts',
        'division' => '13U',
        'location' => 'Eagles Facility',
        'description' => 'Bring your gear.',
        'event_at' => now()->addMonth()->format('Y-m-d\TH:i'),
        'registration_opens_at' => now()->subDay()->format('Y-m-d\TH:i'),
        'registration_closes_at' => now()->addWeeks(3)->format('Y-m-d\TH:i'),
        'capacity' => 30,
        'is_published' => 1,
        'image' => UploadedFile::fake()->image('tryout.jpg', 1200, 800),
        'seo' => [
            'title' => 'Custom SEO title',
            'share_title' => 'Share me',
        ],
        'seo_share_image' => UploadedFile::fake()->image('share.jpg', 1200, 630),
    ]);

    $tryout = Tryout::first();

    $response->assertRedirect(route('admin.tryouts.edit', $tryout));

    expect($tryout)->not->toBeNull()
        ->and($tryout->slug)->toBe('13u-tryouts')
        ->and($tryout->is_published)->toBeTrue()
        ->and($tryout->capacity)->toBe(30)
        ->and($tryout->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($tryout->image_path);

    $meta = SeoMeta::query()->where('metable_type', Tryout::class)->where('metable_id', $tryout->id)->first();
    expect($meta)->not->toBeNull()
        ->and($meta->title)->toBe('Custom SEO title')
        ->and($meta->share_title)->toBe('Share me')
        ->and($meta->share_image_path)->not->toBeNull();
});

test('creating a tryout validates required fields and the registration window', function () {
    $this->actingAs($this->staff)
        ->from(route('admin.tryouts.create'))
        ->post(route('admin.tryouts.store'), [
            'title' => '',
            'division' => '',
            'event_at' => 'not-a-date',
            'registration_opens_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            'registration_closes_at' => now()->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect(route('admin.tryouts.create'))
        ->assertSessionHasErrors(['title', 'division', 'event_at', 'registration_closes_at']);
});

test('staff can view the edit page with seo meta', function () {
    $tryout = Tryout::factory()->create();
    $tryout->seoMeta()->create(['title' => 'SEO title']);

    $this->actingAs($this->staff)
        ->get(route('admin.tryouts.edit', $tryout))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/tryouts/Edit')
            ->where('tryout.id', $tryout->id)
            ->where('seo.title', 'SEO title')
            ->where('publicUrl', route('tryouts.show', $tryout))
        );
});

test('staff can update a tryout and replace its image', function () {
    Storage::fake('public');
    $tryout = Tryout::factory()->create(['image_path' => 'tryouts/old.webp']);
    Storage::disk('public')->put('tryouts/old.webp', 'x');
    Storage::disk('public')->put('tryouts/thumbs/old.webp', 'x');

    $this->actingAs($this->staff)
        ->put(route('admin.tryouts.update', $tryout), [
            'title' => 'Updated title',
            'division' => '14U',
            'event_at' => now()->addMonths(2)->format('Y-m-d\TH:i'),
            'is_published' => 0,
            'image' => UploadedFile::fake()->image('new.jpg', 1000, 700),
        ])
        ->assertRedirect(route('admin.tryouts.edit', $tryout->fresh()));

    $tryout->refresh();

    expect($tryout->title)->toBe('Updated title')
        ->and($tryout->division)->toBe('14U')
        ->and($tryout->is_published)->toBeFalse()
        ->and($tryout->image_path)->not->toBe('tryouts/old.webp');

    Storage::disk('public')->assertMissing('tryouts/old.webp');
    Storage::disk('public')->assertExists($tryout->image_path);
});

test('staff can change only the registration dates and have them stick', function () {
    $tryout = Tryout::factory()->create([
        'registration_opens_at' => now()->subWeek(),
        'registration_closes_at' => now()->addWeek(),
    ]);

    $opens = now()->addDays(2)->startOfMinute();
    $closes = now()->addDays(20)->startOfMinute();

    $this->actingAs($this->staff)
        ->put(route('admin.tryouts.update', $tryout), [
            'title' => $tryout->title,
            'division' => $tryout->division,
            'event_at' => $tryout->event_at->format('Y-m-d\TH:i'),
            'is_published' => 1,
            'registration_opens_at' => $opens->format('Y-m-d\TH:i'),
            'registration_closes_at' => $closes->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.tryouts.edit', $tryout->fresh()));

    $tryout->refresh();

    expect($tryout->registration_opens_at->format('Y-m-d H:i'))->toBe($opens->format('Y-m-d H:i'))
        ->and($tryout->registration_closes_at->format('Y-m-d H:i'))->toBe($closes->format('Y-m-d H:i'));
});

/**
 * The admin form spans tabs. When a tab's inputs are missing from the submit the
 * request fails on fields the editor cannot see, which is what "Save does nothing"
 * looked like — so the errors must at least come back keyed by field.
 */
test('a submit missing another tabs fields fails on those fields', function () {
    $tryout = Tryout::factory()->create();

    $this->actingAs($this->staff)
        ->from(route('admin.tryouts.edit', $tryout))
        ->put(route('admin.tryouts.update', $tryout), [
            'registration_opens_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'registration_closes_at' => now()->addWeek()->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect(route('admin.tryouts.edit', $tryout))
        ->assertSessionHasErrors(['title', 'division', 'event_at']);

    expect($tryout->refresh()->registration_opens_at->isPast())->toBeTrue();
});

test('staff can remove a tryout image on update', function () {
    Storage::fake('public');
    $tryout = Tryout::factory()->create(['image_path' => 'tryouts/old.webp']);
    Storage::disk('public')->put('tryouts/old.webp', 'x');

    $this->actingAs($this->staff)->put(route('admin.tryouts.update', $tryout), [
        'title' => $tryout->title,
        'division' => $tryout->division,
        'event_at' => $tryout->event_at->format('Y-m-d\TH:i'),
        'remove_image' => 1,
    ]);

    expect($tryout->refresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing('tryouts/old.webp');
});

test('staff can delete a tryout and its image', function () {
    Storage::fake('public');
    $tryout = Tryout::factory()->create(['image_path' => 'tryouts/gone.webp']);
    Storage::disk('public')->put('tryouts/gone.webp', 'x');

    $this->actingAs($this->staff)
        ->delete(route('admin.tryouts.destroy', $tryout))
        ->assertRedirect(route('admin.tryouts.index'));

    expect(Tryout::count())->toBe(0);
    Storage::disk('public')->assertMissing('tryouts/gone.webp');
});
