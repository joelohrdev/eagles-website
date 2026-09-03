<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderRequest;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function show(Order $order): Response
    {
        $order->load(['items.variant.product', 'campRegistration.camp']);

        return Inertia::render('admin/orders/Show', [
            'order' => $order,
            'stripeUrl' => $this->stripePaymentUrl($order),
            'statuses' => collect(OrderStatus::cases())->map(fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->label()])->all(),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $validated = $request->validated();
        $attributes = ['notes' => $validated['notes'] ?? null];

        if (! empty($validated['status']) && $validated['status'] !== $order->status->value) {
            $status = OrderStatus::from($validated['status']);
            $attributes['status'] = $status;
            $attributes['fulfilled_at'] = $status === OrderStatus::Fulfilled ? now() : null;
        }

        $order->update($attributes);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order updated.')]);

        return to_route('admin.orders.show', $order);
    }

    private function stripePaymentUrl(Order $order): ?string
    {
        if (blank($order->stripe_payment_intent_id)) {
            return null;
        }

        $testMode = Str::startsWith((string) config('services.stripe.key'), 'pk_test');

        return 'https://dashboard.stripe.com/'.($testMode ? 'test/' : '').'payments/'.$order->stripe_payment_intent_id;
    }
}
