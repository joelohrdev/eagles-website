<?php

namespace Database\Factories;

use App\Models\CampSession;
use App\Models\CampSessionDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampSessionDay>
 */
class CampSessionDayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'camp_session_id' => CampSession::factory(),
            'date' => fake()->dateTimeBetween('+2 weeks', '+3 months')->format('Y-m-d'),
            'label' => null,
        ];
    }
}
