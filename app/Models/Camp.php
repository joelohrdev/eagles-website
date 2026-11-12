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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $location Place name, e.g. Eagles Training Facility.
 * @property string|null $address Street address, used for map links.
 * @property string|null $age_range
 * @property Carbon|null $starts_at First session start; null for a draft with no dates yet.
 * @property Carbon|null $ends_at
 * @property int $price Cheapest way in, synced from the schedule.
 * @property int|null $bundle_price Price for every day of every session; null means no bundle.
 * @property int|null $capacity Players per day when the camp has no age groups.
 * @property Carbon|null $registration_opens_at
 * @property Carbon|null $registration_closes_at
 * @property string|null $image_path
 * @property string|null $youtube_url
 * @property bool $is_published
 * @property int|null $active_registrations_count
 */
#[Fillable(['name', 'slug', 'description', 'location', 'address', 'age_range', 'starts_at', 'ends_at', 'price', 'bundle_price', 'capacity', 'registration_opens_at', 'registration_closes_at', 'image_path', 'youtube_url', 'is_published'])]
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

    /**
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(get: fn (): ?string => ImageUploader::url($this->image_path));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function imageThumbnailUrl(): Attribute
    {
        return Attribute::make(get: fn (): ?string => ImageUploader::thumbnailUrl($this->image_path));
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'price' => 'integer',
            'bundle_price' => 'integer',
            'capacity' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CampRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(CampRegistration::class);
    }

    /**
     * @return HasMany<CampAgeGroup, $this>
     */
    public function ageGroups(): HasMany
    {
        return $this->hasMany(CampAgeGroup::class)->orderBy('id');
    }

    /**
     * Sessions in schedule order (by their first day).
     *
     * @return HasMany<CampSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(CampSession::class)
            ->orderBy(CampSessionDay::query()->select('date')->whereColumn('camp_session_days.camp_session_id', 'camp_sessions.id')->orderBy('date')->limit(1))
            ->orderBy('id');
    }

    /**
     * @return HasManyThrough<CampSessionDay, CampSession, $this>
     */
    public function days(): HasManyThrough
    {
        return $this->hasManyThrough(CampSessionDay::class, CampSession::class);
    }

    /**
     * Coaches shown on this camp's page, in the order set in the camp form.
     *
     * @return HasMany<CampCoach, $this>
     */
    public function coaches(): HasMany
    {
        return $this->hasMany(CampCoach::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return BelongsToMany<Coupon, $this>
     */
    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class);
    }

    /**
     * Load everything the schedule, pricing and capacity checks read.
     */
    public function loadSchedule(): static
    {
        return $this->loadMissing(['ageGroups', 'sessions.days.times']);
    }

    /** @var array<string, int>|null */
    private ?array $takenSlots = null;

    /**
     * Every (day, age group) slot with how many players it holds and how many it takes.
     * A camp without age groups has one slot per day (age group null) sized by `capacity`.
     *
     * @return list<array{day_id: int, age_group_id: int|null, capacity: int|null, taken: int, remaining: int|null}>
     */
    public function slots(): array
    {
        $this->loadSchedule();
        $taken = $this->takenSlots ??= $this->countTakenSlots();
        $groups = $this->ageGroups->isEmpty() ? collect([null]) : $this->ageGroups;
        $slots = [];

        foreach ($this->sessions as $session) {
            foreach ($session->days as $day) {
                foreach ($groups as $group) {
                    $capacity = $group ? $group->capacity : $this->capacity;
                    $count = $taken[$day->id.':'.($group?->id ?? '')] ?? 0;

                    $slots[] = [
                        'day_id' => $day->id,
                        'age_group_id' => $group?->id,
                        'capacity' => $capacity,
                        'taken' => $count,
                        'remaining' => $capacity === null ? null : max(0, $capacity - $count),
                    ];
                }
            }
        }

        return $slots;
    }

    /**
     * Players remaining in one slot, or null when it is unlimited.
     */
    public function slotRemaining(int $dayId, ?int $ageGroupId): ?int
    {
        foreach ($this->slots() as $slot) {
            if ($slot['day_id'] === $dayId && $slot['age_group_id'] === $ageGroupId) {
                return $slot['remaining'];
            }
        }

        return 0;
    }

    /**
     * Forget the cached slot counts after registrations change in this request.
     */
    public function refreshSlots(): void
    {
        $this->takenSlots = null;
    }

    /**
     * A camp is full only when every slot is capped and has no room left.
     */
    public function isFull(): bool
    {
        $slots = $this->slots();

        return $slots !== [] && collect($slots)->every(fn (array $slot): bool => $slot['remaining'] === 0);
    }

    /**
     * Total open places across every slot, or null when any slot is unlimited.
     */
    public function spotsRemaining(): ?int
    {
        $slots = collect($this->slots());

        if ($slots->isEmpty() || $slots->contains(fn (array $slot): bool => $slot['remaining'] === null)) {
            return null;
        }

        return (int) $slots->sum('remaining');
    }

    /**
     * @return array<string, int> keyed by "dayId:ageGroupId" (empty age group for camps without groups)
     */
    private function countTakenSlots(): array
    {
        return DB::table('camp_registration_days')
            ->join('camp_registrations', 'camp_registrations.id', '=', 'camp_registration_days.camp_registration_id')
            ->whereIn('camp_registrations.id', $this->registrations()->countsAgainstCapacity()->select('id'))
            ->groupBy('camp_registration_days.camp_session_day_id', 'camp_registrations.camp_age_group_id')
            ->select('camp_registration_days.camp_session_day_id as day_id', 'camp_registrations.camp_age_group_id as age_group_id', DB::raw('count(*) as taken'))
            ->get()
            ->mapWithKeys(fn (object $row): array => [$row->day_id.':'.($row->age_group_id ?? '') => (int) $row->taken])
            ->all();
    }

    /**
     * More than one way to book (several sessions, or single days), so the
     * listed price is a "from" price.
     */
    public function hasPricingOptions(): bool
    {
        $this->loadMissing('sessions');

        return $this->sessions->count() > 1 || $this->sessions->contains(fn (CampSession $session): bool => $session->day_price !== null);
    }

    /**
     * A Google Maps search link for the address (with the place name), or null without an address.
     */
    public function mapUrl(): ?string
    {
        if (blank($this->address)) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(trim(($this->location ? "{$this->location}, " : '').$this->address));
    }

    public function isFree(): bool
    {
        return $this->price === 0;
    }

    /**
     * Paid registrations plus unexpired pending ones count against capacity.
     *
     * @return HasMany<CampRegistration, $this>
     */
    public function activeRegistrations(): HasMany
    {
        return $this->registrations()->countsAgainstCapacity();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function upcoming(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('ends_at', '>=', now())
            ->orWhere(fn (Builder $q2) => $q2->whereNull('ends_at')->where('starts_at', '>=', now()->startOfDay()))
        );
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('starts_at');
    }
}
