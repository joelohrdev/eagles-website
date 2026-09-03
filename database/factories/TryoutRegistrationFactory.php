<?php

namespace Database\Factories;

use App\Models\Tryout;
use App\Models\TryoutRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TryoutRegistration>
 */
class TryoutRegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tryout_id' => Tryout::factory(),
            'player_first_name' => fake()->firstName(),
            'player_last_name' => fake()->lastName(),
            'player_birthdate' => fake()->dateTimeBetween('-17 years', '-8 years'),
            'parent_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'current_team' => fake()->optional()->company(),
            'primary_position' => fake()->randomElement(['P', 'C', '1B', '2B', 'SS', '3B', 'OF']),
            'notes' => null,
            'registered_at' => now(),
        ];
    }
}
