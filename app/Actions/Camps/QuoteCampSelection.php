<?php

namespace App\Actions\Camps;

use App\Actions\Coupons\ResolveCoupon;
use App\Enums\OrderType;
use App\Models\Camp;
use App\Models\CampAgeGroup;
use App\Models\CampSession;
use App\Models\CampSessionDay;
use App\Models\Coupon;
use App\Support\Camps\CampPricing;
use App\Support\Camps\CampQuote;
use App\Support\Camps\CampQuoteLine;
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

        $coupon = $this->resolveCoupon->handle($couponCode, OrderType::Camp, $camp, $lockCoupon);

        return CampPricing::quote($lines, $camp->sessions->count(), $camp->bundle_price, $coupon);
    }

    /**
     * Price days a player already holds, for a partial refund: the same pricing
     * as sign-up, but days that have passed are fine and capacity isn't checked
     * (they're already booked). The coupon is the one the order used, as is.
     *
     * @param  list<int>  $dayIds
     *
     * @throws ValidationException when the days can't be booked on their own (e.g. part of a whole-session-only session).
     */
    public function reprice(Camp $camp, array $dayIds, ?Coupon $coupon): CampQuote
    {
        $camp->loadSchedule();
        $lines = $this->priceLines($camp, array_values(array_unique($dayIds)), allowPastDays: true);

        return CampPricing::quote($lines, $camp->sessions->count(), $camp->bundle_price, $coupon);
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
    private function priceLines(Camp $camp, array $dayIds, bool $allowPastDays = false): array
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

            $past = $allowPastDays ? null : $picked->first(fn (CampSessionDay $day): bool => $day->date !== null && $day->date->lt(today()));

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
}
