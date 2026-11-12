<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponScope;
use App\Enums\CouponType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Models\Camp;
use App\Models\Coupon;
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

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            Coupon::query()->create($request->couponAttributes())->camps()->sync($request->campIds());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Coupon created.')]);

        return to_route('admin.coupons.index');
    }

    public function edit(Coupon $coupon): Response
    {
        return Inertia::render('admin/coupons/Edit', [
            ...self::formOptions(),
            'coupon' => [
                ...$coupon->only(['id', 'code', 'description', 'starts_at', 'expires_at', 'max_redemptions', 'is_active']),
                'type' => $coupon->type->value,
                'applies_to' => $coupon->applies_to->value,
                'value' => $coupon->type === CouponType::Percent
                    ? (string) $coupon->value
                    : number_format($coupon->value / 100, 2, '.', ''),
                'camp_ids' => $coupon->camps()->pluck('camps.id')->all(),
                'redemptions_count' => $coupon->redemptionCount(),
            ],
        ]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        DB::transaction(function () use ($request, $coupon): void {
            $coupon->update($request->couponAttributes());
            $coupon->camps()->sync($request->campIds());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Coupon updated.')]);

        return to_route('admin.coupons.edit', $coupon);
    }

    /**
     * Orders keep the code they were placed with, so deleting a coupon only stops new uses.
     */
    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Coupon deleted.')]);

        return to_route('admin.coupons.index');
    }

    /**
     * @return array{types: list<array{value: string, label: string}>, scopes: list<array{value: string, label: string}>, camps: list<array{id: int, name: string}>}
     */
    private static function formOptions(): array
    {
        return [
            'types' => array_map(fn (CouponType $type): array => ['value' => $type->value, 'label' => $type->label()], CouponType::cases()),
            'scopes' => array_map(fn (CouponScope $scope): array => ['value' => $scope->value, 'label' => $scope->label()], CouponScope::cases()),
            'camps' => Camp::query()->orderByDesc('starts_at')->get(['id', 'name'])->map(fn (Camp $camp): array => ['id' => $camp->id, 'name' => $camp->name])->all(),
        ];
    }
}
