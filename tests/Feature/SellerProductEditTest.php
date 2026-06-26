<?php

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('seller can visit the edit product page for their own product', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create(['name' => 'Alat Tulis', 'slug' => 'alat-tulis']);
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'name' => 'Pulpen Gel Hitam',
            'slug' => 'pulpen-gel-hitam',
            'description' => 'Pulpen gel hitam untuk catatan harian siswa.',
            'price' => 5000,
            'stock' => 12,
        ]);

    $this->actingAs($seller);

    $response = $this->get(route('seller.products.edit', $product));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('seller/products/edit')
            ->where('product.id', $product->id)
            ->where('product.name', 'Pulpen Gel Hitam')
            ->where('product.category_id', $category->id)
            ->has('categories', 1),
        );
});

test('seller cannot edit another sellers product', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $otherSeller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $product = Product::factory()
        ->for($otherSeller, 'seller')
        ->for($category)
        ->create();

    $this->actingAs($seller);

    $this->get(route('seller.products.edit', $product))->assertForbidden();

    $this->put(route('seller.products.update', $product), [
        'name' => 'Produk Tidak Sah',
        'category_id' => $category->id,
        'description' => 'Deskripsi update yang valid untuk produk.',
        'price' => 10000,
        'stock' => 3,
    ])->assertForbidden();
});

test('seller product update validation is explicit', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()
        ->for($seller, 'seller')
        ->create();

    $this->actingAs($seller);

    $response = $this
        ->from(route('seller.products.edit', $product))
        ->put(route('seller.products.update', $product), [
            'name' => 'AB',
            'category_id' => 999,
            'description' => 'Pendek',
            'price' => 0,
        ]);

    $response
        ->assertRedirect(route('seller.products.edit', $product))
        ->assertSessionHasErrors([
            'name',
            'category_id',
            'description',
            'price',
        ]);
});

test('approved product returns to pending when seller edits it', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $newCategory = Category::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->approved()
        ->create([
            'name' => 'Pulpen Lama',
            'slug' => 'pulpen-lama',
            'description' => 'Pulpen lama yang sudah approved.',
            'price' => 5000,
            'stock' => 10,
        ]);

    $this->actingAs($seller);

    $response = $this
        ->from(route('seller.products.edit', $product))
        ->put(route('seller.products.update', $product), [
            'name' => 'Pulpen Gel Biru',
            'category_id' => $newCategory->id,
            'description' => 'Pulpen gel biru untuk catatan harian siswa.',
            'price' => 6000,
            'stock' => 8,
        ]);

    $response->assertRedirect(route('seller.products.index'));

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'seller_id' => $seller->id,
        'category_id' => $newCategory->id,
        'name' => 'Pulpen Gel Biru',
        'slug' => 'pulpen-gel-biru',
        'description' => 'Pulpen gel biru untuk catatan harian siswa.',
        'price' => 6000,
        'stock' => 10,
        'status' => ProductStatus::Pending->value,
    ]);
});

test('seller product update does not require stock because inventory owns stock changes', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['stock' => 12]);

    $this->actingAs($seller)
        ->from(route('seller.products.edit', $product))
        ->put(route('seller.products.update', $product), [
            'name' => 'Pulpen Gel Merah',
            'category_id' => $category->id,
            'description' => 'Pulpen gel merah untuk catatan harian siswa.',
            'price' => 7000,
        ])
        ->assertRedirect(route('seller.products.index'));

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Pulpen Gel Merah',
        'stock' => 12,
    ]);
});
