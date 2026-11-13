<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderRequest;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Services\StripeSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'status' => $request->string('status')->toString() ?: null,
            'type' => $request->string('type')->toString() ?: null,
            'q' => trim($request->string('q')->toString()) ?: null,
        ];

        $orders = Order::query()
            ->withCount('items')
            ->with(['items:id,order_id,description,size,color,quantity', 'campRegistration:id,order_id,camp_id,player_first_name,player_last_name', 'campRegistration.camp:id,name'])
            ->when($filters['status'], fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['type'], fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['q'], fn (Builder $q, string $term) => $q->where(fn (Builder $inner) => $inner
                ->where('number', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
            ))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/orders/Index', [
            'orders' => $orders,
            'filters' => $filters,
            'statuses' => collect(OrderStatus::cases())->map(fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->label()])->all(),
            'types' => collect(OrderType::cases())->map(fn (OrderType $t) => ['value' => $t->value, 'label' => $t->label()])->all(),
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $order->load(['items.variant.product', 'campRegistration.camp']);

        return Inertia::render('admin/orders/Show', [
            'order' => $order,
            /** The order's history (and its camp registration's), for admins only. */
            'activity' => $request->user()?->can('admin') ? ActivityLog::query()
                ->where(fn ($query) => $query
                    ->where(fn ($q) => $q->where('subject_type', $order->getMorphClass())->where('subject_id', $order->id))
                    ->when($order->campRegistration, fn ($q, $registration) => $q->orWhere(fn ($r) => $r->where('subject_type', $registration->getMorphClass())->where('subject_id', $registration->id))))
                ->latest('created_at')
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->map(fn (ActivityLog $log): array => ActivityLogController::present($log))
                ->all() : null,
            'stripeUrl' => $this->stripePaymentUrl($order),
            'statuses' => collect(OrderStatus::cases())->map(fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->label()])->all(),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order, ActivityLogger $activity): RedirectResponse
    {
        $validated = $request->validated();
        $attributes = ['notes' => $validated['notes'] ?? null];

        if (! empty($validated['status']) && $validated['status'] !== $order->status->value) {
            $status = OrderStatus::from($validated['status']);
            $attributes['status'] = $status;
            $attributes['fulfilled_at'] = $status === OrderStatus::Fulfilled ? now() : null;
        }

        $from = $order->status;

        DB::transaction(function () use ($order, $attributes): void {
            $order->update($attributes);

            if ($order->wasChanged('status')) {
                $this->syncCampRegistration($order);
            }
        });

        if ($order->wasChanged('status')) {
            $activity->log('order.status_changed', "Order {$order->number} marked {$order->status->label()} (was {$from->label()})", $order, [
                'from' => $from->label(),
                'to' => $order->status->label(),
            ]);
        }

        if ($order->wasChanged('notes')) {
            $activity->log('order.notes_updated', "Notes updated on order {$order->number}", $order);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order updated.')]);

        return to_route('admin.orders.show', $order);
    }

    private function stripePaymentUrl(Order $order): ?string
    {
        if (blank($order->stripe_payment_intent_id)) {
            return null;
        }

        $testMode = app(StripeSettings::class)->isTestMode();

        return 'https://dashboard.stripe.com/'.($testMode ? 'test/' : '').'payments/'.$order->stripe_payment_intent_id;
    }

    /**
     * Keep a camp order's registration in step with a status set by hand, so a
     * cancelled or refunded order no longer shows as pending or holds its spot.
     */
    private function syncCampRegistration(Order $order): void
    {
        if ($order->type !== OrderType::Camp || $order->campRegistration === null) {
            return;
        }

        $registrationStatus = match ($order->status) {
            OrderStatus::Cancelled => RegistrationStatus::Cancelled,
            OrderStatus::Refunded => RegistrationStatus::Refunded,
            OrderStatus::Paid, OrderStatus::Fulfilled => RegistrationStatus::Paid,
            OrderStatus::Pending => null,
        };

        if ($registrationStatus !== null) {
            $order->campRegistration->forceFill(['status' => $registrationStatus])->save();
        }
    }
}
