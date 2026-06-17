<?php

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('seller only sees their own products', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $otherSeller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create(['name' => 'Alat Tulis', 'slug' => 'alat-tulis']);

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'name' => 'Pulpen Gel Hitam',
            'slug' => 'pulpen-gel-hitam',
            'price' => 5000,
            'stock' => 12,
            'status' => ProductStatus::Approved,
        ]);

    Product::factory()
        ->for($otherSeller, 'seller')
        ->for($category)
        ->create([
            'name' => 'Produk Seller Lain',
            'slug' => 'produk-seller-lain',
        ]);

    $this->actingAs($seller);

    $response = $this->get(route('seller.products.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('seller/products/index')
            ->has('products', 1)
            ->where('products.0.name', 'Pulpen Gel Hitam')
            ->where('products.0.slug', 'pulpen-gel-hitam')
            ->where('products.0.category.name', 'Alat Tulis')
            ->where('products.0.price', 5000)
            ->where('products.0.stock', 12)
            ->where('products.0.status.code', ProductStatus::Approved->value),
        );
});

test('unverified seller is redirected from seller products index', function () {
    $seller = User::factory()->unverified()->create([
        'role' => UserRole::Seller,
    ]);

    $this->actingAs($seller);

    $response = $this->get(route('seller.products.index'));

    $response->assertRedirect(route('verification.notice'));
});

test('non seller users cannot access seller products index', function (UserRole $role) {
    $user = User::factory()->create([
        'role' => $role,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('seller.products.index'));

    $response->assertForbidden();
})->with([
    UserRole::Admin,
    UserRole::Buyer,
    UserRole::PicketOfficer,
]);
