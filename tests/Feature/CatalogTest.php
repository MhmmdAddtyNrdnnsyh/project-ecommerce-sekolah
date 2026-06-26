<?php

use App\Enums\ProductSalesMethod;
use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
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

test('home page renders buyer landing catalog and can filter categories', function () {
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

    $this->get(route('home', ['category' => 'buku']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/index')
            ->where('filters.category', 'buku')
            ->has('categories', 2)
            ->has('products', 1)
            ->where('products.0.name', 'Buku Tulis Polos'),
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
            ->where('product.image', null)
            ->where('product.category.name', 'Buku')
            ->where('product.seller.name', 'Toko Belajar'),
        );
});

test('product detail includes pickup place for up jurusan products', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $upJurusan = UpJurusan::factory()->create(['name' => 'UP RPL']);
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->approved()
        ->create(['slug' => 'risol-mayo']);

    UpJurusanConsignment::factory()
        ->for($seller, 'seller')
        ->for($product)
        ->for($upJurusan)
        ->create();

    $this->get(route('catalog.show', ['product' => $product->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/show')
            ->where('product.pickup_place.name', 'UP RPL'));
});

test('public catalog can show products owned by up jurusan', function () {
    $category = Category::factory()->create(['name' => 'Makanan', 'slug' => 'makanan']);
    $upJurusan = UpJurusan::factory()->create(['name' => 'UP RPL']);
    $product = Product::factory()
        ->for($category)
        ->approved()
        ->create([
            'seller_id' => null,
            'up_jurusan_id' => $upJurusan->id,
            'name' => 'Produk Jurusan RPL',
            'slug' => 'produk-jurusan-rpl',
            'stock' => 5,
        ]);

    $this->get(route('catalog.index', ['search' => 'Produk Jurusan RPL']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/index')
            ->has('products', 1)
            ->where('products.0.id', $product->id)
            ->where('products.0.owner.type', 'up_jurusan')
            ->where('products.0.owner.name', 'UP RPL'));

    $this->get(route('catalog.show', ['product' => $product->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/show')
            ->where('product.owner.type', 'up_jurusan')
            ->where('product.owner.name', 'UP RPL'));
});

test('catalog uses received up jurusan consignment stock for consigned seller products', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $upJurusan = UpJurusan::factory()->create(['name' => 'UP RPL']);
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->approved()
        ->create([
            'name' => 'Risol Titipan',
            'slug' => 'risol-titipan',
            'sales_method' => ProductSalesMethod::UpJurusan,
            'stock' => 0,
        ]);

    UpJurusanConsignment::query()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
        'requested_quantity' => 8,
        'received_quantity' => 8,
        'sold_quantity' => 3,
        'commission_rate' => 0,
        'status' => UpJurusanConsignmentStatus::Received,
    ]);

    $this->get(route('catalog.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/index')
            ->has('products', 1)
            ->where('products.0.name', 'Risol Titipan')
            ->where('products.0.stock', 5));

    $this->get(route('catalog.show', ['product' => $product->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/show')
            ->where('product.stock', 5));
});

test('buyer cannot add out of stock product to cart', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create([
        'slug' => 'produk-habis',
        'stock' => 0,
    ]);

    $this->actingAs($buyer)
        ->from(route('catalog.show', $product))
        ->post(route('cart.items.store', ['product' => $product->slug]), [
            'quantity' => 1,
        ])
        ->assertRedirect(route('catalog.show', $product))
        ->assertSessionHasErrors('quantity');

    $this->assertDatabaseMissing('cart_items', [
        'user_id' => $buyer->id,
        'product_id' => $product->id,
    ]);
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
