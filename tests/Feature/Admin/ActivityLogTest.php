<?php

use App\Models\ActivityLog;
use App\Models\Camp;
use App\Models\CampRegistration;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Mail::fake();
    $this->admin = User::factory()->admin()->create(['name' => 'Coach Admin']);
});

function webhookEvent(array $payload)
{
    return test()->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => 'fake', 'CONTENT_TYPE' => 'application/json'], json_encode($payload));
}

test('a paid camp sign-up is logged from registration through payment and refund, with who did each step', function () {
    ['camp' => $camp, 'young' => $young, 'pitching' => $pitching] = thanksgivingCamp();
    Coupon::factory()->fixedPerItem(2500)->create(['code' => 'THANKS30']);

    $this->post(route('camps.register.store', $camp), validCampRegistration($camp, [
        'days' => dayIdsOf([$pitching]), 'camp_age_group_id' => $young->id, 'coupon_code' => 'thanks30',
    ]));
    $order = Order::query()->sole();

    webhookEvent(['type' => 'checkout.session.completed', 'session_id' => $order->stripe_checkout_session_id, 'payment_status' => 'paid', 'payment_intent' => 'pi_1']);
    webhookEvent(['type' => 'charge.refunded', 'payment_intent' => 'pi_1', 'refunded' => true]);

    $logs = ActivityLog::query()->orderBy('id')->get();

    expect($logs->pluck('event')->all())->toBe(['camp_registration.created', 'order.paid', 'order.refunded'])
        ->and($logs[0]->actor)->toBe('Customer')
        ->and($logs[0]->ip_address)->toBe('127.0.0.1')
        ->and($logs[0]->description)->toBe('Jamie Rivera registered for Thanksgiving Camp')
        ->and($logs[0]->properties)->toMatchArray(['age_group' => '8–12U', 'total' => 10000, 'discount' => 2500, 'discount_label' => 'THANKS30', 'status' => 'Pending payment'])
        ->and($logs[0]->properties)->not->toHaveKey('medical_notes')
        ->and($logs[1]->actor)->toBe('Stripe')
        ->and($logs[1]->description)->toBe("Payment of \$100.00 received for order {$order->number}")
        ->and($logs[2]->actor)->toBe('Stripe');
});

test('cancellations record why: customer at checkout, Stripe expiry, or an expired hold', function () {
    ['camp' => $camp, 'young' => $young, 'pitching' => $pitching] = thanksgivingCamp();
    $form = fn (string $email) => validCampRegistration($camp, ['days' => dayIdsOf([$pitching]), 'camp_age_group_id' => $young->id, 'email' => $email]);

    $this->post(route('camps.register.store', $camp), $form('a@example.com'));
    $this->get(URL::signedRoute('checkout.cancel', ['order' => Order::query()->latest('id')->first()->number]));

    $this->post(route('camps.register.store', $camp), $form('b@example.com'));
    webhookEvent(['type' => 'checkout.session.expired', 'session_id' => Order::query()->latest('id')->first()->stripe_checkout_session_id]);

    $this->post(route('camps.register.store', $camp), $form('c@example.com'));
    $this->travel(40)->minutes();
    $this->artisan('camps:expire-pending')->assertSuccessful();

    $cancellations = ActivityLog::query()->where('event', 'order.cancelled')->orderBy('id')->get();

    expect($cancellations->pluck('properties.reason')->all())->toBe(['Customer left the Stripe payment page', 'Stripe payment page expired', 'Payment hold expired'])
        ->and($cancellations->pluck('actor')->all())->toBe(['Customer', 'Stripe', 'System'])
        ->and(ActivityLog::query()->where('event', 'camp_registration.expired')->value('actor'))->toBe('System');
});

test('staff actions are logged under their name', function () {
    $camp = Camp::factory()->unpublished()->create();
    $registration = CampRegistration::factory()->for($camp)->create();
    $order = Order::factory()->paid()->create();

    $this->actingAs($this->admin)->delete(route('admin.camps.registrations.destroy', [$camp, $registration]));
    $this->actingAs($this->admin)->put(route('admin.camps.update', $camp), [
        'name' => 'Renamed Camp',
        'preview_passcode' => '1234',
        'is_published' => 1,
        'sessions' => [['name' => 'Hitting', 'price' => '100', 'days' => [['date' => '2027-01-09', 'times' => [['starts_at' => '09:00']]]]]],
    ])->assertSessionHasNoErrors();
    $this->actingAs($this->admin)->put(route('admin.orders.update', $order), ['status' => 'fulfilled']);
    $this->actingAs($this->admin)->post(route('admin.coupons.store'), ['code' => 'SPRING', 'type' => 'percent', 'value' => '10', 'applies_to' => 'all', 'is_active' => 1]);

    $logs = ActivityLog::query()->orderBy('id')->get()->keyBy('event');

    expect($logs->every(fn (ActivityLog $log) => $log->actor === 'Coach Admin' && $log->user_id === $this->admin->id))->toBeTrue()
        ->and($logs['camp.updated']->description)->toBe('Camp Renamed Camp published')
        ->and($logs['camp.updated']->properties['changed_fields'])->toContain('name', 'preview passcode')
        ->and(json_encode($logs['camp.updated']->properties))->not->toContain('1234')
        ->and($logs['camp_registration.deleted']->properties['player'])->toBe($registration->playerName())
        ->and($logs['order.status_changed']->properties)->toEqual(['from' => 'Paid', 'to' => 'Fulfilled'])
        ->and($logs['coupon.created']->description)->toBe('Coupon SPRING created (10% off)');
});

test('only admins can read the activity log, and it filters to one registration’s history', function () {
    ['camp' => $camp, 'young' => $young, 'pitching' => $pitching] = thanksgivingCamp();
    foreach (['first@example.com' => 'Ava', 'second@example.com' => 'Ben'] as $email => $player) {
        $this->post(route('camps.register.store', $camp), validCampRegistration($camp, ['days' => dayIdsOf([$pitching]), 'camp_age_group_id' => $young->id, 'email' => $email, 'player_first_name' => $player]));
    }
    $first = CampRegistration::query()->where('email', 'first@example.com')->sole();
    webhookEvent(['type' => 'checkout.session.completed', 'session_id' => $first->order->stripe_checkout_session_id, 'payment_status' => 'paid']);

    $this->actingAs(User::factory()->staff()->create())->get(route('admin.activity.index'))->assertForbidden();

    $this->actingAs($this->admin)->get(route('admin.activity.index', ['registration' => $first->id]))
        ->assertInertia(fn ($page) => $page
            ->component('admin/activity/Index')
            ->has('logs.data', 2)
            ->where('logs.data.0.event', 'order.paid')
            ->where('logs.data.1.event', 'camp_registration.created')
            ->where('scope', 'History for one registration'));

    $this->actingAs($this->admin)->get(route('admin.activity.index', ['category' => 'payments']))
        ->assertInertia(fn ($page) => $page->has('logs.data', 1));

    $this->actingAs($this->admin)->get(route('admin.activity.index', ['q' => 'second@example.com']))
        ->assertInertia(fn ($page) => $page->has('logs.data', 1));
});

test('order pages show their history to admins only', function () {
    $order = Order::factory()->create();
    app(ActivityLogger::class)->log('order.created', 'Merch order placed', $order);

    $this->actingAs($this->admin)->get(route('admin.orders.show', $order))
        ->assertInertia(fn ($page) => $page->has('activity', 1));
    $this->actingAs(User::factory()->staff()->create())->get(route('admin.orders.show', $order))
        ->assertInertia(fn ($page) => $page->where('activity', null));
});

test('entries older than two years are pruned', function () {
    $old = ActivityLog::query()->create(['event' => 'order.paid', 'description' => 'Old', 'actor' => 'System', 'created_at' => now()->subYears(2)->subDay()]);
    $recent = ActivityLog::query()->create(['event' => 'order.paid', 'description' => 'Recent', 'actor' => 'System', 'created_at' => now()->subYear()]);

    $this->artisan('model:prune', ['--model' => [ActivityLog::class]])->assertSuccessful();

    $this->assertModelMissing($old);
    $this->assertModelExists($recent);
});
