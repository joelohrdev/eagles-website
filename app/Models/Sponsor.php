<?php

namespace App\Models;

use App\Models\Concerns\HasSortOrder;
use App\Services\ImageUploader;
use Database\Factories\SponsorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $website_url
 * @property string $logo_path
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable(['name', 'website_url', 'logo_path', 'sort_order', 'is_active'])]
class Sponsor extends Model
{
    /** @use HasFactory<SponsorFactory> */
    use HasFactory, HasSortOrder;

    /**
     * @var list<string>
     */
    protected $appends = ['logo_url'];

    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
    ];

    /**
     * @return Attribute<string|null, never>
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::make(get: fn (): ?string => ImageUploader::url($this->logo_path));
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
