<?php

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('public catalog only shows approved products with available stock', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create([
        'name' => 'Alat Tulis',
        'slug' => 'alat-tulis',
    ]);

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'name' => 'Pulpen Gel Hitam',
            'slug' => 'pulpen-gel-hitam',
            'status' => ProductStatus::Approved,
            'stock' => 12,
        ]);

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'name' => 'Produk Draft',
            'status' => ProductStatus::Draft,
            'stock' => 12,
        ]);

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'name' => 'Produk Pending',
            'status' => ProductStatus::Pending,
            'stock' => 12,
        ]);

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'name' => 'Produk Rejected',
            'status' => ProductStatus::Rejected,
            'stock' => 12,
        ]);

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->approved()
        ->create([
            'name' => 'Produk Habis',
            'status' => ProductStatus::Approved,
            'stock' => 0,
        ]);

    $response = $this->get(route('catalog.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/index')
            ->where('filters.search', '')
            ->where('filters.category', '')
            ->has('categories', 1)
            ->where('categories.0.name', 'Alat Tulis')
            ->has('products', 1)
            ->where('products.0.name', 'Pulpen Gel Hitam')
            ->where('products.0.stock', 12)
            ->where('products.0.category.name', 'Alat Tulis'),
        );
});

test('public catalog can search products from query string', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->approved()
        ->create([
            'name' => 'Pulpen Gel Hitam',
            'description' => 'Alat tulis untuk catatan rapi.',
            'stock' => 8,
        ]);

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->approved()
        ->create([
            'name' => 'Buku Tulis Polos',
            'description' => 'Buku latihan harian.',
            'stock' => 8,
        ]);

    $response = $this->get(route('catalog.index', ['search' => 'pulpen']));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/index')
            ->where('filters.search', 'pulpen')
            ->has('products', 1)
            ->where('products.0.name', 'Pulpen Gel Hitam'),
        );
});

test('public catalog can filter products by category query string', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $stationery = Category::factory()->create([
        'name' => 'Alat Tulis',
        'slug' => 'alat-tulis',
    ]);
    $books = Category::factory()->create([
        'name' => 'Buku',
        'slug' => 'buku',
    ]);

    Product::factory()
        ->for($seller, 'seller')
        ->for($stationery)
        ->approved()
        ->create([
            'name' => 'Pulpen Gel Hitam',
            'stock' => 8,
        ]);

    Product::factory()
        ->for($seller, 'seller')
        ->for($books)
        ->approved()
        ->create([
            'name' => 'Buku Tulis Polos',
            'stock' => 8,
        ]);

    $response = $this->get(route('catalog.index', ['category' => 'buku']));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/index')
            ->where('filters.category', 'buku')
            ->has('products', 1)
            ->where('products.0.name', 'Buku Tulis Polos')
            ->where('products.0.category.name', 'Buku'),
        );
});

test('public can see approved product detail by slug', function () {
    $seller = User::factory()->create([
        'name' => 'Toko Belajar',
        'role' => UserRole::Seller,
    ]);
    $category = Category::factory()->create([
        'name' => 'Buku',
        'slug' => 'buku',
    ]);
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->approved()
        ->create([
            'name' => 'Buku Tulis Polos',
            'slug' => 'buku-tulis-polos',
            'description' => 'Buku tulis untuk catatan harian.',
            'price' => 12000,
            'stock' => 0,
        ]);

    $response = $this->get(route('catalog.show', ['product' => $product->slug]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/show')
            ->where('product.name', 'Buku Tulis Polos')
            ->where('product.slug', 'buku-tulis-polos')
            ->where('product.description', 'Buku tulis untuk catatan harian.')
            ->where('product.price', 12000)
            ->where('product.stock', 0)
            ->where('product.category.name', 'Buku')
            ->where('product.seller.name', 'Toko Belajar'),
        );
});

test('public cannot see product detail when status is not approved', function (ProductStatus $status) {
    $product = Product::factory()->create([
        'slug' => 'produk-'.$status->value,
        'status' => $status,
    ]);

    $this
        ->get(route('catalog.show', ['product' => $product->slug]))
        ->assertNotFound();
})->with([
    ProductStatus::Draft,
    ProductStatus::Pending,
    ProductStatus::Rejected,
]);
