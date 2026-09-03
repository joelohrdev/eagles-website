<?php

use App\Models\Tryout;
use App\Models\TryoutRegistration;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->staff = User::factory()->staff()->create();
    $this->tryout = Tryout::factory()->create();
});

test('staff can view registrations for a tryout', function () {
    TryoutRegistration::factory()->count(2)->for($this->tryout)->create();

    $this->actingAs($this->staff)
        ->get(route('admin.tryouts.registrations.index', $this->tryout))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/tryouts/Registrations')
            ->where('tryout.id', $this->tryout->id)
            ->has('registrations.data', 2)
        );
});

test('registrations can be searched by name or email', function () {
    TryoutRegistration::factory()->for($this->tryout)->create(['player_first_name' => 'Zed', 'player_last_name' => 'Alpha', 'email' => 'zed@example.com']);
    TryoutRegistration::factory()->for($this->tryout)->create(['player_first_name' => 'Amy', 'player_last_name' => 'Beta', 'email' => 'amy@example.com']);

    $this->actingAs($this->staff)
        ->get(route('admin.tryouts.registrations.index', ['tryout' => $this->tryout, 'q' => 'zed']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('registrations.data', 1)
            ->where('registrations.data.0.player_first_name', 'Zed')
            ->where('filters.q', 'zed')
        );
});

test('staff can export registrations as csv', function () {
    TryoutRegistration::factory()->for($this->tryout)->create([
        'player_first_name' => 'Casey',
        'player_last_name' => 'Jones',
        'email' => 'casey@example.com',
    ]);

    $response = $this->actingAs($this->staff)->get(route('admin.tryouts.registrations.export', $this->tryout));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();

    expect($content)->toContain('Player,Birthdate')
        ->and($content)->toContain('Casey Jones')
        ->and($content)->toContain('casey@example.com');
});

test('staff can delete a registration', function () {
    $registration = TryoutRegistration::factory()->for($this->tryout)->create();

    $this->actingAs($this->staff)
        ->delete(route('admin.tryouts.registrations.destroy', [$this->tryout, $registration]))
        ->assertRedirect(route('admin.tryouts.registrations.index', $this->tryout));

    expect(TryoutRegistration::count())->toBe(0);
});

test('a registration cannot be deleted through a different tryout', function () {
    $registration = TryoutRegistration::factory()->for($this->tryout)->create();
    $other = Tryout::factory()->create();

    $this->actingAs($this->staff)
        ->delete(route('admin.tryouts.registrations.destroy', [$other, $registration]))
        ->assertNotFound();

    expect(TryoutRegistration::count())->toBe(1);
});
