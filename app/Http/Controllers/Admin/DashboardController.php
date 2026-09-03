<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camp;
use App\Models\CampRegistration;
use App\Models\ContactSubmission;
use App\Models\Invitation;
use App\Models\Order;
use App\Models\Tryout;
use App\Models\TryoutRegistration;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $since = now()->subDays(30);

        $openTryouts = Tryout::query()->published()->upcoming()->get()
            ->filter(fn (Tryout $tryout) => $tryout->isRegistrationOpen())
            ->count();

        $stats = [
            'open_tryouts' => $openTryouts,
            'tryout_registrations_30d' => TryoutRegistration::query()->where('registered_at', '>=', $since)->count(),
            'upcoming_camps' => Camp::query()->published()->upcoming()->count(),
            'camp_registrations_30d' => CampRegistration::query()->paid()->where('registered_at', '>=', $since)->count(),
            'unread_messages' => ContactSubmission::query()->unread()->count(),
            'orders_awaiting_fulfillment' => Order::query()->awaitingFulfillment()->count(),
            'revenue_30d' => (int) Order::query()->paid()->where('paid_at', '>=', $since)->sum('total'),
            'pending_invitations' => $request->user()->isAdmin() ? Invitation::query()->pending()->count() : null,
        ];

        return Inertia::render('admin/Dashboard', [
            'stats' => $stats,
            'recentOrders' => Order::query()->latest()->take(5)->get(['id', 'number', 'type', 'name', 'email', 'total', 'status', 'created_at']),
            'recentTryoutRegistrations' => TryoutRegistration::query()->with('tryout:id,title,slug')->latest('registered_at')->take(5)->get(),
            'recentMessages' => ContactSubmission::query()->latest()->take(5)->get(['id', 'name', 'email', 'subject', 'read_at', 'created_at']),
        ]);
    }
}
