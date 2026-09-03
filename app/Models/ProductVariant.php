<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string|null $size
 * @property string|null $color
 * @property string|null $sku
 * @property int|null $stock
 * @property int|null $price_override
 * @property bool $is_active
 */
#[Fillable(['product_id', 'size', 'color', 'sku', 'stock', 'price_override', 'is_active'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'price_override' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Effective unit price in cents.
     */
    public function price(): int
    {
        return $this->price_override ?? $this->product->price;
    }

    public function label(): string
    {
        return collect([$this->size, $this->color])->filter()->implode(' / ') ?: 'Standard';
    }

    public function isInStock(int $quantity = 1): bool
    {
        return $this->stock === null || $this->stock >= $quantity;
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
