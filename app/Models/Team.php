<?php

namespace App\Models;

use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasSortOrder;
use App\Services\ImageUploader;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $division
 * @property string|null $season
 * @property string|null $description
 * @property string|null $photo_path
 * @property int|null $coach_id
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable(['name', 'slug', 'division', 'season', 'description', 'photo_path', 'coach_id', 'sort_order', 'is_active'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory, HasSeoMeta, HasSlug, HasSortOrder;

    /**
     * @var list<string>
     */
    protected $appends = ['photo_url', 'photo_thumbnail_url'];

    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
    ];

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => ImageUploader::url($this->photo_path));
    }

    protected function photoThumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => ImageUploader::thumbnailUrl($this->photo_path));
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('division')->orderBy('name');
    }
}
