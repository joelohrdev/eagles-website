<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'description' => 'Eagles Dri-Fit Tee',
            'size' => 'M',
            'color' => 'Navy',
            'unit_price' => 2500,
            'quantity' => 1,
        ];
    }
}
