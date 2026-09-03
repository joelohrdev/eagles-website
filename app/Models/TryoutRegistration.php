<?php

namespace App\Models;

use Database\Factories\TryoutRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tryout_id
 * @property string $player_first_name
 * @property string $player_last_name
 * @property Carbon $player_birthdate
 * @property string $parent_name
 * @property string $email
 * @property string $phone
 * @property string|null $current_team
 * @property string|null $primary_position
 * @property string|null $notes
 * @property Carbon $registered_at
 */
#[Fillable(['tryout_id', 'player_first_name', 'player_last_name', 'player_birthdate', 'parent_name', 'email', 'phone', 'current_team', 'primary_position', 'notes', 'registered_at'])]
class TryoutRegistration extends Model
{
    /** @use HasFactory<TryoutRegistrationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'player_birthdate' => 'date',
            'registered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tryout, $this>
     */
    public function tryout(): BelongsTo
    {
        return $this->belongsTo(Tryout::class);
    }

    public function playerName(): string
    {
        return "{$this->player_first_name} {$this->player_last_name}";
    }
}
