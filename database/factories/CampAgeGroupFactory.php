<?php

namespace Database\Factories;

use App\Models\Camp;
use App\Models\CampAgeGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampAgeGroup>
 */
class CampAgeGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'camp_id' => Camp::factory()->withoutSchedule(),
            'name' => fake()->randomElement(['8–12U', '13–17U']),
            'capacity' => null,
        ];
    }
}
