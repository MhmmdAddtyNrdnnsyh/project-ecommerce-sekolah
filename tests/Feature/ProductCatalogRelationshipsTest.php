<?php

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;

test('a seller can have products', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->create();

    expect($seller->products()->first()->is($product))->toBeTrue();
});

test('a category can have products', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();

    expect($category->products()->first()->is($product))->toBeTrue();
});

test('a product belongs to a seller and category', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();

    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['status' => ProductStatus::Pending]);

    expect($product->seller->is($seller))->toBeTrue()
        ->and($product->category->is($category))->toBeTrue()
        ->and($product->status)->toBe(ProductStatus::Pending);
});
