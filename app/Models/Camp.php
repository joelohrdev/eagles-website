<?php

namespace App\Models;

use App\Models\Concerns\HasRegistrationWindow;
use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\HasSlug;
use App\Services\ImageUploader;
use Database\Factories\CampFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $location
 * @property string|null $age_range
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property int $price
 * @property int|null $capacity
 * @property Carbon|null $registration_opens_at
 * @property Carbon|null $registration_closes_at
 * @property string|null $image_path
 * @property string|null $youtube_url
 * @property bool $is_published
 */
#[Fillable(['name', 'slug', 'description', 'location', 'age_range', 'starts_at', 'ends_at', 'price', 'capacity', 'registration_opens_at', 'registration_closes_at', 'image_path', 'youtube_url', 'is_published'])]
class Camp extends Model
{
    /** @use HasFactory<CampFactory> */
    use HasFactory, HasRegistrationWindow, HasSeoMeta, HasSlug;

    /**
     * @var list<string>
     */
    protected $appends = ['image_url', 'image_thumbnail_url'];

    protected $attributes = [
        'price' => 0,
        'is_published' => false,
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
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'price' => 'integer',
            'capacity' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CampRegistration::class);
    }

    public function isFree(): bool
    {
        return $this->price === 0;
    }

    /**
     * Paid registrations plus unexpired pending ones count against capacity.
     */
    public function activeRegistrationCount(): int
    {
        return $this->registrations()->countsAgainstCapacity()->count();
    }

    #[Scope]
    protected function upcoming(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('ends_at', '>=', now())
            ->orWhere(fn (Builder $q2) => $q2->whereNull('ends_at')->where('starts_at', '>=', now()->startOfDay()))
        );
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('starts_at');
    }
}
