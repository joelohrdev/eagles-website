<?php

namespace Database\Factories;

use App\Models\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sponsor>
 */
class SponsorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'website_url' => fake()->optional()->url(),
            'logo_path' => 'sponsors/'.fake()->sha1().'.webp',
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
