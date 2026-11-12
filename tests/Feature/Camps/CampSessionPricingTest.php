<?php

use App\Actions\Orders\MarkOrderPaid;
use App\Contracts\PaymentGateway;
use App\Enums\CouponScope;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\RegistrationStatus;
use App\Mail\CampRegistrationConfirmation;
use App\Mail\OrderReceipt;
use App\Models\Camp;
use App\Models\CampAgeGroup;
use App\Models\CampDayTime;
use App\Models\CampRegistration;
use App\Models\CampSession;
use App\Models\CampSessionDay;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Services\Payments\FakeGateway;
use Illuminate\Support\Facades\Mail;

/**
 * The Thanksgiving camp: Pitching (Nov 21 + 23, $125, $75 a day) and Fielding & Hitting
 * (Nov 24 + 25, $125, whole session only), $225 for both, two age groups.
 *
 * @return array{camp: Camp, young: CampAgeGroup, older: CampAgeGroup, pitching: CampSession, fielding: CampSession}
 */
function thanksgivingCamp(?int $olderCapacity = null): array
{
    $camp = Camp::factory()->withoutSchedule()->create(['name' => 'Thanksgiving Camp', 'price' => 7500, 'bundle_price' => 22500, 'capacity' => null]);
    $young = CampAgeGroup::factory()->for($camp)->create(['name' => '8–12U']);
    $older = CampAgeGroup::factory()->for($camp)->create(['name' => '13–17U', 'capacity' => $olderCapacity]);

    $pitching = CampSession::factory()->for($camp)->singleDays(7500)->create(['name' => 'Pitching', 'price' => 12500]);
    $fielding = CampSession::factory()->for($camp)->create(['name' => 'Fielding & Hitting', 'price' => 12500]);

    foreach ([[$pitching, '2026-11-21', null], [$pitching, '2026-11-23', null], [$fielding, '2026-11-24', 'Fielding'], [$fielding, '2026-11-25', 'Hitting']] as [$session, $date, $label]) {
        $day = CampSessionDay::factory()->for($session, 'session')->create(['date' => $date, 'label' => $label]);
        CampDayTime::factory()->for($day, 'day')->create(['camp_age_group_id' => $young->id, 'starts_at' => '17:30:00', 'ends_at' => '19:00:00']);
        CampDayTime::factory()->for($day, 'day')->create(['camp_age_group_id' => $older->id, 'starts_at' => '19:00:00', 'ends_at' => '20:30:00']);
    }

    return ['camp' => $camp->fresh(), 'young' => $young, 'older' => $older, 'pitching' => $pitching, 'fielding' => $fielding];
}

/**
 * @param  iterable<CampSession>  $sessions
 * @return list<int>
 */
function dayIdsOf(iterable $sessions): array
{
    return collect($sessions)->flatMap(fn (CampSession $session) => $session->days()->pluck('id'))->all();
}

function eaglesCoupon(): Coupon
{
    return Coupon::factory()->fixedPerItem(2500)->scope(CouponScope::Camps)->create(['code' => 'Thanks30']);
}

function quote(Camp $camp, array $payload)
{
    return test()->postJson(route('camps.quote', $camp), $payload);
}

test('both sessions use the multi-session price', function () {
    ['camp' => $camp, 'young' => $young, 'pitching' => $pitching, 'fielding' => $fielding] = thanksgivingCamp();

    quote($camp, ['days' => dayIdsOf([$pitching, $fielding]), 'camp_age_group_id' => $young->id])
        ->assertOk()
        ->assertJson(['subtotal' => 25000, 'discount' => 2500, 'discount_label' => 'Multi-session price', 'total' => 22500]);
});

test('the Eagles code takes $25 off each session instead of the multi-session price', function (bool $both, int $total) {
    ['camp' => $camp, 'young' => $young, 'pitching' => $pitching, 'fielding' => $fielding] = thanksgivingCamp();
    eaglesCoupon();

    quote($camp, ['days' => dayIdsOf($both ? [$pitching, $fielding] : [$pitching]), 'camp_age_group_id' => $young->id, 'coupon_code' => 'thanks30'])
        ->assertOk()
        ->assertJson(['discount_label' => 'THANKS30', 'coupon_code' => 'THANKS30', 'total' => $total]);
})->with([
    'one session' => [false, 10000],
    'both sessions' => [true, 20000],
]);

test('a code worth less than the multi-session price is not applied', function () {
    ['camp' => $camp, 'young' => $young, 'pitching' => $pitching, 'fielding' => $fielding] = thanksgivingCamp();
    Coupon::factory()->percent(5)->create(['code' => 'SMALL']);

    quote($camp, ['days' => dayIdsOf([$pitching, $fielding]), 'camp_age_group_id' => $young->id, 'coupon_code' => 'SMALL'])
        ->assertOk()
        ->assertJson(['discount_label' => 'Multi-session price', 'coupon_code' => null, 'total' => 22500])
        ->assertJsonPath('coupon_note', fn (?string $note) => $note !== null);
});

test('single days use the day price, and only where the session offers it', function () {
    ['camp' => $camp, 'young' => $young, 'pitching' => $pitching, 'fielding' => $fielding] = thanksgivingCamp();

    quote($camp, ['days' => [$pitching->days()->first()->id], 'camp_age_group_id' => $young->id])
        ->assertOk()
        ->assertJson(['total' => 7500, 'discount' => 0]);

    quote($camp, ['days' => [$fielding->days()->first()->id], 'camp_age_group_id' => $young->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['days' => 'Fielding & Hitting can only be booked for all of its days.']);
});

test('the selection must be days of this camp, with an age group when the camp has them', function () {
    ['camp' => $camp, 'pitching' => $pitching] = thanksgivingCamp();
    $otherDay = Camp::factory()->create()->days()->first();

    quote($camp, ['days' => dayIdsOf([$pitching])])->assertJsonValidationErrors('camp_age_group_id');
    quote($camp, ['days' => [$otherDay->id], 'camp_age_group_id' => $camp->ageGroups()->first()->id])->assertJsonValidationErrors('days');
    quote($camp, ['days' => []])->assertJsonValidationErrors('days');
});

test('unusable coupon codes are rejected with a reason', function (Closure $makeCoupon, string $message) {
    ['camp' => $camp, 'young' => $young, 'pitching' => $pitching] = thanksgivingCamp();
    $makeCoupon();

    quote($camp, ['days' => dayIdsOf([$pitching]), 'camp_age_group_id' => $young->id, 'coupon_code' => 'CODE'])
        ->assertJsonValidationErrors(['coupon_code' => $message]);
})->with([
    'unknown' => [fn () => null, 'That coupon code doesn’t exist. Check the spelling and try again.'],
    'inactive' => [fn () => Coupon::factory()->inactive()->create(['code' => 'CODE']), 'That coupon code is not active.'],
    'expired' => [fn () => Coupon::factory()->expired()->create(['code' => 'CODE']), 'That coupon code has expired.'],
    'merch only' => [fn () => Coupon::factory()->scope(CouponScope::Merch)->create(['code' => 'CODE']), 'That coupon code can’t be used for camps.'],
    'another camp' => [fn () => Coupon::factory()->hasAttached(Camp::factory())->create(['code' => 'CODE']), 'That coupon code can’t be used for this camp.'],
    'used up' => [fn () => Order::factory()->for(Coupon::factory()->state(['code' => 'CODE', 'max_redemptions' => 1]))->paid()->create(), 'That coupon code has been used up.'],
]);

test('cancelled and refunded orders give their coupon use back', function () {
    ['camp' => $camp, 'young' => $young, 'pitching' => $pitching] = thanksgivingCamp();
    $coupon = Coupon::factory()->create(['code' => 'ONCE', 'max_redemptions' => 1]);
    Order::factory()->for($coupon)->create(['status' => OrderStatus::Cancelled]);
    Order::factory()->for($coupon)->create(['status' => OrderStatus::Refunded]);

    quote($camp, ['days' => dayIdsOf([$pitching]), 'camp_age_group_id' => $young->id, 'coupon_code' => 'ONCE'])->assertOk();
});

test('a full age group slot blocks that day for the group but not for others', function () {
    ['camp' => $camp, 'young' => $young, 'older' => $older, 'pitching' => $pitching] = thanksgivingCamp(olderCapacity: 1);
    $day = $pitching->days()->first();
    CampRegistration::factory()->for($camp)->forDays([$day])->create(['camp_age_group_id' => $older->id]);

    quote($camp, ['days' => [$day->id], 'camp_age_group_id' => $older->id])
        ->assertJsonValidationErrors(['days' => "{$day->title()} is full for 13–17U."]);
    quote($camp, ['days' => [$day->id], 'camp_age_group_id' => $young->id])->assertOk();
    quote($camp, ['days' => [$pitching->days->last()->id], 'camp_age_group_id' => $older->id])->assertOk();
});

test('registering with the Eagles code records the sessions, days, age group and discount', function () {
    Mail::fake();
    ['camp' => $camp, 'older' => $older, 'pitching' => $pitching, 'fielding' => $fielding] = thanksgivingCamp();
    $coupon = eaglesCoupon();

    $this->post(route('camps.register.store', $camp), validCampRegistration($camp, [
        'days' => dayIdsOf([$pitching, $fielding]),
        'camp_age_group_id' => $older->id,
        'coupon_code' => 'Thanks30',
    ]))->assertSessionHasNoErrors();

    $order = Order::query()->with('items')->sole();
    $registration = CampRegistration::query()->sole();

    expect($order->type)->toBe(OrderType::Camp)
        ->and($order->subtotal)->toBe(25000)
        ->and($order->discount)->toBe(5000)
        ->and($order->total)->toBe(20000)
        ->and($order->coupon_id)->toBe($coupon->id)
        ->and($order->coupon_code)->toBe('THANKS30')
        ->and($order->items->pluck('unit_price')->all())->toBe([12500, 12500])
        ->and($order->items->first()->description)->toBe('Pitching — Sat, Nov 21 & Mon, Nov 23 — Jamie Rivera')
        ->and($registration->camp_age_group_id)->toBe($older->id)
        ->and($registration->days()->pluck('camp_session_days.id')->sort()->values()->all())->toBe(collect(dayIdsOf([$pitching, $fielding]))->sort()->values()->all())
        ->and($registration->status)->toBe(RegistrationStatus::PendingPayment);
});

test('a coupon that covers the whole price confirms the registration without Stripe', function () {
    Mail::fake();
    ['camp' => $camp, 'young' => $young, 'pitching' => $pitching] = thanksgivingCamp();
    Coupon::factory()->percent(100)->create(['code' => 'COACH']);

    $this->post(route('camps.register.store', $camp), validCampRegistration($camp, [
        'days' => dayIdsOf([$pitching]),
        'camp_age_group_id' => $young->id,
        'coupon_code' => 'COACH',
    ]))->assertRedirect(route('camps.show', ['camp' => $camp, 'registered' => 1]));

    $order = Order::query()->sole();

    expect($order->total)->toBe(0)
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and(CampRegistration::query()->sole()->status)->toBe(RegistrationStatus::Paid)
        ->and(app(PaymentGateway::class))->toBeInstanceOf(FakeGateway::class)
        ->and(app(PaymentGateway::class)->sessions)->toBe([]);

    Mail::assertQueued(OrderReceipt::class);
});

test('a full slot is rechecked when the registration is submitted', function () {
    ['camp' => $camp, 'older' => $older, 'pitching' => $pitching] = thanksgivingCamp(olderCapacity: 1);
    CampRegistration::factory()->for($camp)->forDays($pitching->days)->create(['camp_age_group_id' => $older->id]);

    $this->post(route('camps.register.store', $camp), validCampRegistration($camp, [
        'days' => dayIdsOf([$pitching]),
        'camp_age_group_id' => $older->id,
    ]))->assertSessionHasErrors('days');

    expect(CampRegistration::count())->toBe(1);
});

test('staff can filter a camp roster by day and age group', function () {
    ['camp' => $camp, 'young' => $young, 'older' => $older, 'pitching' => $pitching, 'fielding' => $fielding] = thanksgivingCamp();
    $saturday = $pitching->days->first();
    CampRegistration::factory()->for($camp)->forDays([$saturday])->create(['player_first_name' => 'Young', 'camp_age_group_id' => $young->id]);
    CampRegistration::factory()->for($camp)->forDays([$saturday])->create(['player_first_name' => 'Older', 'camp_age_group_id' => $older->id]);
    CampRegistration::factory()->for($camp)->forDays($fielding->days)->create(['player_first_name' => 'Fielder', 'camp_age_group_id' => $older->id]);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.camps.registrations.index', ['camp' => $camp, 'day' => $saturday->id, 'age_group' => $older->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('registrations.data', 1)
            ->where('registrations.data.0.player_first_name', 'Older')
            ->where('registrations.data.0.age_group', '13–17U')
            ->where('registrations.data.0.schedule', ['Pitching: Sat, Nov 21 · 7:00 PM – 8:30 PM']));
});

test('the receipt lists the booked days at the player’s times and the discount', function () {
    ['camp' => $camp, 'older' => $older, 'pitching' => $pitching] = thanksgivingCamp();
    eaglesCoupon();
    Mail::fake();

    $this->post(route('camps.register.store', $camp), validCampRegistration($camp, [
        'days' => dayIdsOf([$pitching]),
        'camp_age_group_id' => $older->id,
        'coupon_code' => 'thanks30',
    ]));

    (new OrderReceipt(Order::query()->sole()))
        ->assertSeeInHtml('13–17U')
        ->assertSeeInHtml('Pitching: Sat, Nov 21 · 7:00 PM – 8:30 PM')
        ->assertSeeInHtml('Pitching: Mon, Nov 23 · 7:00 PM – 8:30 PM')
        ->assertSeeInHtml('Discount (THANKS30)')
        ->assertSeeInHtml('$100.00');
});

test('a paid camp sign-up emails the family their sessions, days and times once payment goes through', function () {
    Mail::fake();
    ['camp' => $camp, 'older' => $older, 'pitching' => $pitching, 'fielding' => $fielding] = thanksgivingCamp();
    $camp->update(['location' => 'Eagles Training Facility', 'address' => '1234 Main St, Naperville, IL 60540']);

    $this->post(route('camps.register.store', $camp), validCampRegistration($camp, [
        'days' => dayIdsOf([$pitching, $fielding]),
        'camp_age_group_id' => $older->id,
    ]));
    Mail::assertNotQueued(CampRegistrationConfirmation::class);

    $order = Order::query()->sole();
    app(MarkOrderPaid::class)->handle($order);
    app(MarkOrderPaid::class)->handle($order->refresh());

    Mail::assertQueued(CampRegistrationConfirmation::class, 1);
    Mail::assertQueued(CampRegistrationConfirmation::class, fn ($mail) => $mail->hasTo('alex@example.com'));

    (new CampRegistrationConfirmation(CampRegistration::query()->sole()))
        ->assertSeeInOrderInHtml(['13–17U', 'Pitching', 'Sat, Nov 21', '7:00 PM – 8:30 PM', 'Mon, Nov 23', 'Fielding &amp; Hitting', 'Tue, Nov 24 · Fielding', 'Wed, Nov 25 · Hitting'], escape: false)
        ->assertSeeInHtml('1234 Main St, Naperville, IL 60540')
        ->assertSeeInHtml('Get directions')
        ->assertSeeInHtml($order->number)
        ->assertSeeInHtml('$225.00');
});

test('a sign-up a coupon covers in full gets one confirmation email', function () {
    Mail::fake();
    ['camp' => $camp, 'young' => $young, 'pitching' => $pitching] = thanksgivingCamp();
    Coupon::factory()->percent(100)->create(['code' => 'COACH']);

    $this->post(route('camps.register.store', $camp), validCampRegistration($camp, [
        'days' => dayIdsOf([$pitching]),
        'camp_age_group_id' => $young->id,
        'coupon_code' => 'COACH',
    ]));

    Mail::assertQueued(CampRegistrationConfirmation::class, 1);
});
