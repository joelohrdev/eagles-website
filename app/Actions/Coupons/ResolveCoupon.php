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
            ?? throw ValidationException::withMessages(['coupon_code' => __('That coupon code isn’t valid.')]);

        if ($problem = $coupon->problemFor($type, $camp)) {
            throw ValidationException::withMessages(['coupon_code' => $problem]);
        }

        return $coupon;
    }
}
