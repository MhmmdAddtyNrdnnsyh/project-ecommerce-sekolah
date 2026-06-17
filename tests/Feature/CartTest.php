<?php

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated user can add approved product to cart', function () {
    $user = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()
        ->approved()
        ->create([
            'name' => 'Pulpen Gel Hitam',
            'slug' => 'pulpen-gel-hitam',
            'stock' => 5,
            'price' => 7000,
        ]);

    $this->actingAs($user);

    $response = $this->post(route('cart.items.store', ['product' => $product->slug]), [
        'quantity' => 2,
    ]);

    $response->assertRedirect(route('cart.index'));

    $this->assertDatabaseHas('cart_items', [
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $cartResponse = $this->get(route('cart.index'));

    $cartResponse
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cart/index')
            ->has('items', 1)
            ->where('items.0.product.name', 'Pulpen Gel Hitam')
            ->where('items.0.quantity', 2)
            ->where('items.0.subtotal', 14000)
            ->where('summary.total_items', 2)
            ->where('summary.total_price', 14000),
        );
});

test('adding the same product increases cart quantity without exceeding stock', function () {
    $user = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()
        ->approved()
        ->create([
            'stock' => 5,
        ]);

    CartItem::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($user);

    $this
        ->post(route('cart.items.store', ['product' => $product->slug]), [
            'quantity' => 3,
        ])
        ->assertRedirect(route('cart.index'));

    $this->assertDatabaseHas('cart_items', [
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $this
        ->post(route('cart.items.store', ['product' => $product->slug]), [
            'quantity' => 1,
        ])
        ->assertSessionHasErrors('quantity');

    $this->assertDatabaseHas('cart_items', [
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);
});

test('authenticated user can update cart item quantity', function () {
    $user = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()
        ->approved()
        ->create([
            'stock' => 8,
        ]);
    $cartItem = CartItem::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($user);

    $response = $this->put(route('cart.items.update', $cartItem), [
        'quantity' => 6,
    ]);

    $response->assertRedirect(route('cart.index'));

    $this->assertDatabaseHas('cart_items', [
        'id' => $cartItem->id,
        'quantity' => 6,
    ]);
});

test('cart item quantity cannot exceed product stock', function () {
    $user = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()
        ->approved()
        ->create([
            'stock' => 3,
        ]);
    $cartItem = CartItem::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($user);

    $this
        ->post(route('cart.items.store', ['product' => $product->slug]), [
            'quantity' => 4,
        ])
        ->assertSessionHasErrors('quantity');

    $this
        ->put(route('cart.items.update', $cartItem), [
            'quantity' => 4,
        ])
        ->assertSessionHasErrors('quantity');

    $this->assertDatabaseHas('cart_items', [
        'id' => $cartItem->id,
        'quantity' => 2,
    ]);
});

test('authenticated user can remove cart item', function () {
    $user = User::factory()->create(['role' => UserRole::Buyer]);
    $cartItem = CartItem::query()->create([
        'user_id' => $user->id,
        'product_id' => Product::factory()->approved()->create()->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user);

    $response = $this->delete(route('cart.items.destroy', $cartItem));

    $response->assertRedirect(route('cart.index'));

    $this->assertDatabaseMissing('cart_items', [
        'id' => $cartItem->id,
    ]);
});

test('guest users are redirected from cart endpoints', function () {
    $product = Product::factory()
        ->approved()
        ->create([
            'stock' => 5,
        ]);

    $this->get(route('cart.index'))->assertRedirect(route('login'));
    $this
        ->post(route('cart.items.store', ['product' => $product->slug]), [
            'quantity' => 1,
        ])
        ->assertRedirect(route('login'));
});

test('non approved products cannot be added to cart', function (ProductStatus $status) {
    $user = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->create([
        'status' => $status,
        'stock' => 5,
    ]);

    $this->actingAs($user);

    $this
        ->post(route('cart.items.store', ['product' => $product->slug]), [
            'quantity' => 1,
        ])
        ->assertNotFound();

    $this->assertDatabaseMissing('cart_items', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
})->with([
    ProductStatus::Draft,
    ProductStatus::Pending,
    ProductStatus::Rejected,
]);
