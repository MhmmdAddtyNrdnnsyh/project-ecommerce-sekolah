<?php

use App\Enums\UserRole;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

test('guest can only access public catalog pages from client routes', function () {
    $product = Product::factory()->approved()->create();

    $this->get(route('catalog.index'))->assertOk();
    $this->get(route('catalog.show', $product))->assertOk();

    $this->get(route('cart.index'))->assertRedirect(route('login'));
    $this->post(route('checkout'))->assertRedirect(route('login'));
    $this->get(route('orders.index'))->assertRedirect(route('login'));
    $this->get(route('orders.show', Order::factory()->create()))->assertRedirect(route('login'));
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
});

test('buyer can access buyer transactional routes and profile', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create(['stock' => 5]);
    $cartItem = CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);
    $order = Order::factory()->for($buyer)->create();

    $this->actingAs($buyer);

    $this->get(route('cart.index'))->assertOk();
    $this->post(route('cart.items.store', $product), ['quantity' => 1])->assertRedirect(route('cart.index'));
    $this->put(route('cart.items.update', $cartItem), ['quantity' => 2])->assertRedirect(route('cart.index'));
    $this->delete(route('cart.items.destroy', $cartItem))->assertRedirect(route('cart.index'));
    $this->get(route('orders.index'))->assertOk();
    $this->get(route('orders.show', $order))->assertOk();
    $this->get(route('profile.edit'))->assertOk();
});

test('seller and admin cannot access buyer transactional routes', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $product = Product::factory()->approved()->create(['stock' => 5]);
    $cartItem = CartItem::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);
    $order = Order::factory()->create();

    $this->actingAs($user);

    $this->get(route('cart.index'))->assertForbidden();
    $this->post(route('cart.items.store', $product), ['quantity' => 1])->assertForbidden();
    $this->put(route('cart.items.update', $cartItem), ['quantity' => 2])->assertForbidden();
    $this->delete(route('cart.items.destroy', $cartItem))->assertForbidden();
    $this->post(route('checkout'))->assertForbidden();
    $this->get(route('orders.index'))->assertForbidden();
    $this->get(route('orders.show', $order))->assertForbidden();
})->with([
    UserRole::Seller,
    UserRole::Admin,
]);
