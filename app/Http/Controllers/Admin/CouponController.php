<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponScope;
use App\Enums\CouponType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreviewCouponRequest;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Models\Camp;
use App\Models\CampSession;
use App\Models\Coupon;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Support\Camps\CampPricing;
use App\Support\Camps\CampQuoteLine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(): Response
    {
        $coupons = Coupon::query()
            ->with('camps:id,name')
            ->withCount('redemptions')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Coupon $coupon): array => [
                ...$coupon->only(['id', 'code', 'description', 'starts_at', 'expires_at', 'max_redemptions', 'is_active']),
                'summary' => $coupon->summary(),
                'applies_to_label' => $coupon->applies_to->label(),
                'camp_names' => $coupon->camps->pluck('name')->all(),
                'redemptions_count' => $coupon->redemptions_count,
            ]);

        return Inertia::render('admin/coupons/Index', [
            'coupons' => $coupons,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/coupons/Create', self::formOptions());
    }

    public function store(StoreCouponRequest $request, ActivityLogger $activity): RedirectResponse
    {
        $coupon = DB::transaction(function () use ($request): Coupon {
            $coupon = Coupon::query()->create($request->couponAttributes());
            $coupon->camps()->sync($request->campIds());

            return $coupon;
        });

        $activity->log('coupon.created', "Coupon {$coupon->code} created ({$coupon->summary()})", $coupon, [
            'works_for' => $coupon->applies_to->label(),
            'max_redemptions' => $coupon->max_redemptions,
            'active' => $coupon->is_active,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Coupon created.')]);

        return to_route('admin.coupons.index');
    }

    public function edit(Coupon $coupon): Response
    {
        return Inertia::render('admin/coupons/Edit', [
            ...self::formOptions(),
            'coupon' => [
                ...$coupon->only(['id', 'code', 'description', 'starts_at', 'expires_at', 'max_redemptions', 'is_active', 'combinable_with_multi_session']),
                'type' => $coupon->type->value,
                'applies_to' => $coupon->applies_to->value,
                'value' => $coupon->type === CouponType::Percent
                    ? (string) $coupon->value
                    : number_format($coupon->value / 100, 2, '.', ''),
                'camp_ids' => $coupon->camps()->pluck('camps.id')->all(),
                'redemptions_count' => $coupon->redemptionCount(),
            ],
            /** Every order placed with the code, newest first; cancelled and refunded ones are listed but don't count as uses. */
            'uses' => $coupon->orders()
                ->with(['campRegistration:id,order_id,camp_id,player_first_name,player_last_name', 'campRegistration.camp:id,name'])
                ->withCount('items')
                ->latest()
                ->paginate(20, pageName: 'uses_page')
                ->withQueryString()
                ->through(fn (Order $order): array => [
                    'id' => $order->id,
                    'number' => $order->number,
                    'name' => $order->name,
                    'email' => $order->email,
                    'purchase' => $order->campRegistration
                        ? trim("{$order->campRegistration->camp?->name} · {$order->campRegistration->playerName()}", ' ·')
                        : trans_choice(':count merch item|:count merch items', $order->items_count),
                    'discount' => $order->discount,
                    'total' => $order->total,
                    'status' => $order->status->value,
                    'status_label' => $order->status->label(),
                    'created_at' => $order->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon, ActivityLogger $activity): RedirectResponse
    {
        $changed = [];

        DB::transaction(function () use ($request, $coupon, &$changed): void {
            $coupon->update($request->couponAttributes());
            $changed = array_values(array_diff(array_keys($coupon->getChanges()), ['updated_at']));

            if (array_filter($coupon->camps()->sync($request->campIds()))) {
                $changed[] = 'camps';
            }
        });

        $activity->log('coupon.updated', "Coupon {$coupon->code} updated ({$coupon->summary()})", $coupon, ['changed_fields' => $changed]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Coupon updated.')]);

        return to_route('admin.coupons.edit', $coupon);
    }

    /**
     * Orders keep the code they were placed with, so deleting a coupon only stops new uses.
     */
    public function destroy(Coupon $coupon, ActivityLogger $activity): RedirectResponse
    {
        $activity->log('coupon.deleted', "Coupon {$coupon->code} deleted", $coupon, ['times_used' => $coupon->redemptionCount()]);

        $coupon->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Coupon deleted.')]);

        return to_route('admin.coupons.index');
    }

    /**
     * Price one session and every session of a camp with the coupon as it is
     * being edited, through the same pricing as checkout. Uses the coupon's
     * camp if it is limited to one, else the next upcoming camp with several
     * sessions, else a sample camp ($125 sessions, $225 for both).
     */
    public function preview(PreviewCouponRequest $request): JsonResponse
    {
        $coupon = $request->coupon();
        $camp = self::previewCamp($request->campIds());

        if ($camp === null) {
            $sessions = collect([
                new CampSession(['name' => 'Session 1', 'price' => 12500]),
                new CampSession(['name' => 'Session 2', 'price' => 12500]),
            ]);
            $bundlePrice = 22500;
        } else {
            $sessions = $camp->sessions;
            $bundlePrice = $camp->bundle_price;
        }

        $line = fn (CampSession $session): CampQuoteLine => new CampQuoteLine(
            session: $session,
            days: $session->relationLoaded('days') ? $session->days : new Collection,
            wholeSession: true,
            price: (int) $session->price,
        );

        $scenarios = [['label' => '1 session', 'lines' => [$line($sessions->first())]]];

        if ($sessions->count() > 1) {
            $scenarios[] = [
                'label' => $sessions->count() === 2 ? 'Both sessions' : "All {$sessions->count()} sessions",
                'lines' => $sessions->map($line)->values()->all(),
            ];
        }

        return response()->json([
            'camp' => $camp?->name,
            'scenarios' => array_map(function (array $scenario) use ($sessions, $bundlePrice, $coupon): array {
                $quote = CampPricing::quote(array_values($scenario['lines']), $sessions->count(), $bundlePrice, $coupon);

                return [
                    'label' => $scenario['label'],
                    'subtotal' => $quote->subtotal,
                    'discounts' => array_map(
                        fn (array $discount): array => [...$discount, 'label' => $discount['label'] === 'PREVIEW' ? 'This coupon' : $discount['label']],
                        $quote->discounts(),
                    ),
                    'total' => $quote->total(),
                    'note' => $quote->couponNote(),
                ];
            }, $scenarios),
        ]);
    }

    /**
     * @param  list<int>  $campIds
     */
    private static function previewCamp(array $campIds): ?Camp
    {
        $query = Camp::query()->with('sessions.days');

        $camp = $campIds !== []
            ? $query->whereKey($campIds[0])->first()
            : $query->upcoming()->has('sessions', '>=', 2)->orderBy('starts_at')->first();

        return $camp?->sessions->isNotEmpty() ? $camp : null;
    }

    /**
     * @return array{types: list<array{value: string, label: string}>, scopes: list<array{value: string, label: string}>, camps: list<array{id: int, name: string}>}
     */
    private static function formOptions(): array
    {
        return [
            'types' => array_map(fn (CouponType $type): array => ['value' => $type->value, 'label' => $type->label()], CouponType::cases()),
            'scopes' => array_map(fn (CouponScope $scope): array => ['value' => $scope->value, 'label' => $scope->label()], CouponScope::cases()),
            'camps' => array_values(Camp::query()->orderByDesc('starts_at')->get(['id', 'name'])->map(fn (Camp $camp): array => ['id' => $camp->id, 'name' => $camp->name])->all()),
        ];
    }
}
