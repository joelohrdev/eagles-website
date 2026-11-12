<?php

namespace App\Models;

use Database\Factories\CampDayTimeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * When an age group trains on a camp day. Times are wall-clock times in the
 * app timezone (America/Chicago), stored as HH:MM:SS.
 *
 * @property int $id
 * @property int $camp_session_day_id
 * @property int|null $camp_age_group_id
 * @property string|null $starts_at Null while a draft camp's time is still unknown.
 * @property string|null $ends_at
 */
#[Fillable(['camp_session_day_id', 'camp_age_group_id', 'starts_at', 'ends_at'])]
class CampDayTime extends Model
{
    /** @use HasFactory<CampDayTimeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<CampSessionDay, $this>
     */
    public function day(): BelongsTo
    {
        return $this->belongsTo(CampSessionDay::class, 'camp_session_day_id');
    }

    /**
     * @return BelongsTo<CampAgeGroup, $this>
     */
    public function ageGroup(): BelongsTo
    {
        return $this->belongsTo(CampAgeGroup::class, 'camp_age_group_id');
    }

    /**
     * "5:30 PM – 7:00 PM", or just the start when there is no end time.
     */
    public function range(): string
    {
        if ($this->starts_at === null) {
            return 'Time TBD';
        }

        $start = Carbon::createFromFormat('H:i:s', self::normalize($this->starts_at))->format('g:i A');

        if ($this->ends_at === null) {
            return $start;
        }

        return $start.' – '.Carbon::createFromFormat('H:i:s', self::normalize($this->ends_at))->format('g:i A');
    }

    /**
     * Accept HH:MM as well as HH:MM:SS.
     */
    public static function normalize(string $time): string
    {
        return strlen($time) === 5 ? "{$time}:00" : $time;
    }
}
