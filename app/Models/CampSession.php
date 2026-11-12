<?php

namespace App\Models;

use Database\Factories\CampSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A bookable part of a camp (e.g. Pitching) made of one or more days.
 * Booking every day costs `price`; single days cost `day_price` each,
 * and are not offered when `day_price` is null.
 *
 * @property int $id
 * @property int $camp_id
 * @property string $name
 * @property string|null $description
 * @property int|null $price Null while a draft camp's price is still unknown.
 * @property int|null $day_price
 */
#[Fillable(['camp_id', 'name', 'description', 'price', 'day_price'])]
class CampSession extends Model
{
    /** @use HasFactory<CampSessionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'day_price' => 'integer',
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
     * @return HasMany<CampSessionDay, $this>
     */
    public function days(): HasMany
    {
        return $this->hasMany(CampSessionDay::class)->orderBy('date')->orderBy('id');
    }

    public function allowsSingleDays(): bool
    {
        return $this->day_price !== null;
    }
}
