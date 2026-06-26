<?php

namespace Database\Factories;

use App\Enums\OrderItemStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->word(),
            'price' => fake()->numberBetween(1000, 100000),
            'quantity' => fake()->numberBetween(1, 5),
            'subtotal' => fn (array $attrs) => $attrs['price'] * $attrs['quantity'],
            'status' => OrderItemStatus::Pending,
        ];
    }
}
