<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Eagles Dri-Fit Tee', 'Eagles Hoodie', 'Eagles Snapback', 'Eagles Practice Jersey', 'Eagles Windbreaker']),
            'description' => fake()->paragraph(),
            'price' => fake()->randomElement([2500, 3500, 4500, 6000]),
            'image_path' => null,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
