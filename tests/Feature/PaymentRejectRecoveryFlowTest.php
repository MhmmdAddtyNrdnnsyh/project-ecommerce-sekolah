<?php

use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductSalesMethod;
use App\Enums\StockMovementSource;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanStockMovement;
use App\Models\User;

test('seller payment reject recovers stock for self managed item', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['stock' => 5]);
    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    $product->update(['stock' => 3]);

    $this->actingAs($seller)
        ->from(route('seller.orders.show', $orderItem))
        ->post(route('seller.orders.payment.reject', $orderItem), [
            'payment_rejection_reason' => 'Tunai tidak diterima.',
        ])
        ->assertRedirect(route('seller.orders.show', $orderItem));

    $orderItem->refresh();
    $order->refresh();
    $product->refresh();

    expect($orderItem->payment_status)->toBe(PaymentStatus::Rejected)
        ->and($orderItem->payment_rejection_reason)->toBe('Tunai tidak diterima.')
        ->and($orderItem->status)->toBe(OrderItemStatus::Cancelled)
        ->and($product->stock)->toBe(5)
        ->and($order->payment_status)->toBe(PaymentStatus::Rejected);
});

test('seller cannot double reject or reject paid payment', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id]);
    $paid = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'payment_status' => PaymentStatus::Paid,
        'status' => OrderItemStatus::Pending,
    ]);

    $this->actingAs($seller)
        ->from(route('seller.orders.show', $paid))
        ->post(route('seller.orders.payment.reject', $paid), [
            'payment_rejection_reason' => 'nope',
        ])
        ->assertRedirect(route('seller.orders.show', $paid))
        ->assertSessionHasErrors('payment');

    $rejected = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'payment_status' => PaymentStatus::Rejected,
        'status' => OrderItemStatus::Cancelled,
    ]);

    $this->actingAs($seller)
        ->from(route('seller.orders.show', $rejected))
        ->post(route('seller.orders.payment.reject', $rejected), [
            'payment_rejection_reason' => 'again',
        ])
        ->assertRedirect(route('seller.orders.show', $rejected))
        ->assertSessionHasErrors('payment');
});

test('picket payment reject recovers consignment stock via reverse movement', function () {
    $upJurusan = UpJurusan::factory()->create();
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $upJurusan->id,
    ]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->for($seller, 'seller')->create([
        'sales_method' => ProductSalesMethod::UpJurusan,
        'stock' => 0,
    ]);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
        'status' => UpJurusanConsignmentStatus::Received,
        'requested_quantity' => 10,
        'received_quantity' => 10,
        'sold_quantity' => 2,
        'commission_rate' => 10,
    ]);

    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
        'price' => 1000,
        'subtotal' => 2000,
    ]);

    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'product_id' => null,
        'order_id' => $order->id,
        'user_id' => $picket->id,
        'type' => 'out',
        'source' => StockMovementSource::OnlineOrder,
        'quantity' => 2,
        'unit_price' => 1000,
        'gross_amount' => 2000,
        'commission_amount' => 200,
        'seller_amount' => 1800,
        'note' => 'Checkout online',
    ]);

    $this->actingAs($picket)
        ->from(route('picket.orders'))
        ->post(route('picket.orders.payment.reject', $orderItem), [
            'payment_rejection_reason' => 'Pembeli tidak bayar.',
        ])
        ->assertRedirect(route('picket.orders'));

    $orderItem->refresh();
    $consignment->refresh();

    expect($orderItem->payment_status)->toBe(PaymentStatus::Rejected)
        ->and($orderItem->status)->toBe(OrderItemStatus::Cancelled)
        ->and($consignment->sold_quantity)->toBe(0)
        ->and(
            UpJurusanStockMovement::query()
                ->where('order_id', $order->id)
                ->where('type', 'in')
                ->where('source', StockMovementSource::Reverse)
                ->exists()
        )->toBeTrue();
});
