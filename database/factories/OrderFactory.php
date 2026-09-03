<?php

namespace Database\Factories;

use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(2500, 15000);

        return [
            'type' => OrderType::Merch,
            'email' => fake()->safeEmail(),
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'fulfillment' => Fulfillment::Pickup,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'status' => OrderStatus::Pending,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
            'stripe_checkout_session_id' => 'cs_test_'.fake()->lexify('????????????'),
            'stripe_payment_intent_id' => 'pi_'.fake()->lexify('????????????'),
        ]);
    }

    public function fulfilled(): static
    {
        return $this->paid()->state(fn () => [
            'status' => OrderStatus::Fulfilled,
            'fulfilled_at' => now(),
        ]);
    }

    public function camp(): static
    {
        return $this->state(fn () => ['type' => OrderType::Camp]);
    }

    public function shipping(): static
    {
        return $this->state(fn () => [
            'fulfillment' => Fulfillment::Shipping,
            'shipping_address_line1' => fake()->streetAddress(),
            'shipping_city' => fake()->city(),
            'shipping_state' => 'IL',
            'shipping_postal_code' => fake()->postcode(),
        ]);
    }
}
