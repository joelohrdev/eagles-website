<?php

namespace Database\Factories;

use App\Models\Camp;
use App\Models\CampCoach;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampCoach>
 */
class CampCoachFactory extends Factory
{
    public function definition(): array
    {
        return [
            'camp_id' => Camp::factory(),
            'name' => fake()->name(),
            'title' => fake()->optional()->randomElement(['Pitching Instructor', 'Hitting Coach', 'Guest Instructor']),
            'bio' => fake()->paragraph(),
            'photo_path' => null,
            'position' => 0,
        ];
    }
}
