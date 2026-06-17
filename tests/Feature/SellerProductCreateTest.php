<?php

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('seller can visit the create product page', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    Category::factory()->create(['name' => 'Alat Tulis', 'slug' => 'alat-tulis']);

    $this->actingAs($seller);

    $response = $this->get(route('seller.products.create'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('seller/products/create')
            ->has('categories', 1)
            ->where('categories.0.name', 'Alat Tulis'),
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
