<?php

namespace Database\Factories;

use App\Models\Tryout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tryout>
 */
class TryoutFactory extends Factory
{
    public function definition(): array
    {
        $division = fake()->randomElement(['9U', '10U', '11U', '12U', '13U', '14U', '15U', '16U', '17U']);
        $eventAt = fake()->dateTimeBetween('+1 week', '+2 months');

        return [
            'title' => "{$division} Tryouts",
            'division' => $division,
            'location' => 'Eagles Training Facility',
            'description' => fake()->paragraph(),
            'event_at' => $eventAt,
            'registration_opens_at' => now()->subWeek(),
            'registration_closes_at' => (clone $eventAt)->modify('-1 day'),
            'capacity' => fake()->optional()->numberBetween(15, 40),
            'image_path' => null,
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }

    public function registrationClosed(): static
    {
        return $this->state(fn () => ['registration_closes_at' => now()->subDay()]);
    }

    public function registrationUpcoming(): static
    {
        return $this->state(fn () => ['registration_opens_at' => now()->addWeek()]);
    }

    public function past(): static
    {
        return $this->state(fn () => [
            'event_at' => now()->subMonth(),
            'registration_closes_at' => now()->subMonth()->subDay(),
        ]);
    }
}
