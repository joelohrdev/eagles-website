<?php

namespace App\Models;

use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasSortOrder;
use App\Services\ImageUploader;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $price
 * @property string|null $image_path
 * @property bool $is_active
 * @property int $sort_order
 */
#[Fillable(['name', 'slug', 'description', 'price', 'image_path', 'is_active', 'sort_order'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasSeoMeta, HasSlug, HasSortOrder;

    /**
     * @var list<string>
     */
    protected $appends = ['image_url', 'image_thumbnail_url'];

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => ImageUploader::url($this->image_path));
    }

    protected function imageThumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => ImageUploader::thumbnailUrl($this->image_path));
    }

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
