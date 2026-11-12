<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\Camp;
use App\Models\CampRegistration;
use App\Models\CampSessionDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampRegistration>
 */
class CampRegistrationFactory extends Factory
{
    /**
     * Book the player for every day of the camp unless the test picks days itself
     * (`->forDays(...)`), so registrations count against capacity like real ones.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (CampRegistration $registration): void {
            if (! $registration->days()->exists()) {
                $registration->days()->sync($registration->camp->days()->pluck('camp_session_days.id'));
            }
        });
    }

    /**
     * @param  iterable<CampSessionDay>  $days
     */
    public function forDays(iterable $days): static
    {
        return $this->afterCreating(fn (CampRegistration $registration) => $registration->days()->sync(collect($days)->pluck('id')));
    }

    public function definition(): array
    {
        return [
            'camp_id' => Camp::factory(),
            'order_id' => null,
            'player_first_name' => fake()->firstName(),
            'player_last_name' => fake()->lastName(),
            'player_birthdate' => fake()->dateTimeBetween('-17 years', '-8 years'),
            'parent_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
            'medical_notes' => null,
            'status' => RegistrationStatus::Paid,
            'registered_at' => now(),
            'expires_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => RegistrationStatus::PendingPayment,
            'expires_at' => now()->addMinutes(CampRegistration::PENDING_HOLD_MINUTES),
        ]);
    }

    public function expiredPending(): static
    {
        return $this->state(fn () => [
            'status' => RegistrationStatus::PendingPayment,
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => RegistrationStatus::Cancelled]);
    }
}
