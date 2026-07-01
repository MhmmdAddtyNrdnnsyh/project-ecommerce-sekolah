<?php

namespace Database\Factories;

use App\Enums\OrderItemStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
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
            'payment_status' => PaymentStatus::Unpaid,
            'payment_method' => PaymentMethod::Cash,
            'payment_confirmed_at' => null,
            'payment_confirmed_by' => null,
            'payment_rejection_reason' => null,
            'is_pre_order' => false,
            'pre_order_estimate_days' => null,
            'pre_order_deadline' => null,
            'pre_order_min_quantity' => null,
            'pre_order_note' => null,
        ];
    }
}
