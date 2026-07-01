<?php

use App\Enums\ProductFulfillmentType;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

test('seller can keep edited draft product as draft', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'name' => 'Draft Lama',
            'status' => ProductStatus::Draft,
        ]);

    $this->actingAs($seller)
        ->from(route('seller.products.edit', $product))
        ->put(route('seller.products.update', $product), [
            'name' => 'Draft Baru',
            'category_id' => $category->id,
            'description' => 'Produk draft yang masih belum siap diajukan.',
            'price' => 12000,
            'status' => ProductStatus::Draft->value,
        ])
        ->assertRedirect(route('seller.products.index'));

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Draft Baru',
        'status' => ProductStatus::Draft->value,
    ]);
});

test('seller can submit edited draft product for moderation', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'name' => 'Draft Lama',
            'status' => ProductStatus::Draft,
        ]);

    $this->actingAs($seller)
        ->from(route('seller.products.edit', $product))
        ->put(route('seller.products.update', $product), [
            'name' => 'Produk Siap Review',
            'category_id' => $category->id,
            'description' => 'Produk draft sudah lengkap dan siap diajukan.',
            'price' => 15000,
            'status' => ProductStatus::Pending->value,
        ])
        ->assertRedirect(route('seller.products.index'));

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Produk Siap Review',
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

test('seller can update product pre-order settings', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $deadline = now()->addDays(10)->toDateString();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'name' => 'Kaos Lama',
            'description' => 'Kaos lama untuk siswa.',
            'price' => 40000,
            'stock' => 5,
        ]);

    $this->actingAs($seller)
        ->from(route('seller.products.edit', $product))
        ->put(route('seller.products.update', $product), [
            'name' => 'Kaos Kelas PO',
            'category_id' => $category->id,
            'description' => 'Kaos kelas dibuat setelah pesanan terkumpul.',
            'price' => 50000,
            'fulfillment_type' => ProductFulfillmentType::PreOrder->value,
            'pre_order_estimate_days' => 10,
            'pre_order_deadline' => $deadline,
            'pre_order_min_quantity' => 8,
            'pre_order_note' => 'Produksi setelah kuota minimum tercapai.',
        ])
        ->assertRedirect(route('seller.products.index'));

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'fulfillment_type' => ProductFulfillmentType::PreOrder->value,
        'pre_order_estimate_days' => 10,
        'pre_order_deadline' => "{$deadline} 00:00:00",
        'pre_order_min_quantity' => 8,
        'pre_order_note' => 'Produksi setelah kuota minimum tercapai.',
        'stock' => 5,
    ]);
});

test('seller product update deletes the old image when replacing it', function () {
    Storage::fake('public');

    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'image' => 'products/old-image.jpg',
        ]);
    Storage::disk('public')->put('products/old-image.jpg', 'old image');

    $this->actingAs($seller)
        ->from(route('seller.products.edit', $product))
        ->put(route('seller.products.update', $product), [
            'name' => 'Pulpen Gel Biru',
            'category_id' => $category->id,
            'description' => 'Pulpen gel biru untuk catatan harian siswa.',
            'price' => 6000,
            'image' => UploadedFile::fake()->image('new-image.jpg'),
        ])
        ->assertRedirect(route('seller.products.index'));

    Storage::disk('public')->assertMissing('products/old-image.jpg');

    $product->refresh();

    expect($product->image)->not->toBe('products/old-image.jpg');
    Storage::disk('public')->assertExists($product->image);
});
