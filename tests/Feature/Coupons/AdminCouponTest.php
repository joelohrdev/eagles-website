<?php

use App\Enums\CouponScope;
use App\Enums\CouponType;
use App\Models\Camp;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('only admins can manage coupons', function () {
    $this->get(route('admin.coupons.index'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->staff()->create())->get(route('admin.coupons.index'))->assertForbidden();
});

test('the index lists coupons with how often they were used', function () {
    $coupon = Coupon::factory()->fixedPerItem(2500)->create(['code' => 'THANKS30', 'max_redemptions' => 40]);
    Order::factory()->for($coupon)->paid()->count(2)->create();
    Order::factory()->for($coupon)->create(['status' => 'cancelled']);

    $this->actingAs($this->admin)->get(route('admin.coupons.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/coupons/Index')
            ->where('coupons.data.0.code', 'THANKS30')
            ->where('coupons.data.0.summary', '$25.00 off each item')
            ->where('coupons.data.0.redemptions_count', 2));
});

test('admins can create a fixed per-item coupon limited to a camp', function () {
    $camp = Camp::factory()->create();

    $this->actingAs($this->admin)->post(route('admin.coupons.store'), [
        'code' => ' thanks30 ',
        'description' => 'Eagles families',
        'type' => 'fixed_per_item',
        'value' => '25',
        'applies_to' => 'camps',
        'camp_ids' => [$camp->id],
        'is_active' => 1,
    ])->assertRedirect(route('admin.coupons.index'))->assertSessionHasNoErrors();

    $coupon = Coupon::query()->sole();

    expect($coupon->code)->toBe('THANKS30')
        ->and($coupon->type)->toBe(CouponType::FixedPerItem)
        ->and($coupon->value)->toBe(2500)
        ->and($coupon->applies_to)->toBe(CouponScope::Camps)
        ->and($coupon->camps()->pluck('camps.id')->all())->toBe([$camp->id]);
});

test('percent coupons store whole percentages and merch coupons drop camp limits', function () {
    $coupon = Coupon::factory()->create(['code' => 'SPRING']);
    $coupon->camps()->attach(Camp::factory()->create());

    $this->actingAs($this->admin)->put(route('admin.coupons.update', $coupon), [
        'code' => 'SPRING',
        'type' => 'percent',
        'value' => '20',
        'applies_to' => 'merch',
        'is_active' => 0,
    ])->assertSessionHasNoErrors();

    $coupon->refresh();

    expect($coupon->value)->toBe(20)
        ->and($coupon->is_active)->toBeFalse()
        ->and($coupon->camps()->count())->toBe(0);
});

test('coupon validation rejects duplicate codes, oversized percentages and bad dates', function () {
    Coupon::factory()->create(['code' => 'TAKEN']);

    $this->actingAs($this->admin)->post(route('admin.coupons.store'), [
        'code' => 'taken',
        'type' => 'percent',
        'value' => '150',
        'applies_to' => 'all',
        'starts_at' => '2026-12-01T00:00',
        'expires_at' => '2026-11-01T00:00',
    ])->assertSessionHasErrors(['code', 'value', 'expires_at']);
});

test('deleting a coupon keeps the code on orders that used it', function () {
    $coupon = Coupon::factory()->create(['code' => 'GONE']);
    $order = Order::factory()->for($coupon)->paid()->create(['coupon_code' => 'GONE', 'discount' => 500]);

    $this->actingAs($this->admin)->delete(route('admin.coupons.destroy', $coupon))->assertRedirect(route('admin.coupons.index'));

    $this->assertModelMissing($coupon);
    expect($order->refresh()->coupon_id)->toBeNull()
        ->and($order->coupon_code)->toBe('GONE');
});

test('a camp coupon can be set to combine with the multi-session price, but a merch coupon never does', function (string $appliesTo, bool $combinable) {
    $this->actingAs($this->admin)->post(route('admin.coupons.store'), [
        'code' => 'STACK',
        'type' => 'percent',
        'value' => '20',
        'applies_to' => $appliesTo,
        'combinable_with_multi_session' => 1,
        'is_active' => 1,
    ])->assertSessionHasNoErrors();

    expect(Coupon::query()->sole()->combinable_with_multi_session)->toBe($combinable);
})->with([
    'camps' => ['camps', true],
    'merch' => ['merch', false],
]);

test('new coupons compete with the multi-session price unless the box is ticked', function () {
    $this->actingAs($this->admin)->post(route('admin.coupons.store'), [
        'code' => 'PLAIN',
        'type' => 'percent',
        'value' => '20',
        'applies_to' => 'camps',
        'is_active' => 1,
    ])->assertSessionHasNoErrors();

    expect(Coupon::query()->sole()->combinable_with_multi_session)->toBeFalse();
});

test('the price preview uses a sample camp when no camp is chosen', function (bool $combinable, int $bothTotal, ?string $note) {
    $this->actingAs($this->admin)->postJson(route('admin.coupons.preview'), [
        'type' => 'percent',
        'value' => '20',
        'combinable_with_multi_session' => $combinable,
    ])
        ->assertOk()
        ->assertJsonPath('camp', null)
        ->assertJsonPath('scenarios.0.label', '1 session')
        ->assertJsonPath('scenarios.0.total', 10000)
        ->assertJsonPath('scenarios.1.label', 'Both sessions')
        ->assertJsonPath('scenarios.1.total', $bothTotal)
        ->assertJsonPath('scenarios.1.note', $note);

    expect(Coupon::count())->toBe(0);
})->with([
    'not combinable' => [false, 20000, null],
    'combinable' => [true, 18000, null],
]);

test('the price preview uses the camp a coupon is limited to', function () {
    ['camp' => $camp] = thanksgivingCamp();
    $camp->update(['bundle_price' => 20000]);

    $this->actingAs($this->admin)->postJson(route('admin.coupons.preview'), [
        'type' => 'percent',
        'value' => '5',
        'camp_ids' => [$camp->id],
    ])
        ->assertOk()
        ->assertJsonPath('camp', 'Thanksgiving Camp')
        ->assertJsonPath('scenarios.1.total', 20000)
        ->assertJsonPath('scenarios.1.note', 'Multi-session discount applied — it’s a better deal than your coupon.');
});

test('only admins can preview coupon prices', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->postJson(route('admin.coupons.preview'), ['type' => 'percent', 'value' => '20'])
        ->assertForbidden();
});
