<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * One entry in the admin activity log: what happened, to which record, who
 * did it (a staff member, a customer, Stripe or the system) and when. Written
 * by App\Services\ActivityLogger; never edited. Entries older than two years
 * are pruned.
 *
 * @property int $id
 * @property string $event e.g. camp_registration.created, order.refunded
 * @property string $description
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property int|null $user_id
 * @property string $actor Display name at the time: staff name, "Customer", "Stripe" or "System".
 * @property array<string, mixed>|null $properties
 * @property string|null $ip_address
 * @property Carbon $created_at
 */
#[Fillable(['event', 'description', 'subject_type', 'subject_id', 'user_id', 'actor', 'properties', 'ip_address', 'created_at'])]
class ActivityLog extends Model
{
    use Prunable;

    public const int KEEP_YEARS = 2;

    public const null UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subYears(self::KEEP_YEARS));
    }
}
