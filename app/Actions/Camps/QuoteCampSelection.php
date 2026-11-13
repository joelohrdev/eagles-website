<?php

namespace App\Actions\Camps;

use App\Actions\Coupons\ResolveCoupon;
use App\Enums\OrderType;
use App\Models\Camp;
use App\Models\CampAgeGroup;
use App\Models\CampSession;
use App\Models\CampSessionDay;
use App\Support\Camps\CampQuote;
use App\Support\Camps\CampQuoteLine;
use App\Support\Coupons\CouponCalculator;
use App\Support\Coupons\DiscountableLine;
use Illuminate\Validation\ValidationException;

/**
 * Check a parent's choice of days, age group and coupon against a camp's
 * schedule and capacity, and price it. Used for the live price on the
 * registration form and again, under a row lock, when the registration is saved.
 */
class QuoteCampSelection
{
    public function __construct(private ResolveCoupon $resolveCoupon) {}

    /**
     * @param  list<int>  $dayIds
     * @param  bool  $lockCoupon  Lock the coupon row so its redemption limit holds under concurrent sign-ups (needs a transaction).
     *
     * @throws ValidationException
     */
    public function handle(Camp $camp, array $dayIds, ?int $ageGroupId, ?string $couponCode, bool $lockCoupon = false): CampQuote
    {
        $camp->loadSchedule();

        $ageGroup = $this->resolveAgeGroup($camp, $ageGroupId);
        $lines = $this->priceLines($camp, array_values(array_unique(array_map('intval', $dayIds))));
        $this->ensureRoom($camp, $lines, $ageGroup);

        $subtotal = array_sum(array_map(fn (CampQuoteLine $line): int => $line->price, $lines));
        $bundleDiscount = $this->bundleDiscount($camp, $lines, $subtotal);
        $coupon = $this->resolveCoupon->handle($couponCode, OrderType::Camp, $camp, $lockCoupon);

        return new CampQuote(
            lines: $lines,
            subtotal: $subtotal,
            bundleDiscount: $bundleDiscount,
            coupon: $coupon,
            couponDiscount: $coupon ? min(
                CouponCalculator::discount($coupon, $this->discountableLines($lines, $bundleDiscount)),
                $subtotal - $bundleDiscount,
            ) : 0,
        );
    }

    private function resolveAgeGroup(Camp $camp, ?int $ageGroupId): ?CampAgeGroup
    {
        if ($camp->ageGroups->isEmpty()) {
            return null;
        }

        return $camp->ageGroups->firstWhere('id', $ageGroupId)
            ?? throw ValidationException::withMessages(['camp_age_group_id' => __('Choose the player’s age group.')]);
    }

    /**
     * @param  list<int>  $dayIds
     * @return list<CampQuoteLine>
     */
    private function priceLines(Camp $camp, array $dayIds): array
    {
        if ($dayIds === []) {
            throw ValidationException::withMessages(['days' => __('Choose at least one session or day.')]);
        }

        $knownDayIds = $camp->sessions->flatMap(fn (CampSession $session) => $session->days->pluck('id'))->all();

        if (array_diff($dayIds, $knownDayIds) !== []) {
            throw ValidationException::withMessages(['days' => __('One of the chosen days is not part of this camp.')]);
        }

        $lines = [];

        foreach ($camp->sessions as $session) {
            $picked = $session->days->filter(fn (CampSessionDay $day): bool => in_array($day->id, $dayIds, true))->values();

            if ($picked->isEmpty()) {
                continue;
            }

            $past = $picked->first(fn (CampSessionDay $day): bool => $day->date !== null && $day->date->lt(today()));

            if ($past !== null) {
                throw ValidationException::withMessages(['days' => __(':day has already happened.', ['day' => $past->title()])]);
            }

            $whole = $picked->count() === $session->days->count();

            if (! $whole && ! $session->allowsSingleDays()) {
                throw ValidationException::withMessages(['days' => __(':session can only be booked for all of its days.', ['session' => $session->name])]);
            }

            $lines[] = new CampQuoteLine(
                session: $session,
                days: $picked,
                wholeSession: $whole,
                price: $whole ? (int) $session->price : (int) $session->day_price * $picked->count(),
            );
        }

        return $lines;
    }

    /**
     * @param  list<CampQuoteLine>  $lines
     */
    private function ensureRoom(Camp $camp, array $lines, ?CampAgeGroup $ageGroup): void
    {
        foreach ($lines as $line) {
            foreach ($line->days as $day) {
                if ($camp->slotRemaining($day->id, $ageGroup?->id) === 0) {
                    throw ValidationException::withMessages(['days' => $ageGroup
                        ? __(':day is full for :group.', ['day' => $day->title(), 'group' => $ageGroup->name])
                        : __(':day is full.', ['day' => $day->title()])]);
                }
            }
        }
    }

    /**
     * The lines a coupon is worked out on: each session's price with its share of
     * the multi-session saving already taken off, so the coupon comes off the
     * bundled price instead of the full one.
     *
     * @param  list<CampQuoteLine>  $lines
     * @return list<DiscountableLine>
     */
    private function discountableLines(array $lines, int $bundleDiscount): array
    {
        $subtotal = array_sum(array_map(fn (CampQuoteLine $line): int => $line->price, $lines));
        $remainingDiscount = $bundleDiscount;
        $discountable = [];

        foreach ($lines as $index => $line) {
            $share = $index === count($lines) - 1 || $subtotal === 0
                ? $remainingDiscount
                : intdiv($bundleDiscount * $line->price, $subtotal);
            $remainingDiscount -= $share;
            $discountable[] = new DiscountableLine(max(0, $line->price - $share));
        }

        return $discountable;
    }

    /**
     * The saving from the multi-session price, when every day of every session is picked.
     *
     * @param  list<CampQuoteLine>  $lines
     */
    private function bundleDiscount(Camp $camp, array $lines, int $subtotal): int
    {
        $everything = count($lines) === $camp->sessions->count()
            && collect($lines)->every(fn (CampQuoteLine $line): bool => $line->wholeSession);

        if (! $everything || $camp->bundle_price === null || $camp->sessions->count() < 2) {
            return 0;
        }

        return max(0, $subtotal - $camp->bundle_price);
    }
}
