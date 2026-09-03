<?php

namespace Database\Factories;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeoMeta>
 */
class SeoMetaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'route_key' => fake()->unique()->slug(2),
            'title' => fake()->sentence(5),
            'description' => fake()->sentence(15),
            'canonical_url' => null,
            'robots' => null,
            'share_title' => null,
            'share_description' => null,
            'share_image_path' => null,
            'share_image_alt' => null,
            'twitter_card' => null,
            'json_ld' => null,
        ];
    }

    public function forRoute(string $routeKey): static
    {
        return $this->state(fn () => ['route_key' => $routeKey]);
    }
}
