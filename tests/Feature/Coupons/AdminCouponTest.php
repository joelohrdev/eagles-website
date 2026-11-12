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
