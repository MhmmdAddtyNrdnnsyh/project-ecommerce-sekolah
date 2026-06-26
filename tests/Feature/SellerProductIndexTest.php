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
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Pulpen Gel Hitam')
            ->where('products.data.0.slug', 'pulpen-gel-hitam')
            ->where('products.data.0.category.name', 'Alat Tulis')
            ->where('products.data.0.price', 5000)
            ->where('products.data.0.stock', 12)
            ->where('products.data.0.status.code', ProductStatus::Approved->value),
        );
});

test('seller can search products by name', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['name' => 'Pulpen Gel Hitam', 'slug' => 'pulpen-gel-hitam']);
    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['name' => 'Buku Tulis', 'slug' => 'buku-tulis']);

    $this->actingAs($seller);

    $response = $this->get(route('seller.products.index', ['q' => 'Pulpen']));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Pulpen Gel Hitam')
            ->where('filters.q', 'Pulpen'),
        );
});

test('seller can search products by slug', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['name' => 'Pulpen Gel Hitam', 'slug' => 'pulpen-gel-hitam']);
    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['name' => 'Buku Tulis', 'slug' => 'buku-tulis']);

    $this->actingAs($seller);

    $response = $this->get(route('seller.products.index', ['q' => 'buku-tulis']));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Buku Tulis'),
        );
});

test('seller can filter products by status', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['name' => 'Produk Approved', 'status' => ProductStatus::Approved]);
    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['name' => 'Produk Pending', 'status' => ProductStatus::Pending]);
    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['name' => 'Produk Rejected', 'status' => ProductStatus::Rejected]);

    $this->actingAs($seller);

    $response = $this->get(route('seller.products.index', ['status' => ProductStatus::Pending->value]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Produk Pending'),
        );
});

test('seller can filter products by category', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $categoryA = Category::factory()->create(['name' => 'Alat Tulis']);
    $categoryB = Category::factory()->create(['name' => 'Elektronik']);

    Product::factory()
        ->for($seller, 'seller')
        ->for($categoryA)
        ->create(['name' => 'Pulpen']);
    Product::factory()
        ->for($seller, 'seller')
        ->for($categoryB)
        ->create(['name' => 'Charger']);

    $this->actingAs($seller);

    $response = $this->get(route('seller.products.index', ['category_id' => $categoryA->id]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Pulpen'),
        );
});

test('seller can filter products by stock condition', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['name' => 'Stok Habis', 'stock' => 0]);
    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['name' => 'Stok Rendah', 'stock' => 3]);
    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['name' => 'Stok Cukup', 'stock' => 50]);

    $this->actingAs($seller);

    $response = $this->get(route('seller.products.index', ['stock' => 'out']));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Stok Habis'),
        );

    $response = $this->get(route('seller.products.index', ['stock' => 'low']));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Stok Rendah'),
        );
});

test('seller product index uses pagination with default 10 per page', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();

    Product::factory()
        ->count(12)
        ->for($seller, 'seller')
        ->for($category)
        ->create();

    $this->actingAs($seller);

    $response = $this->get(route('seller.products.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 10)
            ->etc(),
        );
});

test('seller product index query does not leak to other sellers', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $otherSeller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['name' => 'Produk Saya', 'status' => ProductStatus::Approved]);
    Product::factory()
        ->for($otherSeller, 'seller')
        ->for($category)
        ->create(['name' => 'Produk Orang Lain', 'status' => ProductStatus::Approved]);

    $this->actingAs($seller);

    $response = $this->get(route('seller.products.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Produk Saya'),
        );
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
