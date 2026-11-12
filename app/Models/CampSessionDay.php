<?php

namespace App\Models;

use Database\Factories\CampSessionDayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $camp_session_id
 * @property Carbon|null $date Null while a draft camp's date is still unknown.
 * @property string|null $label
 */
#[Fillable(['camp_session_id', 'date', 'label'])]
class CampSessionDay extends Model
{
    /** @use HasFactory<CampSessionDayFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<CampSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(CampSession::class, 'camp_session_id');
    }

    /**
     * @return HasMany<CampDayTime, $this>
     */
    public function times(): HasMany
    {
        return $this->hasMany(CampDayTime::class)->orderBy('starts_at');
    }

    /**
     * @return BelongsToMany<CampRegistration, $this>
     */
    public function registrations(): BelongsToMany
    {
        return $this->belongsToMany(CampRegistration::class, 'camp_registration_days')->withTimestamps();
    }

    /**
     * The time slot for an age group, or the day's only slot when the camp has no age groups.
     */
    public function timeFor(?int $ageGroupId): ?CampDayTime
    {
        return $this->times->firstWhere('camp_age_group_id', $ageGroupId);
    }

    /**
     * "Sat, Nov 21" plus the label when there is one: "Tue, Nov 24 · Fielding".
     */
    public function title(): string
    {
        return ($this->date?->format('D, M j') ?? 'Date TBD').($this->label ? " · {$this->label}" : '');
    }
}
