<?php

use App\Actions\Orders\MarkOrderPaid;
use App\Contracts\PaymentGateway;
use App\Mail\CampRegistrationChanged;
use App\Models\CampRegistration;
use App\Models\CampSession;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

/**
 * Both Thanksgiving sessions booked and paid ($225 multi-session price, less any coupon).
 *
 * @return array{registration: CampRegistration, order: Order, pitching: CampSession, fielding: CampSession}
 */
function paidForBothSessions(?string $couponCode = null): array
{
    ['camp' => $camp, 'young' => $young, 'pitching' => $pitching, 'fielding' => $fielding] = thanksgivingCamp();
    test()->post(route('camps.register.store', $camp), validCampRegistration($camp, array_filter([
        'days' => dayIdsOf([$pitching, $fielding]),
        'camp_age_group_id' => $young->id,
        'coupon_code' => $couponCode,
    ])))->assertSessionHasNoErrors();
    $order = Order::query()->sole();
    app(MarkOrderPaid::class)->handle($order, 'pi_fake_1');

    return ['registration' => CampRegistration::query()->sole(), 'order' => $order->refresh(), 'pitching' => $pitching, 'fielding' => $fielding];
}

function dropDaysUrl(CampRegistration $registration, string $route = 'admin.camps.registrations.drop-days'): string
{
    return route($route, [$registration->camp, $registration]);
}

test('dropping a session from a multi-session booking refunds the difference from the single-session price', function () {
    Mail::fake();
    ['registration' => $registration, 'order' => $order, 'fielding' => $fielding] = paidForBothSessions();

    $this->actingAs($this->admin)->postJson(dropDaysUrl($registration, 'admin.camps.registrations.drop-days.preview'), ['days' => dayIdsOf([$fielding])])
        ->assertOk()
        ->assertJson(['paid' => 22500, 'kept_price' => 12500, 'suggested_refund' => 10000, 'max_refund' => 22500]);

    $this->actingAs($this->admin)->post(dropDaysUrl($registration), ['days' => dayIdsOf([$fielding]), 'refund' => '100.00'])
        ->assertSessionHasNoErrors();

    expect($registration->days()->pluck('camp_session_days.id')->all())->not->toContain(...dayIdsOf([$fielding]))
        ->and($registration->days()->count())->toBe(2)
        ->and($order->refresh()->refunded_amount)->toBe(10000)
        ->and($order->amountKept())->toBe(12500)
        ->and(app(PaymentGateway::class)->refunds)->toBe([['order_id' => $order->id, 'amount' => 10000]]);
    Mail::assertQueued(CampRegistrationChanged::class, fn ($mail) => $mail->refund === 10000 && $mail->hasTo($registration->email));

    (new CampRegistrationChanged($registration->refresh(), 10000))
        ->assertSeeInHtml('Pitching')
        ->assertDontSeeInHtml('Fielding')
        ->assertSeeInHtml('$100.00');
});

test('the session kept is re-priced with the coupon the order used', function () {
    Coupon::factory()->fixedPerItem(2500)->combinable()->create(['code' => 'THANKS30']);
    ['registration' => $registration, 'fielding' => $fielding] = paidForBothSessions('THANKS30');

    $this->actingAs($this->admin)->postJson(dropDaysUrl($registration, 'admin.camps.registrations.drop-days.preview'), ['days' => dayIdsOf([$fielding])])
        ->assertJson(['paid' => 17500, 'kept_price' => 10000, 'suggested_refund' => 7500]);
});

test('staff can change the refund, but not above what was paid', function () {
    Mail::fake();
    ['registration' => $registration, 'order' => $order, 'fielding' => $fielding] = paidForBothSessions();

    $this->actingAs($this->admin)->post(dropDaysUrl($registration), ['days' => dayIdsOf([$fielding]), 'refund' => '300'])
        ->assertSessionHasErrors(['refund' => 'The refund can be up to $225.00.']);
    expect($registration->days()->count())->toBe(4);

    $this->actingAs($this->admin)->post(dropDaysUrl($registration), ['days' => dayIdsOf([$fielding]), 'refund' => '0'])
        ->assertSessionHasNoErrors();
    expect($registration->days()->count())->toBe(2)
        ->and($order->refresh()->refunded_amount)->toBe(0)
        ->and(app(PaymentGateway::class)->refunds)->toBe([]);
});

test('if Stripe turns the refund down, the booking is left as it was', function () {
    Mail::fake();
    ['registration' => $registration, 'order' => $order, 'fielding' => $fielding] = paidForBothSessions();
    app(PaymentGateway::class)->failRefund = true;

    $this->actingAs($this->admin)->post(dropDaysUrl($registration), ['days' => dayIdsOf([$fielding]), 'refund' => '100'])
        ->assertSessionHasErrors('refund');

    expect($registration->days()->count())->toBe(4)
        ->and($order->refresh()->refunded_amount)->toBe(0);
    Mail::assertNotQueued(CampRegistrationChanged::class);
});

test('days can only be dropped in ways the camp could have been booked, and at least one day stays', function (Closure $days, string $message) {
    ['registration' => $registration, 'pitching' => $pitching, 'fielding' => $fielding] = paidForBothSessions();

    $this->actingAs($this->admin)->postJson(dropDaysUrl($registration, 'admin.camps.registrations.drop-days.preview'), ['days' => $days($pitching, $fielding)])
        ->assertJsonValidationErrors(['days' => $message]);
})->with([
    'half of a whole-session-only session' => [fn ($pitching, $fielding) => [$fielding->days()->first()->id], 'Fielding & Hitting can only be booked for all of its days.'],
    'every day' => [fn ($pitching, $fielding) => dayIdsOf([$pitching, $fielding]), 'Keep at least one day. To cancel everything, refund the whole order in Stripe.'],
]);

test('only admins can drop days and refund', function () {
    ['registration' => $registration, 'fielding' => $fielding] = paidForBothSessions();

    $this->actingAs(User::factory()->staff()->create())
        ->post(dropDaysUrl($registration), ['days' => dayIdsOf([$fielding]), 'refund' => '100'])
        ->assertForbidden();
});

test('a partial refund made in the Stripe dashboard is recorded on the order', function () {
    ['order' => $order] = paidForBothSessions();

    $this->postJson(route('stripe.webhook'), [
        'type' => 'charge.refunded',
        'payment_intent' => 'pi_fake_1',
        'refunded' => false,
        'amount_refunded' => 5000,
    ], ['Stripe-Signature' => 'fake'])->assertOk();

    expect($order->refresh()->refunded_amount)->toBe(5000)
        ->and($order->status->value)->toBe('paid');
});
