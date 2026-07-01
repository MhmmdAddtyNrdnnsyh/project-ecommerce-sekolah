<?php

use App\Enums\ProductFulfillmentType;
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

test('seller can create a pre-order product without ready stock', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $deadline = now()->addDays(7)->toDateString();

    $this->actingAs($seller)
        ->from(route('seller.products.create'))
        ->post(route('seller.products.store'), [
            'name' => 'Kaos Kelas PO',
            'category_id' => $category->id,
            'description' => 'Kaos kelas dibuat setelah pesanan terkumpul.',
            'price' => 50000,
            'fulfillment_type' => ProductFulfillmentType::PreOrder->value,
            'pre_order_estimate_days' => 14,
            'pre_order_deadline' => $deadline,
            'pre_order_min_quantity' => 12,
            'pre_order_note' => 'Produksi dimulai setiap Jumat.',
        ])
        ->assertRedirect(route('seller.products.index'));

    $this->assertDatabaseHas('products', [
        'seller_id' => $seller->id,
        'name' => 'Kaos Kelas PO',
        'stock' => 0,
        'fulfillment_type' => ProductFulfillmentType::PreOrder->value,
        'pre_order_estimate_days' => 14,
        'pre_order_deadline' => "{$deadline} 00:00:00",
        'pre_order_min_quantity' => 12,
        'pre_order_note' => 'Produksi dimulai setiap Jumat.',
        'status' => ProductStatus::Pending->value,
    ]);
});

test('seller can create an up jurusan pre-order product without requested quantity', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $upJurusan = UpJurusan::factory()->create();

    $this->actingAs($seller)
        ->from(route('seller.products.create'))
        ->post(route('seller.products.store'), [
            'name' => 'Risol PO UP',
            'category_id' => $category->id,
            'description' => 'Risol diproduksi setelah pesanan terkumpul.',
            'price' => 3000,
            'sales_method' => 'up_jurusan',
            'fulfillment_type' => ProductFulfillmentType::PreOrder->value,
            'pre_order_estimate_days' => 3,
            'up_jurusan_id' => $upJurusan->id,
        ])
        ->assertRedirect(route('seller.products.index'));

    $this->assertDatabaseHas('products', [
        'seller_id' => $seller->id,
        'category_id' => $category->id,
        'name' => 'Risol PO UP',
        'stock' => 0,
        'sales_method' => 'up_jurusan',
        'fulfillment_type' => ProductFulfillmentType::PreOrder->value,
        'pre_order_estimate_days' => 3,
        'status' => ProductStatus::Pending->value,
    ]);
    $this->assertDatabaseMissing('up_jurusan_consignments', [
        'seller_id' => $seller->id,
        'up_jurusan_id' => $upJurusan->id,
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

test('consigned product ignores seller draft status and creates an up jurusan request', function () {
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
        'status' => ProductStatus::Pending->value,
    ]);
    $this->assertDatabaseHas('up_jurusan_consignments', [
        'seller_id' => $seller->id,
        'up_jurusan_id' => $upJurusan->id,
        'requested_quantity' => 20,
        'status' => UpJurusanConsignmentStatus::PendingApproval->value,
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
