<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = trim(fake()->unique()->sentence(3), '.');

        return [
            'seller_id' => User::factory()->state(['role' => UserRole::Seller]),
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(5_000, 250_000),
            'stock' => fake()->numberBetween(0, 50),
            'status' => fake()->randomElement(ProductStatus::cases()),
            'image' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Approved,
        ]);
    }
}
