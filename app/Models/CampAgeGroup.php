<?php

namespace App\Models;

use Database\Factories\CampAgeGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A group of players (e.g. 8–12U) that trains at its own time each camp day.
 *
 * @property int $id
 * @property int $camp_id
 * @property string $name
 * @property int|null $capacity Players per group per day; null means unlimited.
 */
#[Fillable(['camp_id', 'name', 'capacity'])]
class CampAgeGroup extends Model
{
    /** @use HasFactory<CampAgeGroupFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Camp, $this>
     */
    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    /**
     * @return HasMany<CampRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(CampRegistration::class);
    }
}
