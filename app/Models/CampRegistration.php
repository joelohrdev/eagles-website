<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Database\Factories\CampRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $camp_id
 * @property int|null $order_id
 * @property int|null $camp_age_group_id
 * @property string $player_first_name
 * @property string $player_last_name
 * @property Carbon $player_birthdate
 * @property string $parent_name
 * @property string $email
 * @property string $phone
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $medical_notes
 * @property RegistrationStatus $status
 * @property Carbon $registered_at
 * @property Carbon|null $expires_at
 */
#[Fillable(['camp_id', 'order_id', 'camp_age_group_id', 'player_first_name', 'player_last_name', 'player_birthdate', 'parent_name', 'email', 'phone', 'emergency_contact_name', 'emergency_contact_phone', 'medical_notes', 'status', 'registered_at', 'expires_at'])]
class CampRegistration extends Model
{
    /** @use HasFactory<CampRegistrationFactory> */
    use HasFactory;

    /**
     * Minutes an unpaid registration holds a spot before it expires. The Stripe
     * checkout session closes at the same moment, so it must stay above Stripe's
     * 30-minute minimum session lifetime (measured from when Stripe creates it).
     */
    public const int PENDING_HOLD_MINUTES = 35;

    protected $attributes = [
        'status' => 'pending_payment',
    ];

    protected function casts(): array
    {
        return [
            'player_birthdate' => 'date',
            'status' => RegistrationStatus::class,
            'registered_at' => 'datetime',
            'expires_at' => 'datetime',
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
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<CampAgeGroup, $this>
     */
    public function ageGroup(): BelongsTo
    {
        return $this->belongsTo(CampAgeGroup::class, 'camp_age_group_id');
    }

    /**
     * The camp days this player is booked for, in date order.
     *
     * @return BelongsToMany<CampSessionDay, $this>
     */
    public function days(): BelongsToMany
    {
        return $this->belongsToMany(CampSessionDay::class, 'camp_registration_days')
            ->withTimestamps()
            ->orderBy('date');
    }

    /**
     * Each booked day with this player's time, e.g. "Sat, Nov 21 · 9:00 AM – 10:30 AM".
     *
     * @return list<string>
     */
    public function scheduleLines(): array
    {
        $this->loadMissing(['days.times', 'days.session']);

        return array_values($this->days->map(function (CampSessionDay $day): string {
            $time = $day->timeFor($this->camp_age_group_id) ?? $day->times->first();

            return "{$day->session->name}: {$day->title()}".($time ? " · {$time->range()}" : '');
        })->all());
    }

    /**
     * Booked days grouped by session, each with the player's time:
     * [['session' => 'Pitching', 'days' => [['title' => 'Sat, Nov 21', 'time' => '7:00 PM – 8:30 PM'], ...]], ...].
     *
     * @return list<array{session: string, days: list<array{title: string, time: string|null}>}>
     */
    public function scheduleBySession(): array
    {
        $this->loadMissing(['days.times', 'days.session']);

        return array_values($this->days
            ->groupBy('camp_session_id')
            ->map(fn ($days): array => [
                'session' => $days->first()->session->name,
                'days' => array_values($days->map(function (CampSessionDay $day): array {
                    $time = $day->timeFor($this->camp_age_group_id) ?? $day->times->first();

                    return ['title' => $day->title(), 'time' => $time?->range()];
                })->all()),
            ])
            ->all());
    }

    public function playerName(): string
    {
        return "{$this->player_first_name} {$this->player_last_name}";
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function paid(Builder $query): Builder
    {
        return $query->where('status', RegistrationStatus::Paid);
    }

    /**
     * Registrations that currently hold a spot: paid, or pending and not yet expired.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function countsAgainstCapacity(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('status', RegistrationStatus::Paid)
            ->orWhere(fn (Builder $q2) => $q2
                ->where('status', RegistrationStatus::PendingPayment)
                ->where(fn (Builder $q3) => $q3->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            )
        );
    }
}
