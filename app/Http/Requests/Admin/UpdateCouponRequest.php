<?php

namespace App\Http\Requests\Admin;

use App\Models\Coupon;
use Illuminate\Validation\Rules\Unique;

class UpdateCouponRequest extends StoreCouponRequest
{
    protected function uniqueCode(): Unique
    {
        /** @var Coupon $coupon */
        $coupon = $this->route('coupon');

        return parent::uniqueCode()->ignore($coupon);
    }
}
