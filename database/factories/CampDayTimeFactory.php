<?php

namespace Database\Factories;

use App\Models\CampDayTime;
use App\Models\CampSessionDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampDayTime>
 */
class CampDayTimeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'camp_session_day_id' => CampSessionDay::factory(),
            'camp_age_group_id' => null,
            'starts_at' => '17:30:00',
            'ends_at' => '19:00:00',
        ];
    }
}
