<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared registration-window logic for tryouts and camps.
 * Requires registration_opens_at, registration_closes_at, capacity columns
 * and a registrations() relationship.
 */
trait HasRegistrationWindow
{
    public function isRegistrationOpen(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        $now = now();

        if ($this->registration_opens_at && $now->lt($this->registration_opens_at)) {
            return false;
        }

        if ($this->registration_closes_at && $now->gt($this->registration_closes_at)) {
            return false;
        }

        return ! $this->isFull();
    }

    public function isRegistrationUpcoming(): bool
    {
        return $this->registration_opens_at !== null && now()->lt($this->registration_opens_at);
    }

    public function isRegistrationClosed(): bool
    {
        return $this->registration_closes_at !== null && now()->gt($this->registration_closes_at);
    }

    public function isFull(): bool
    {
        if ($this->capacity === null) {
            return false;
        }

        return $this->activeRegistrationCount() >= $this->capacity;
    }

    public function spotsRemaining(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->activeRegistrationCount());
    }

    /**
     * Number of registrations that count against capacity.
     */
    public function activeRegistrationCount(): int
    {
        return $this->registrations()->count();
    }

    /**
     * Human-readable registration state for the frontend.
     */
    public function registrationState(): string
    {
        return match (true) {
            $this->isRegistrationOpen() => 'open',
            $this->isRegistrationUpcoming() => 'upcoming',
            $this->isFull() => 'full',
            default => 'closed',
        };
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
