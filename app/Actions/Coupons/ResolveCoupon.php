<?php

namespace App\Actions\Coupons;

use App\Enums\OrderType;
use App\Models\Camp;
use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

/**
 * Turn a typed coupon code into a usable coupon for a camp or merch order,
 * or fail with a message for the coupon field.
 */
class ResolveCoupon
{
    /**
     * @param  bool  $lock  Lock the coupon row so its use limit holds under concurrent orders (needs a transaction).
     *
     * @throws ValidationException
     */
    public function handle(?string $code, OrderType $type, ?Camp $camp = null, bool $lock = false): ?Coupon
    {
        if (blank($code)) {
            return null;
        }

        $coupon = Coupon::query()
            ->where('code', Coupon::normalizeCode($code))
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->first()
            ?? throw ValidationException::withMessages(['coupon_code' => __('That coupon code doesn’t exist. Check the spelling and try again.')]);

        if ($problem = $coupon->problemFor($type, $camp, locked: $lock)) {
            throw ValidationException::withMessages(['coupon_code' => $problem]);
        }

        return $coupon;
    }
}
