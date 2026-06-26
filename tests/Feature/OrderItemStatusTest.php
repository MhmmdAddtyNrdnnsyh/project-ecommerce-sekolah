<?php

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

test('order item status exposes values labels and next transitions', function () {
    expect(OrderItemStatus::values())->toBe(['pending', 'packed', 'sent'])
        ->and(OrderItemStatus::Pending->label())->toBe('Menunggu')
        ->and(OrderItemStatus::Packed->label())->toBe('Dikemas')
        ->and(OrderItemStatus::Sent->label())->toBe('Dikirim')
        ->and(OrderItemStatus::Pending->next())->toBe(OrderItemStatus::Packed)
        ->and(OrderItemStatus::Packed->next())->toBe(OrderItemStatus::Sent)
        ->and(OrderItemStatus::Sent->next())->toBeNull();
});

test('order item has default pending status', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->approved()->create();

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    expect($orderItem->status)->toBe(OrderItemStatus::Pending);
    $this->assertDatabaseHas('order_items', [
        'id' => $orderItem->id,
        'status' => OrderItemStatus::Pending->value,
    ]);
});

test('order item status can be cast to enum', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->approved()->create();

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'status' => OrderItemStatus::Packed,
    ]);

    expect($orderItem->status)->toBeInstanceOf(OrderItemStatus::class);
    expect($orderItem->status->value)->toBe('packed');
    expect($orderItem->status->label())->toBe('Dikemas');
});

test('order item belongs to order and product', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()
        ->for($seller, 'seller')
        ->approved()
        ->create(['name' => 'Pulpen', 'price' => 5000]);

    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'total_price' => 10000,
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'price' => $product->price,
        'quantity' => 2,
        'subtotal' => 10000,
    ]);

    expect($orderItem->order->id)->toBe($order->id);
    expect($orderItem->product->id)->toBe($product->id);
});

test('checkout creates order items with pending status', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()
        ->approved()
        ->create([
            'name' => 'Pulpen Gel Hitam',
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

    $response->assertRedirect(route('cart.index'));
    $response->assertSessionHas('success', 'Pesanan berhasil dibuat.');

    $this->assertDatabaseHas('orders', [
        'user_id' => $buyer->id,
        'status' => OrderStatus::Pending->value,
        'total_price' => 10000,
    ]);

    $this->assertDatabaseHas('order_items', [
        'product_id' => $product->id,
        'price' => 5000,
        'quantity' => 2,
        'subtotal' => 10000,
        'status' => OrderItemStatus::Pending->value,
    ]);
});
