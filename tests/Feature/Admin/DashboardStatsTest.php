<?php

use App\Enums\OrderStatus;
use App\Models\Camp;
use App\Models\CampRegistration;
use App\Models\ContactSubmission;
use App\Models\Invitation;
use App\Models\Order;
use App\Models\Tryout;
use App\Models\TryoutRegistration;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard shows stats and recent activity', function () {
    $open = Tryout::factory()->create();
    Tryout::factory()->registrationClosed()->create();
    Tryout::factory()->unpublished()->create();
    TryoutRegistration::factory()->count(2)->create(['tryout_id' => $open->id]);

    [$camp] = Camp::factory()->count(2)->create();
    Camp::factory()->past()->create();
    CampRegistration::factory()->create(['camp_id' => $camp->id]);
    CampRegistration::factory()->pending()->create(['camp_id' => $camp->id]);

    ContactSubmission::factory()->count(3)->create();
    ContactSubmission::factory()->read()->create();

    Order::factory()->paid()->create(['total' => 2500]);
    Order::factory()->fulfilled()->create(['total' => 1000]);
    Order::factory()->create(['status' => OrderStatus::Pending, 'total' => 9999]);

    Invitation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Dashboard')
            ->where('stats.open_tryouts', 1)
            ->where('stats.tryout_registrations_30d', 2)
            ->where('stats.upcoming_camps', 2)
            ->where('stats.camp_registrations_30d', 1)
            ->where('stats.unread_messages', 3)
            ->where('stats.orders_awaiting_fulfillment', 1)
            ->where('stats.revenue_30d', 3500)
            ->where('stats.pending_invitations', 1)
            ->has('recentOrders', 3)
            ->has('recentTryoutRegistrations', 2)
            ->has('recentMessages', 4)
        );
});

test('staff do not see the pending invitations stat', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('stats.pending_invitations', null));
});
