<?php

namespace Database\Factories;

use App\Models\NavigationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NavigationItem>
 */
class NavigationItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location' => NavigationItem::HEADER,
            'label' => fake()->words(2, true),
            'route_name' => 'teams.index',
            'url' => null,
            'opens_in_new_tab' => false,
            'is_visible' => true,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }

    public function header(): static
    {
        return $this->state(fn () => ['location' => NavigationItem::HEADER]);
    }

    public function footer(): static
    {
        return $this->state(fn () => ['location' => NavigationItem::FOOTER]);
    }

    public function footerBottom(): static
    {
        return $this->state(fn () => ['location' => NavigationItem::FOOTER_BOTTOM]);
    }

    public function custom(string $url = 'https://example.com'): static
    {
        return $this->state(fn () => ['route_name' => null, 'url' => $url]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_visible' => false]);
    }
}
