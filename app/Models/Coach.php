<?php

namespace App\Models;

use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasSortOrder;
use App\Services\ImageUploader;
use Database\Factories\CoachFactory;
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
 * @property string|null $title
 * @property string|null $bio
 * @property string|null $photo_path
 * @property string|null $email
 * @property string|null $phone
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable(['name', 'slug', 'title', 'bio', 'photo_path', 'email', 'phone', 'sort_order', 'is_active'])]
class Coach extends Model
{
    /** @use HasFactory<CoachFactory> */
    use HasFactory, HasSeoMeta, HasSlug, HasSortOrder;

    /**
     * @var list<string>
     */
    protected $appends = ['photo_url', 'photo_thumbnail_url'];

    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
    ];

    /**
     * @return Attribute<string|null, never>
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(get: fn (): ?string => ImageUploader::url($this->photo_path));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function photoThumbnailUrl(): Attribute
    {
        return Attribute::make(get: fn (): ?string => ImageUploader::thumbnailUrl($this->photo_path));
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
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
