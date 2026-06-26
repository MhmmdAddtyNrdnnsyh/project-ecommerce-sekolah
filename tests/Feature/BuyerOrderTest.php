<?php

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('buyer can list their own orders', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $otherBuyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['name' => 'Pulpen Biru']);
    $order = Order::factory()->for($buyer)->create([
        'status' => OrderStatus::Pending,
        'total_price' => 20_000,
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'product_name' => 'Pulpen Biru',
        'quantity' => 2,
        'subtotal' => 20_000,
        'status' => OrderItemStatus::Pending,
    ]);
    Order::factory()->for($otherBuyer)->create(['total_price' => 99_000]);

    $this->actingAs($buyer)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('orders/index')
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $order->id)
            ->where('orders.data.0.items.0.product_name', 'Pulpen Biru')
            ->where('orders.data.0.total_price', 20_000),
        );
});

test('buyer can view their order detail', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['name' => 'Buku Tulis']);
    $order = Order::factory()->for($buyer)->create(['total_price' => 12_000]);
    OrderItem::factory()->for($order)->for($product)->create([
        'product_name' => 'Buku Tulis',
        'price' => 6_000,
        'quantity' => 2,
        'subtotal' => 12_000,
    ]);

    $this->actingAs($buyer)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('orders/show')
            ->where('order.id', $order->id)
            ->where('order.status.code', OrderItemStatus::Pending->value)
            ->where('order.status.label', OrderItemStatus::Pending->label())
            ->where('order.total_price', 12_000)
            ->where('order.created_at', $order->created_at->toIso8601String())
            ->where('order.items.0.product_name', 'Buku Tulis')
            ->where('order.items.0.price', 6_000)
            ->where('order.items.0.quantity', 2)
            ->where('order.items.0.subtotal', 12_000)
            ->where('order.items.0.seller.name', $seller->name),
        );
});

test('buyer order summary status follows seller item status updates', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create();
    $order = Order::factory()->for($buyer)->create([
        'status' => OrderStatus::Pending,
    ]);

    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Packed,
    ]);

    $this->actingAs($buyer)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('orders.data.0.status.code', OrderItemStatus::Packed->value)
            ->where('orders.data.0.status.label', OrderItemStatus::Packed->label()),
        );
});

test('buyer can view an empty order list', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);

    $this->actingAs($buyer)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('orders/index')
            ->has('orders.data', 0)
            ->where('orders.total', 0),
        );
});

test('buyer cannot view another buyers order detail', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $otherBuyer = User::factory()->create(['role' => UserRole::Buyer]);
    $order = Order::factory()->for($otherBuyer)->create();

    $this->actingAs($buyer)
        ->get(route('orders.show', $order))
        ->assertNotFound();
});

test('guest is redirected from buyer orders', function () {
    $order = Order::factory()->create();

    $this->get(route('orders.index'))->assertRedirect(route('login'));
    $this->get(route('orders.show', $order))->assertRedirect(route('login'));
});

test('non buyer users cannot access buyer orders', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $order = Order::factory()->create();

    $this->actingAs($user);

    $this->get(route('orders.index'))->assertForbidden();
    $this->get(route('orders.show', $order))->assertForbidden();
})->with([
    UserRole::Admin,
    UserRole::Seller,
    UserRole::PicketOfficer,
]);
