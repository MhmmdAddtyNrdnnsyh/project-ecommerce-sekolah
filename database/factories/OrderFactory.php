<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Support\TransactionCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'code' => TransactionCode::make(),
            'user_id' => User::factory(),
            'status' => OrderStatus::Pending,
            'total_price' => 0,
            'pickup_method' => 'pickup',
            'pickup_location' => null,
        ];
    }
}
