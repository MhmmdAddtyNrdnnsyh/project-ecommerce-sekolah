<?php

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can see pending products for moderation', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create(['name' => 'Alat Tulis', 'slug' => 'alat-tulis']);

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'name' => 'Produk Pending',
            'slug' => 'produk-pending',
            'status' => ProductStatus::Pending,
        ]);

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->approved()
        ->create([
            'name' => 'Produk Approved',
            'slug' => 'produk-approved',
        ]);

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'name' => 'Produk Rejected',
            'slug' => 'produk-rejected',
            'status' => ProductStatus::Rejected,
        ]);

    $this->actingAs($admin);

    $response = $this->get(route('admin.products.moderation.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/products/moderation')
            ->has('products', 1)
            ->where('products.0.name', 'Produk Pending')
            ->where('products.0.category.name', 'Alat Tulis')
            ->where('products.0.seller.name', $seller->name),
        );
});

test('admin can approve a pending product and buyer catalog shows it', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'name' => 'Pulpen Gel Hitam',
            'slug' => 'pulpen-gel-hitam',
            'status' => ProductStatus::Pending,
            'stock' => 10,
            'rejection_reason' => 'Butuh foto lebih jelas',
        ]);

    Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'name' => 'Produk Masih Pending',
            'slug' => 'produk-masih-pending',
            'status' => ProductStatus::Pending,
        ]);

    $this->actingAs($admin);

    $response = $this
        ->from(route('admin.products.moderation.index'))
        ->post(route('admin.products.moderation.approve', $product));

    $response->assertRedirect(route('admin.products.moderation.index'));

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'status' => ProductStatus::Approved->value,
        'rejection_reason' => null,
    ]);

    $this->actingAs($buyer);

    $catalogResponse = $this->get(route('catalog.index'));

    $catalogResponse
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/index')
            ->has('products', 1)
            ->where('products.0.name', 'Pulpen Gel Hitam')
            ->where('products.0.slug', 'pulpen-gel-hitam'),
        );
});

test('admin can reject a pending product with an optional reason', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()
        ->for($seller, 'seller')
        ->create([
            'status' => ProductStatus::Pending,
        ]);
    $productWithoutReason = Product::factory()
        ->for($seller, 'seller')
        ->create([
            'status' => ProductStatus::Pending,
        ]);

    $this->actingAs($admin);

    $response = $this
        ->from(route('admin.products.moderation.index'))
        ->post(route('admin.products.moderation.reject', $product), [
            'reason' => 'Foto produk belum jelas.',
        ]);

    $response->assertRedirect(route('admin.products.moderation.index'));

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'status' => ProductStatus::Rejected->value,
        'rejection_reason' => 'Foto produk belum jelas.',
    ]);

    $this
        ->from(route('admin.products.moderation.index'))
        ->post(route('admin.products.moderation.reject', $productWithoutReason))
        ->assertRedirect(route('admin.products.moderation.index'));

    $this->assertDatabaseHas('products', [
        'id' => $productWithoutReason->id,
        'status' => ProductStatus::Rejected->value,
        'rejection_reason' => null,
    ]);
});

test('non admin users cannot access product moderation endpoints', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $product = Product::factory()->create([
        'status' => ProductStatus::Pending,
    ]);

    $this->actingAs($user);

    $this->get(route('admin.products.moderation.index'))->assertForbidden();
    $this->post(route('admin.products.moderation.approve', $product))->assertForbidden();
    $this->post(route('admin.products.moderation.reject', $product))->assertForbidden();
})->with([
    UserRole::Buyer,
    UserRole::Seller,
    UserRole::PicketOfficer,
]);
