<?php

use App\Enums\ProductSalesMethod;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('seller can view inventory list', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['name' => 'Pulpen', 'stock' => 10]);

    $this->actingAs($seller);

    $response = $this->get(route('seller.inventory.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('seller/inventory/index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Pulpen')
            ->has('summary'),
        );
});

test('inventory summary shows correct counts', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['stock' => 0]);
    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['stock' => 3]);
    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['stock' => 50]);

    $this->actingAs($seller);

    $response = $this->get(route('seller.inventory.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.total', 3)
            ->where('summary.low_stock', 1)
            ->where('summary.out_of_stock', 1),
        );
});

test('inventory can filter by stock condition', function () {
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

    $response = $this->get(route('seller.inventory.index', ['stock' => 'out']));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Stok Habis'),
        );

    $response = $this->get(route('seller.inventory.index', ['stock' => 'low']));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Stok Rendah'),
        );
});

test('inventory can search by name or slug', function () {
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

    $response = $this->get(route('seller.inventory.index', ['q' => 'Pulpen']));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Pulpen Gel Hitam'),
        );
});

test('inventory only shows seller own products', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $otherSeller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['name' => 'Produk Saya']);
    Product::factory()
        ->for($otherSeller, 'seller')
        ->for($category)
        ->create(['name' => 'Produk Orang Lain']);

    $this->actingAs($seller);

    $response = $this->get(route('seller.inventory.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Produk Saya'),
        );
});

test('seller can update inventory stock successfully', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $product = Product::factory()
        ->approved()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['stock' => 10, 'status' => ProductStatus::Approved]);

    $this->actingAs($seller);

    $response = $this->from(route('seller.inventory.index'))
        ->patch(route('seller.inventory.update', $product), [
            'stock' => 25,
        ]);

    $response->assertRedirect(route('seller.inventory.index'));
    $response->assertSessionHas('success', 'Stok produk berhasil diperbarui.');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock' => 25,
        'status' => ProductStatus::Approved->value,
    ]);
});

test('seller can set stock to zero', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['stock' => 10]);

    $this->actingAs($seller);

    $this->patch(route('seller.inventory.update', $product), ['stock' => 0]);

    $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 0]);
});

test('inventory update validates stock input', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['stock' => 10]);

    $this->actingAs($seller);

    $response = $this->from(route('seller.inventory.index'))
        ->patch(route('seller.inventory.update', $product), [
            'stock' => -1,
        ]);

    $response->assertRedirect(route('seller.inventory.index'));
    $response->assertSessionHasErrors('stock');
});

test('seller cannot update another sellers inventory', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $otherSeller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $product = Product::factory()
        ->for($otherSeller, 'seller')
        ->for($category)
        ->create(['stock' => 10]);

    $this->actingAs($seller);

    $this->patch(route('seller.inventory.update', $product), ['stock' => 25])
        ->assertForbidden();
});

test('seller cannot update up jurusan consigned product inventory', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'sales_method' => ProductSalesMethod::UpJurusan,
            'stock' => 8,
        ]);

    $this->actingAs($seller)
        ->patch(route('seller.inventory.update', $product), ['stock' => 25])
        ->assertForbidden();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock' => 8,
    ]);
});

test('non seller users cannot access inventory', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user);

    $this->get(route('seller.inventory.index'))->assertForbidden();
})->with([
    UserRole::Admin,
    UserRole::Buyer,
    UserRole::PicketOfficer,
]);
