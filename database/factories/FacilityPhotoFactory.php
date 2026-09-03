<?php

namespace Database\Factories;

use App\Models\FacilityPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacilityPhoto>
 */
class FacilityPhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'image_path' => 'facility/'.fake()->uuid().'.webp',
            'caption' => fake()->optional()->sentence(4),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
