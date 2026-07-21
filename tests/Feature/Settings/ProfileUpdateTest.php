<?php

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/profile')
            ->missing('mustVerifyEmail'),
        );
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('buyer can safely delete account with cart and completed orders', function () {
    $user = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Completed,
    ]);

    CartItem::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Completed,
    ]);

    $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
    $this->assertDatabaseMissing('cart_items', ['user_id' => $user->id]);
    $this->assertDatabaseMissing('orders', ['user_id' => $user->id]);
    $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
});

test('buyer cannot delete account while order is active', function () {
    $user = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Pending,
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Pending,
    ]);

    $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('password');

    expect($user->fresh())->not->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});

test('email verification routes stay removed', function () {
    expect(Route::has('verification.notice'))->toBeFalse();
    expect(Route::has('verification.send'))->toBeFalse();
    expect(Route::has('verification.verify'))->toBeFalse();
});
