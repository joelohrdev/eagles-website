<?php

use App\Models\Coach;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->staff = User::factory()->staff()->create();
});

dataset('reorderable lists', [
    'teams' => [Team::class, 'teams'],
    'coaches' => [Coach::class, 'coaches'],
    'products' => [Product::class, 'products'],
]);

test('guests cannot reorder a list', function (string $model, string $resource) {
    $record = $model::factory()->create();

    $this->post(route("admin.{$resource}.reorder"), ['order' => [$record->id]])
        ->assertRedirect(route('login'));
})->with('reorderable lists');

test('staff can drag records into a new order', function (string $model, string $resource) {
    $records = collect(range(0, 2))
        ->map(fn (int $position) => $model::factory()->create(['sort_order' => $position]));

    $reversed = $records->pluck('id')->reverse()->values()->all();

    $this->actingAs($this->staff)
        ->from(route("admin.{$resource}.index"))
        ->post(route("admin.{$resource}.reorder"), ['order' => $reversed])
        ->assertRedirect(route("admin.{$resource}.index"))
        ->assertSessionHasNoErrors();

    expect($model::query()->ordered()->pluck('id')->all())->toBe($reversed);
})->with('reorderable lists');

test('reordering one page leaves records on other pages in place', function (string $model, string $resource) {
    $records = collect(range(0, 3))
        ->map(fn (int $position) => $model::factory()->create(['sort_order' => $position]));

    [$first, $second, $third, $fourth] = $records->all();

    $this->actingAs($this->staff)
        ->post(route("admin.{$resource}.reorder"), ['order' => [$second->id, $first->id]])
        ->assertSessionHasNoErrors();

    expect($model::query()->ordered()->pluck('id')->all())
        ->toBe([$second->id, $first->id, $third->id, $fourth->id]);
})->with('reorderable lists');

test('reordering rejects ids that do not exist', function (string $model, string $resource) {
    $record = $model::factory()->create();

    $this->actingAs($this->staff)
        ->from(route("admin.{$resource}.index"))
        ->post(route("admin.{$resource}.reorder"), ['order' => [$record->id, 99999]])
        ->assertSessionHasErrors('order.1');
})->with('reorderable lists');

test('a new record is appended to the end of the order', function (string $model, string $resource, array $payload) {
    $model::factory()->create(['sort_order' => 7]);

    $this->actingAs($this->staff)
        ->post(route("admin.{$resource}.store"), $payload)
        ->assertSessionHasNoErrors();

    $created = $model::query()->latest('id')->firstOrFail();

    expect($created->sort_order)->toBe(8)
        ->and($model::query()->ordered()->pluck('id')->last())->toBe($created->id);
})->with([
    'teams' => [Team::class, 'teams', ['name' => 'Newest Team', 'division' => '10U']],
    'coaches' => [Coach::class, 'coaches', ['name' => 'Newest Coach']],
    'products' => [Product::class, 'products', ['name' => 'Newest Product', 'price' => '10.00']],
]);

test('the sort order cannot be set from a form', function () {
    $this->actingAs($this->staff)
        ->post(route('admin.teams.store'), [
            'name' => 'Manual Order Team',
            'division' => '10U',
            'sort_order' => 99,
        ])
        ->assertSessionHasNoErrors();

    expect(Team::query()->where('name', 'Manual Order Team')->firstOrFail()->sort_order)->toBe(1);
});
