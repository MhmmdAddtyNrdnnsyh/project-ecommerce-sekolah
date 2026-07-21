<?php

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductSalesMethod;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use App\Support\OrderItemCancellation;
use Illuminate\Support\Facades\Artisan;

test('buyer can cancel unpaid order and restock direct seller product', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['stock' => 5]);
    $order = Order::factory()->for($buyer)->create([
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
        'expires_at' => now()->addDay(),
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'quantity' => 2,
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    $product->update(['stock' => 3]);

    $this->actingAs($buyer)
        ->post(route('orders.cancel', $order), [
            'cancel_reason' => 'Berubah pikiran',
        ])
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHas('success');

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->fresh()->cancelled_at)->not->toBeNull()
        ->and($product->fresh()->stock)->toBe(5);

    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'status' => OrderItemStatus::Cancelled->value,
        'cancel_reason' => 'Berubah pikiran',
    ]);
});

test('buyer cannot cancel paid order item', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create();
    $order = Order::factory()->for($buyer)->create();
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
    ]);

    $this->actingAs($buyer)
        ->from(route('orders.show', $order))
        ->post(route('orders.cancel', $order))
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasErrors('order');

    expect($order->items()->first()->status)->toBe(OrderItemStatus::Pending);
});

test('seller can cancel unpaid item and restock', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['stock' => 1]);
    $order = Order::factory()->for($buyer)->create();
    $item = OrderItem::factory()->for($order)->for($product)->create([
        'quantity' => 2,
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    $this->actingAs($seller)
        ->post(route('seller.orders.cancel', $item), [
            'cancel_reason' => 'Stok tidak tersedia',
        ])
        ->assertRedirect(route('seller.orders.index'))
        ->assertSessionHas('success');

    expect($item->fresh()->status)->toBe(OrderItemStatus::Cancelled)
        ->and($product->fresh()->stock)->toBe(3)
        ->and($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});

test('cancelling consignment order item restocks sold quantity and records reverse movement', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->for($adminJurusan, 'adminJurusan')->create();
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $upJurusan->id,
    ]);
    $product = Product::factory()
        ->for($seller, 'seller')
        ->approved()
        ->create([
            'sales_method' => ProductSalesMethod::UpJurusan,
            'stock' => 0,
        ]);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
        'requested_quantity' => 10,
        'received_quantity' => 10,
        'sold_quantity' => 3,
        'status' => UpJurusanConsignmentStatus::Received,
        'commission_rate' => 10,
    ]);
    $order = Order::factory()->for($buyer)->create();
    $item = OrderItem::factory()->for($order)->for($product)->create([
        'quantity' => 3,
        'price' => 1000,
        'subtotal' => 3000,
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    $out = UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'product_id' => null,
        'order_id' => $order->id,
        'user_id' => $buyer->id,
        'type' => 'out',
        'quantity' => 3,
        'unit_price' => 1000,
        'gross_amount' => 3000,
        'commission_amount' => 300,
        'seller_amount' => 2700,
    ]);

    $this->actingAs($picket)
        ->post(route('picket.orders.cancel', $item), [
            'cancel_reason' => 'Buyer tidak datang',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($item->fresh()->status)->toBe(OrderItemStatus::Cancelled)
        ->and($consignment->fresh()->sold_quantity)->toBe(0);

    $this->assertDatabaseHas('up_jurusan_stock_movements', [
        'order_id' => $order->id,
        'type' => 'in',
        'quantity' => 3,
        'reverses_movement_id' => $out->id,
    ]);
});

test('admin can force cancel paid sent item', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['stock' => 0]);
    $order = Order::factory()->for($buyer)->create();
    OrderItem::factory()->for($order)->for($product)->create([
        'quantity' => 1,
        'status' => OrderItemStatus::Sent,
        'payment_status' => PaymentStatus::Paid,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.orders.cancel', $order), [
            'cancel_reason' => 'Dispute disetujui',
            'force' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($product->fresh()->stock)->toBe(1);
});

test('expire unpaid orders command cancels expired unpaid items and restocks', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['stock' => 1]);
    $order = Order::factory()->for($buyer)->create([
        'expires_at' => now()->subHour(),
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'quantity' => 2,
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    Artisan::call('orders:expire-unpaid');

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($product->fresh()->stock)->toBe(3)
        ->and(OrderItem::query()->where('order_id', $order->id)->value('cancel_reason'))
        ->toContain('batas waktu pembayaran');

    expect($admin->id)->toBeInt();
});

test('expire unpaid orders command skips paid items', function () {
    User::factory()->create(['role' => UserRole::Admin]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create();
    $order = Order::factory()->for($buyer)->create([
        'expires_at' => now()->subHour(),
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
    ]);

    Artisan::call('orders:expire-unpaid');

    expect($order->items()->first()->status)->toBe(OrderItemStatus::Pending)
        ->and($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('pre-order cancel does not change product stock', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->preOrder(5)->create(['stock' => 0]);
    $order = Order::factory()->for($buyer)->create();
    OrderItem::factory()->for($order)->for($product)->create([
        'quantity' => 4,
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
        'is_pre_order' => true,
    ]);

    OrderItemCancellation::cancelOrder($order, $buyer, 'Batal pre-order');

    expect($product->fresh()->stock)->toBe(0)
        ->and($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});
