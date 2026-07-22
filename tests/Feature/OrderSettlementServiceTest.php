<?php

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderSettlementService;

function settlementOrder(array $items): Order
{
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $sellerA = User::factory()->create(['role' => UserRole::Seller]);
    $sellerB = User::factory()->create(['role' => UserRole::Seller]);
    $productA = Product::factory()->for($sellerA, 'seller')->approved()->create();
    $productB = Product::factory()->for($sellerB, 'seller')->approved()->create();
    $order = Order::factory()->for($buyer)->create(['status' => OrderStatus::Open]);

    foreach ($items as $index => $item) {
        OrderItem::factory()->for($order)->for($index === 0 ? $productA : $productB)->create($item);
    }

    return $order->fresh(['items']);
}

test('mixed paid and unpaid items yield partially_paid', function () {
    $order = settlementOrder([
        ['status' => OrderItemStatus::Pending, 'payment_status' => PaymentStatus::Paid],
        ['status' => OrderItemStatus::Pending, 'payment_status' => PaymentStatus::Unpaid],
    ]);

    expect(OrderSettlementService::deriveStatus($order->items))->toBe(OrderStatus::PartiallyPaid);

    OrderSettlementService::sync($order);
    expect($order->fresh()->status)->toBe(OrderStatus::PartiallyPaid);
});

test('all active items paid yield paid', function () {
    $order = settlementOrder([
        ['status' => OrderItemStatus::Packed, 'payment_status' => PaymentStatus::Paid],
        ['status' => OrderItemStatus::Sent, 'payment_status' => PaymentStatus::Paid],
    ]);

    expect(OrderSettlementService::deriveStatus($order->items))->toBe(OrderStatus::Paid);
});

test('mixed completed and pending items yield partially_completed', function () {
    $order = settlementOrder([
        ['status' => OrderItemStatus::Completed, 'payment_status' => PaymentStatus::Paid],
        ['status' => OrderItemStatus::Pending, 'payment_status' => PaymentStatus::Paid],
    ]);

    expect(OrderSettlementService::deriveStatus($order->items))->toBe(OrderStatus::PartiallyCompleted);
});

test('mixed cancelled and completed items yield completed terminal', function () {
    $order = settlementOrder([
        ['status' => OrderItemStatus::Completed, 'payment_status' => PaymentStatus::Paid],
        ['status' => OrderItemStatus::Cancelled, 'payment_status' => PaymentStatus::Unpaid],
    ]);

    expect(OrderSettlementService::deriveStatus($order->items))->toBe(OrderStatus::Completed);
});

test('all completed items yield completed', function () {
    $order = settlementOrder([
        ['status' => OrderItemStatus::Completed, 'payment_status' => PaymentStatus::Paid],
        ['status' => OrderItemStatus::Completed, 'payment_status' => PaymentStatus::Paid],
    ]);

    expect(OrderSettlementService::deriveStatus($order->items))->toBe(OrderStatus::Completed);
});

test('all cancelled items yield cancelled', function () {
    $order = settlementOrder([
        ['status' => OrderItemStatus::Cancelled, 'payment_status' => PaymentStatus::Unpaid],
        ['status' => OrderItemStatus::Cancelled, 'payment_status' => PaymentStatus::Unpaid],
    ]);

    expect(OrderSettlementService::deriveStatus($order->items))->toBe(OrderStatus::Cancelled);
});

test('partial payment prefers partially_paid over open', function () {
    $order = settlementOrder([
        ['status' => OrderItemStatus::InProduction, 'payment_status' => PaymentStatus::Paid],
        ['status' => OrderItemStatus::Pending, 'payment_status' => PaymentStatus::Rejected],
    ]);

    expect(OrderSettlementService::deriveStatus($order->items))->toBe(OrderStatus::PartiallyPaid);
});

test('partial completion takes precedence over paid when some items still open', function () {
    $order = settlementOrder([
        ['status' => OrderItemStatus::Completed, 'payment_status' => PaymentStatus::Paid],
        ['status' => OrderItemStatus::Sent, 'payment_status' => PaymentStatus::Paid],
    ]);

    expect(OrderSettlementService::deriveStatus($order->items))->toBe(OrderStatus::PartiallyCompleted);
});

test('unpaid open items remain open', function () {
    $order = settlementOrder([
        ['status' => OrderItemStatus::Pending, 'payment_status' => PaymentStatus::Unpaid],
        ['status' => OrderItemStatus::Packed, 'payment_status' => PaymentStatus::PendingConfirmation],
    ]);

    expect(OrderSettlementService::deriveStatus($order->items))->toBe(OrderStatus::Open);
});

test('empty items yield open', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $order = Order::factory()->for($buyer)->create(['status' => OrderStatus::Open]);

    expect(OrderSettlementService::deriveStatus([]))->toBe(OrderStatus::Open);
});
