<?php

namespace Database\Factories;

use App\Models\Camp;
use App\Models\CampSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampSession>
 */
class CampSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'camp_id' => Camp::factory()->withoutSchedule(),
            'name' => fake()->randomElement(['Pitching', 'Fielding & Hitting', 'Catching']),
            'description' => null,
            'price' => 12500,
            'day_price' => null,
        ];
    }

    public function singleDays(int $dayPrice = 7500): static
    {
        return $this->state(fn () => ['day_price' => $dayPrice]);
    }
}
