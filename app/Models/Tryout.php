<?php

namespace App\Models;

use App\Models\Concerns\HasRegistrationWindow;
use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\HasSlug;
use App\Services\ImageUploader;
use Database\Factories\TryoutFactory;
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
 * @property string $title
 * @property string $slug
 * @property string $division
 * @property string|null $location
 * @property string|null $description
 * @property Carbon $event_at
 * @property Carbon|null $registration_opens_at
 * @property Carbon|null $registration_closes_at
 * @property int|null $capacity
 * @property string|null $image_path
 * @property bool $is_published
 */
#[Fillable(['title', 'slug', 'division', 'location', 'description', 'event_at', 'registration_opens_at', 'registration_closes_at', 'capacity', 'image_path', 'is_published'])]
class Tryout extends Model
{
    /** @use HasFactory<TryoutFactory> */
    use HasFactory, HasRegistrationWindow, HasSeoMeta, HasSlug;

    /**
     * @var list<string>
     */
    protected $appends = ['image_url', 'image_thumbnail_url'];

    protected $attributes = [
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
            'event_at' => 'datetime',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'capacity' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    protected function slugSource(): string
    {
        return 'title';
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TryoutRegistration::class);
    }

    #[Scope]
    protected function upcoming(Builder $query): Builder
    {
        return $query->where('event_at', '>=', now()->startOfDay());
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('event_at');
    }

    /**
     * Tryouts a visitor can register for right now: published, still upcoming,
     * inside the registration window, and not at capacity. This is the query
     * form of HasRegistrationWindow::isRegistrationOpen().
     */
    #[Scope]
    protected function openForRegistration(Builder $query): Builder
    {
        return $query
            ->published()
            ->upcoming()
            ->where(fn (Builder $window) => $window
                ->whereNull('registration_opens_at')
                ->orWhere('registration_opens_at', '<=', now()))
            ->where(fn (Builder $window) => $window
                ->whereNull('registration_closes_at')
                ->orWhere('registration_closes_at', '>=', now()))
            ->where(fn (Builder $capacity) => $capacity
                ->whereNull('capacity')
                ->orWhereRaw('capacity > (select count(*) from tryout_registrations where tryout_registrations.tryout_id = tryouts.id)'));
    }
}
