<?php

use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\UpJurusan;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('seller can visit the create product page', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    Category::factory()->create(['name' => 'Alat Tulis', 'slug' => 'alat-tulis']);
    UpJurusan::factory()->create(['name' => 'UP RPL']);

    $this->actingAs($seller);

    $response = $this->get(route('seller.products.create'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('seller/products/create')
            ->has('categories', 1)
            ->where('categories.0.name', 'Alat Tulis')
            ->has('upJurusans', 1)
            ->where('upJurusans.0.name', 'UP RPL'),
        );
});

test('seller can create a product with pending status', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();

    $this->actingAs($seller);

    $response = $this
        ->from(route('seller.products.create'))
        ->post(route('seller.products.store'), [
            'name' => 'Pulpen Gel Hitam',
            'category_id' => $category->id,
            'description' => 'Pulpen gel hitam untuk catatan harian siswa.',
            'price' => 5000,
            'stock' => 12,
        ]);

    $response->assertRedirect(route('seller.products.index'));

    $this->assertDatabaseHas('products', [
        'seller_id' => $seller->id,
        'category_id' => $category->id,
        'name' => 'Pulpen Gel Hitam',
        'slug' => 'pulpen-gel-hitam',
        'description' => 'Pulpen gel hitam untuk catatan harian siswa.',
        'price' => 5000,
        'stock' => 12,
        'status' => ProductStatus::Pending->value,
        'image' => null,
    ]);
});

test('seller can save a product as draft', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();

    $this->actingAs($seller)
        ->from(route('seller.products.create'))
        ->post(route('seller.products.store'), [
            'name' => 'Produk Belum Siap',
            'category_id' => $category->id,
            'description' => 'Produk ini masih disiapkan seller.',
            'price' => 5000,
            'stock' => 12,
            'status' => ProductStatus::Draft->value,
        ])
        ->assertRedirect(route('seller.products.index'));

    $this->assertDatabaseHas('products', [
        'seller_id' => $seller->id,
        'name' => 'Produk Belum Siap',
        'status' => ProductStatus::Draft->value,
    ]);
});

test('seller can create a consigned product from product form', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $upJurusan = UpJurusan::factory()->create();

    $this->actingAs($seller)
        ->from(route('seller.products.create'))
        ->post(route('seller.products.store'), [
            'name' => 'Risol Mayo',
            'category_id' => $category->id,
            'description' => 'Risol mayo titipan untuk kantin jurusan.',
            'price' => 3000,
            'sales_method' => 'up_jurusan',
            'up_jurusan_id' => $upJurusan->id,
            'requested_quantity' => 20,
        ])
        ->assertRedirect(route('seller.products.index'));

    $this->assertDatabaseHas('products', [
        'seller_id' => $seller->id,
        'category_id' => $category->id,
        'name' => 'Risol Mayo',
        'stock' => 0,
        'sales_method' => 'up_jurusan',
        'status' => ProductStatus::Pending->value,
    ]);

    $this->assertDatabaseHas('up_jurusan_consignments', [
        'seller_id' => $seller->id,
        'up_jurusan_id' => $upJurusan->id,
        'requested_quantity' => 20,
        'status' => UpJurusanConsignmentStatus::PendingApproval->value,
    ]);
});

test('draft consigned product does not create an up jurusan request yet', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $upJurusan = UpJurusan::factory()->create();

    $this->actingAs($seller)
        ->from(route('seller.products.create'))
        ->post(route('seller.products.store'), [
            'name' => 'Risol Draft',
            'category_id' => $category->id,
            'description' => 'Risol draft sebelum dititipkan.',
            'price' => 3000,
            'sales_method' => 'up_jurusan',
            'up_jurusan_id' => $upJurusan->id,
            'requested_quantity' => 20,
            'status' => ProductStatus::Draft->value,
        ])
        ->assertRedirect(route('seller.products.index'));

    $this->assertDatabaseHas('products', [
        'seller_id' => $seller->id,
        'name' => 'Risol Draft',
        'sales_method' => 'up_jurusan',
        'status' => ProductStatus::Draft->value,
    ]);
    $this->assertDatabaseMissing('up_jurusan_consignments', [
        'seller_id' => $seller->id,
        'up_jurusan_id' => $upJurusan->id,
        'requested_quantity' => 20,
    ]);
});

test('product create validation is explicit', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);

    $this->actingAs($seller);

    $response = $this
        ->from(route('seller.products.create'))
        ->post(route('seller.products.store'), [
            'name' => 'AB',
            'category_id' => 999,
            'description' => 'Pendek',
            'price' => 0,
            'stock' => -1,
        ]);

    $response
        ->assertRedirect(route('seller.products.create'))
        ->assertSessionHasErrors([
            'name',
            'category_id',
            'description',
            'price',
            'stock',
        ]);
});

test('buyer and admin cannot use seller product create endpoints', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user);

    $this->get(route('seller.products.create'))->assertForbidden();
    $this->post(route('seller.products.store'))->assertForbidden();
})->with([
    UserRole::Buyer,
    UserRole::Admin,
]);
