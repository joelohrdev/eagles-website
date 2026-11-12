<?php

namespace Database\Factories;

use App\Models\Camp;
use App\Models\CampDayTime;
use App\Models\CampSession;
use App\Models\CampSessionDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Camp>
 */
class CampFactory extends Factory
{
    /**
     * Give each camp one session at its price with a single day on its start date,
     * matching how camps from before sessions were backfilled.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Camp $camp): void {
            $session = CampSession::factory()->for($camp)->create(['name' => $camp->name, 'price' => $camp->price]);
            $day = CampSessionDay::factory()->for($session, 'session')->create(['date' => $camp->starts_at->toDateString()]);
            CampDayTime::factory()->for($day, 'day')->create([
                'starts_at' => $camp->starts_at->format('H:i:s'),
                'ends_at' => $camp->ends_at?->format('H:i:s'),
            ]);
        });
    }

    /**
     * Create the camp with no sessions, for tests that build their own schedule.
     */
    public function withoutSchedule(): static
    {
        return $this->afterCreating(fn (Camp $camp) => $camp->sessions()->delete());
    }

    public function definition(): array
    {
        $starts = fake()->dateTimeBetween('+2 weeks', '+3 months');

        return [
            'name' => fake()->randomElement(['Winter Hitting Camp', 'Summer Skills Camp', 'Pitching & Catching Clinic', 'Fall Fundamentals Camp']),
            'description' => fake()->paragraphs(2, true),
            'location' => 'Eagles Training Facility',
            'age_range' => fake()->randomElement(['9U–12U', '13U–14U', '15U–17U', 'All ages']),
            'starts_at' => $starts,
            'ends_at' => (clone $starts)->modify('+3 hours'),
            'price' => fake()->randomElement([0, 7500, 12500, 19900]),
            'capacity' => fake()->optional()->numberBetween(10, 40),
            'registration_opens_at' => now()->subWeek(),
            'registration_closes_at' => (clone $starts)->modify('-1 day'),
            'image_path' => null,
            'youtube_url' => null,
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }

    public function free(): static
    {
        return $this->state(fn () => ['price' => 0]);
    }

    public function paid(int $price = 12500): static
    {
        return $this->state(fn () => ['price' => $price]);
    }

    public function registrationClosed(): static
    {
        return $this->state(fn () => ['registration_closes_at' => now()->subDay()]);
    }

    public function registrationUpcoming(): static
    {
        return $this->state(fn () => ['registration_opens_at' => now()->addWeek()]);
    }

    public function past(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subMonth()->addHours(3),
            'registration_closes_at' => now()->subMonth()->subDay(),
        ]);
    }
}
