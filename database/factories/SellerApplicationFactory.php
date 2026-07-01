<?php

namespace Database\Factories;

use App\Models\SellerApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerApplication>
 */
class SellerApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'store_name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'product_plan' => fake()->sentence(),
            'reason' => fake()->sentence(),
            'status' => SellerApplication::PENDING,
        ];
    }
}
