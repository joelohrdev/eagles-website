<?php

namespace App\Models;

use App\Services\ImageUploader;
use Database\Factories\FacilityPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $image_path
 * @property string|null $caption
 * @property int $sort_order
 */
#[Fillable(['image_path', 'caption', 'sort_order'])]
class FacilityPhoto extends Model
{
    /** @use HasFactory<FacilityPhotoFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['image_url', 'thumbnail_url'];

    protected $attributes = [
        'sort_order' => 0,
    ];

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => ImageUploader::url($this->image_path));
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => ImageUploader::thumbnailUrl($this->image_path));
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
