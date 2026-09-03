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
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $camp_id
 * @property int|null $order_id
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
#[Fillable(['camp_id', 'order_id', 'player_first_name', 'player_last_name', 'player_birthdate', 'parent_name', 'email', 'phone', 'emergency_contact_name', 'emergency_contact_phone', 'medical_notes', 'status', 'registered_at', 'expires_at'])]
class CampRegistration extends Model
{
    /** @use HasFactory<CampRegistrationFactory> */
    use HasFactory;

    /**
     * Minutes an unpaid registration holds a spot before it expires.
     */
    public const int PENDING_HOLD_MINUTES = 30;

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

    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function playerName(): string
    {
        return "{$this->player_first_name} {$this->player_last_name}";
    }

    #[Scope]
    protected function paid(Builder $query): Builder
    {
        return $query->where('status', RegistrationStatus::Paid);
    }

    /**
     * Registrations that currently hold a spot: paid, or pending and not yet expired.
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
