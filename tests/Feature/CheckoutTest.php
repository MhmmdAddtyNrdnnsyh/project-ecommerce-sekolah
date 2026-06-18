<?php

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;

test('authenticated buyer can checkout and convert cart into an order', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()
        ->approved()
        ->create([
            'name' => 'Pulpen Gel Hitam',
            'slug' => 'pulpen-gel-hitam',
            'price' => 5000,
            'stock' => 10,
        ]);

    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($buyer);

    $response = $this->from(route('cart.index'))->post(route('checkout'));

    $response
        ->assertRedirect(route('cart.index'))
        ->assertSessionHas('success', 'Pesanan berhasil dibuat.');

    $this->assertDatabaseHas('orders', [
        'user_id' => $buyer->id,
        'status' => OrderStatus::Pending->value,
        'total_price' => 10000,
    ]);

    $order = $buyer->orders()->first();

    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => 'Pulpen Gel Hitam',
        'price' => 5000,
        'quantity' => 2,
        'subtotal' => 10000,
    ]);

    expect($order->items)->toHaveCount(1);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock' => 8,
    ]);

    $this->assertDatabaseMissing('cart_items', [
        'user_id' => $buyer->id,
    ]);
});

test('checkout rejects an empty cart and creates no order', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);

    $this->actingAs($buyer);

    $response = $this->from(route('cart.index'))->post(route('checkout'));

    $response->assertRedirect(route('cart.index'));
    $response->assertSessionHasErrors('cart');

    $this->assertDatabaseMissing('orders', [
        'user_id' => $buyer->id,
    ]);
});

test('checkout rejects quantity exceeding stock and rolls back', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()
        ->approved()
        ->create([
            'price' => 7000,
            'stock' => 3,
        ]);

    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $this->actingAs($buyer);

    $response = $this->from(route('cart.index'))->post(route('checkout'));

    $response->assertRedirect(route('cart.index'));
    $response->assertSessionHasErrors('cart');

    $this->assertDatabaseMissing('orders', [
        'user_id' => $buyer->id,
    ]);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock' => 3,
    ]);

    $this->assertDatabaseHas('cart_items', [
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);
});

test('checkout rejects non approved products and rolls back', function (ProductStatus $status) {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->create([
        'status' => $status,
        'stock' => 5,
    ]);

    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->actingAs($buyer);

    $response = $this->from(route('cart.index'))->post(route('checkout'));

    $response->assertRedirect(route('cart.index'));
    $response->assertSessionHasErrors('cart');

    $this->assertDatabaseMissing('orders', [
        'user_id' => $buyer->id,
    ]);
})->with([
    ProductStatus::Draft,
    ProductStatus::Pending,
    ProductStatus::Rejected,
]);

test('guest is redirected from the checkout endpoint', function () {
    $this->from(route('cart.index'))->post(route('checkout'))->assertRedirect(route('login'));
});
