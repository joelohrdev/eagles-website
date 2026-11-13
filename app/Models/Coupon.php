<?php

namespace App\Models;

use App\Enums\CouponScope;
use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A discount code. `value` is a whole percentage for Percent coupons and
 * cents for the fixed types. A coupon limited to camps can be narrowed
 * further to specific camps; with none attached it covers every camp.
 *
 * @property int $id
 * @property string $code
 * @property string|null $description
 * @property CouponType $type
 * @property int $value
 * @property CouponScope $applies_to
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property int|null $max_redemptions
 * @property bool $is_active
 * @property int|null $redemptions_count
 */
#[Fillable(['code', 'description', 'type', 'value', 'applies_to', 'starts_at', 'expires_at', 'max_redemptions', 'is_active'])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * Order statuses that use up a redemption. Cancelled and refunded orders give theirs back.
     */
    public const array REDEEMING_STATUSES = [OrderStatus::Pending, OrderStatus::Paid, OrderStatus::Fulfilled];

    protected $attributes = [
        'applies_to' => 'all',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'applies_to' => CouponScope::class,
            'value' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'max_redemptions' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Codes are matched case-insensitively, so they are stored trimmed and uppercase.
     *
     * @return Attribute<string, string>
     */
    protected function code(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => self::normalizeCode($value));
    }

    public static function normalizeCode(string $code): string
    {
        return Str::upper(trim($code));
    }

    public static function findByCode(?string $code): ?self
    {
        if (blank($code)) {
            return null;
        }

        return static::query()->where('code', self::normalizeCode($code))->first();
    }

    /**
     * @return BelongsToMany<Camp, $this>
     */
    public function camps(): BelongsToMany
    {
        return $this->belongsToMany(Camp::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Orders using up this code. A camp hold whose time ran out stops counting
     * straight away, without waiting for the expiry job to cancel its order.
     *
     * @return HasMany<Order, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->orders()
            ->whereIn('status', self::REDEEMING_STATUSES)
            ->whereNot(fn (Builder $query) => $query
                ->where('status', OrderStatus::Pending)
                ->whereHas('campRegistration', fn (Builder $registration) => $registration->where('expires_at', '<=', now())));
    }

    /**
     * @param  bool  $lock  Read with a locking read, so a transaction sees uses committed while it waited for the coupon lock.
     */
    public function redemptionCount(bool $lock = false): int
    {
        if ($lock) {
            return $this->redemptions()->lockForUpdate()->count();
        }

        return $this->redemptions_count ?? $this->redemptions()->count();
    }

    /**
     * Why this coupon can't be used for an order of this type (and camp), or null when it can.
     */
    /**
     * @param  bool  $locked  The coupon row is locked in this transaction; count uses with a locking read.
     */
    public function problemFor(OrderType $type, ?Camp $camp = null, bool $locked = false): ?string
    {
        if (! $this->is_active) {
            return __('That coupon code is not active.');
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return __('That coupon code is not active yet.');
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return __('That coupon code has expired.');
        }

        if (! $this->applies_to->covers($type)) {
            return $type === OrderType::Camp
                ? __('That coupon code can’t be used for camps.')
                : __('That coupon code can’t be used for merch.');
        }

        if ($camp !== null && $this->camps()->exists() && ! $this->camps()->whereKey($camp->id)->exists()) {
            return __('That coupon code can’t be used for this camp.');
        }

        if ($this->max_redemptions !== null && $this->redemptionCount($locked) >= $this->max_redemptions) {
            return __('That coupon code has been used up.');
        }

        return null;
    }

    /**
     * "20% off", "$25.00 off", "$25.00 off each item".
     */
    public function summary(): string
    {
        $amount = '$'.number_format($this->value / 100, 2);

        return match ($this->type) {
            CouponType::Percent => "{$this->value}% off",
            CouponType::FixedOrder => "{$amount} off",
            CouponType::FixedPerItem => "{$amount} off each item",
        };
    }
}
