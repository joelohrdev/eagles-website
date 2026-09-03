<?php

namespace Database\Factories;

use App\Models\Coach;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        $division = fake()->randomElement(['9U', '10U', '11U', '12U', '13U', '14U', '15U', '16U', '17U']);

        return [
            'name' => "Eagles {$division} ".fake()->randomElement(['Navy', 'Blue', 'White', 'Elite']),
            'division' => $division,
            'season' => (string) now()->year,
            'description' => fake()->paragraph(),
            'photo_path' => null,
            'coach_id' => Coach::factory(),
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withoutCoach(): static
    {
        return $this->state(fn () => ['coach_id' => null]);
    }
}
