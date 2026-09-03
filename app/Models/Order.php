<?php

namespace App\Models;

use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $number
 * @property OrderType $type
 * @property string $email
 * @property string $name
 * @property string|null $phone
 * @property Fulfillment $fulfillment
 * @property string|null $shipping_address_line1
 * @property string|null $shipping_address_line2
 * @property string|null $shipping_city
 * @property string|null $shipping_state
 * @property string|null $shipping_postal_code
 * @property int $subtotal
 * @property int $total
 * @property OrderStatus $status
 * @property string|null $stripe_checkout_session_id
 * @property string|null $stripe_payment_intent_id
 * @property Carbon|null $paid_at
 * @property Carbon|null $fulfilled_at
 * @property string|null $notes
 */
#[Fillable(['number', 'type', 'email', 'name', 'phone', 'fulfillment', 'shipping_address_line1', 'shipping_address_line2', 'shipping_city', 'shipping_state', 'shipping_postal_code', 'subtotal', 'total', 'status', 'stripe_checkout_session_id', 'stripe_payment_intent_id', 'paid_at', 'fulfilled_at', 'notes'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $attributes = [
        'type' => 'merch',
        'fulfillment' => 'pickup',
        'status' => 'pending',
        'subtotal' => 0,
        'total' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            $order->number ??= static::generateNumber();
        });
    }

    protected function casts(): array
    {
        return [
            'type' => OrderType::class,
            'fulfillment' => Fulfillment::class,
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'EB-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (static::query()->where('number', $number)->exists());

        return $number;
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function campRegistration(): HasOne
    {
        return $this->hasOne(CampRegistration::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, [OrderStatus::Paid, OrderStatus::Fulfilled], true);
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    #[Scope]
    protected function paid(Builder $query): Builder
    {
        return $query->whereIn('status', [OrderStatus::Paid, OrderStatus::Fulfilled]);
    }

    #[Scope]
    protected function awaitingFulfillment(Builder $query): Builder
    {
        return $query->where('type', OrderType::Merch)->where('status', OrderStatus::Paid);
    }
}
