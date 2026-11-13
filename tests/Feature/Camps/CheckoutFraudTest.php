<?php

use App\Actions\Orders\CancelPendingOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Mail\CampRegistrationConfirmation;
use App\Models\ActivityLog;
use App\Models\Camp;
use App\Models\CampRegistration;
use App\Models\Coupon;
use App\Models\Order;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\UnavailableGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Mail::fake();
});

/**
 * @return array{camp: Camp, form: array<string, mixed>}
 */
function pitchingSignUp(array $overrides = [], ?int $olderCapacity = null): array
{
    ['camp' => $camp, 'young' => $young, 'pitching' => $pitching] = thanksgivingCamp($olderCapacity);

    return ['camp' => $camp, 'form' => validCampRegistration($camp, [
        'days' => dayIdsOf([$pitching]),
        'camp_age_group_id' => $young->id,
        ...$overrides,
    ])];
}

test('cancelling closes the Stripe page before the spot and coupon use are released', function () {
    Coupon::factory()->fixedPerItem(2500)->create(['code' => 'ONCE', 'max_redemptions' => 1]);
    ['camp' => $camp, 'form' => $form] = pitchingSignUp(['coupon_code' => 'ONCE']);

    $this->post(route('camps.register.store', $camp), $form);
    $order = Order::query()->sole();

    $this->get(URL::signedRoute('checkout.cancel', ['order' => $order->number]))
        ->assertRedirect(route('camps.show', $camp));

    expect(app(PaymentGateway::class)->closedSessions)->toBe([$order->stripe_checkout_session_id])
        ->and($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->campRegistration->status)->toBe(RegistrationStatus::Cancelled);
});

test('an order that was already paid at Stripe is not released when the customer cancels', function () {
    ['camp' => $camp, 'form' => $form] = pitchingSignUp();
    $this->post(route('camps.register.store', $camp), $form);
    $order = Order::query()->sole();

    /** @var FakeGateway $gateway */
    $gateway = app(PaymentGateway::class);
    $gateway->paidSessions[] = $order->stripe_checkout_session_id;

    $this->get(URL::signedRoute('checkout.cancel', ['order' => $order->number]))
        ->assertRedirect(URL::signedRoute('checkout.success', ['order' => $order->number]));

    expect($order->refresh()->status)->toBe(OrderStatus::Pending)
        ->and($order->campRegistration->status)->toBe(RegistrationStatus::PendingPayment);
});

test('one email can only hold two unpaid spots on a camp at once', function () {
    ['camp' => $camp, 'form' => $form] = pitchingSignUp(olderCapacity: 2);

    $this->post(route('camps.register.store', $camp), $form)->assertSessionHasNoErrors();
    $this->post(route('camps.register.store', $camp), [...$form, 'email' => 'ALEX@example.com'])->assertSessionHasNoErrors();
    $this->post(route('camps.register.store', $camp), $form)->assertSessionHasErrors('email');

    expect(CampRegistration::count())->toBe(2);

    CampRegistration::query()->first()->update(['status' => RegistrationStatus::Paid, 'expires_at' => null]);
    $this->post(route('camps.register.store', $camp), $form)->assertSessionHasNoErrors();
});

test('sign-ups a coupon covers in full are not counted as unpaid holds', function () {
    Coupon::factory()->percent(100)->create(['code' => 'COACH']);
    ['camp' => $camp, 'form' => $form] = pitchingSignUp(['coupon_code' => 'COACH']);

    foreach (range(1, 3) as $i) {
        $this->post(route('camps.register.store', $camp), $form)->assertSessionHasNoErrors();
    }

    expect(CampRegistration::count())->toBe(3);
});

test('camp sign-up needs the bot check when Turnstile is switched on', function () {
    config(['services.turnstile.site_key' => 'site', 'services.turnstile.secret_key' => 'secret']);
    ['camp' => $camp, 'form' => $form] = pitchingSignUp();

    $this->get(route('camps.register', $camp))->assertInertia(fn ($page) => $page->where('turnstileSiteKey', 'site'));

    Http::preventStrayRequests();
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);
    $this->post(route('camps.register.store', $camp), $form)->assertSessionHasErrors('cf-turnstile-response');
    $this->post(route('camps.register.store', $camp), [...$form, 'cf-turnstile-response' => 'bad'])->assertSessionHasErrors('cf-turnstile-response');

    expect(CampRegistration::count())->toBe(0);
});

test('checking prices does not use up the sign-up rate limit', function () {
    ['camp' => $camp, 'form' => $form] = pitchingSignUp();

    foreach (range(1, 12) as $i) {
        $this->postJson(route('camps.quote', $camp), ['days' => $form['days'], 'camp_age_group_id' => $form['camp_age_group_id']])->assertOk();
    }

    $this->post(route('camps.register.store', $camp), $form)->assertRedirect();
});

test('with payments switched off, paid sign-ups and orders are refused without holding anything', function () {
    app()->instance(PaymentGateway::class, new UnavailableGateway);
    ['camp' => $camp, 'form' => $form] = pitchingSignUp();

    $this->post(route('camps.register.store', $camp), $form)
        ->assertSessionHasErrors(['registration' => 'Online payment isn’t available right now. Please contact us to register.']);

    expect(CampRegistration::count())->toBe(0)
        ->and(Order::count())->toBe(0);
});

test('a payment that lands after the hold ended keeps the spot while there is still room', function () {
    ['camp' => $camp, 'form' => $form] = pitchingSignUp();
    $this->post(route('camps.register.store', $camp), $form);
    $order = Order::query()->sole();
    app(CancelPendingOrder::class)->handle($order, 'Payment hold expired');

    app(MarkOrderPaid::class)->handle($order->refresh());

    expect($order->refresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->campRegistration->status)->toBe(RegistrationStatus::Paid);
    Mail::assertQueued(CampRegistrationConfirmation::class);
});

test('a payment that lands after its spot was taken is recorded and flagged for staff', function () {
    ['camp' => $camp, 'form' => $form] = pitchingSignUp(olderCapacity: 1);
    $form['camp_age_group_id'] = $camp->ageGroups()->where('name', '13–17U')->value('id');
    $this->post(route('camps.register.store', $camp), $form);
    $late = Order::query()->sole();
    app(CancelPendingOrder::class)->handle($late, 'Payment hold expired');
    $this->post(route('camps.register.store', $camp), [...$form, 'email' => 'other@example.com'])->assertSessionHasNoErrors();

    app(MarkOrderPaid::class)->handle($late->refresh());

    expect($late->refresh()->status)->toBe(OrderStatus::Paid)
        ->and($late->campRegistration->status)->toBe(RegistrationStatus::Cancelled)
        ->and(ActivityLog::query()->where('event', 'order.paid_without_spot')->count())->toBe(1);
    Mail::assertNotQueued(CampRegistrationConfirmation::class);
});

test('a Stripe error while opening the payment page releases the spot and asks the parent to try again', function () {
    Coupon::factory()->fixedPerItem(2500)->create(['code' => 'ONCE', 'max_redemptions' => 1]);
    ['camp' => $camp, 'form' => $form] = pitchingSignUp(['coupon_code' => 'ONCE']);
    app(PaymentGateway::class)->failCheckout = true;

    $this->post(route('camps.register.store', $camp), $form)->assertSessionHasErrors('registration');

    expect(Order::query()->sole()->status)->toBe(OrderStatus::Cancelled)
        ->and(CampRegistration::query()->sole()->status)->toBe(RegistrationStatus::Cancelled);
});

test('the expiry job leaves a hold alone when its Stripe page was already paid', function () {
    ['camp' => $camp, 'form' => $form] = pitchingSignUp();
    $this->post(route('camps.register.store', $camp), $form);
    $order = Order::query()->sole();
    app(PaymentGateway::class)->paidSessions[] = $order->stripe_checkout_session_id;
    $this->travel(CampRegistration::PENDING_HOLD_MINUTES + 1)->minutes();

    $this->artisan('camps:expire-pending')->assertSuccessful();

    expect($order->refresh()->status)->toBe(OrderStatus::Pending)
        ->and($order->campRegistration->status)->toBe(RegistrationStatus::PendingPayment);
});
