<?php

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanPayout;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use App\Support\ActorLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('active order item statuses exclude terminal values', function () {
    expect(ActorLifecycle::activeOrderItemStatuses())
        ->not->toContain(OrderItemStatus::Completed->value)
        ->not->toContain(OrderItemStatus::Cancelled->value)
        ->toContain(OrderItemStatus::Pending->value)
        ->toContain(OrderItemStatus::Sent->value);
});

test('userHasActiveOrders is true for pending item', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $order = Order::factory()->for($buyer)->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->for($order)->create(['status' => OrderItemStatus::Pending]);

    expect(ActorLifecycle::userHasActiveOrders($buyer))->toBeTrue();
});

test('userHasActiveOrders is false when all items cancelled', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $order = Order::factory()->for($buyer)->create(['status' => OrderStatus::Cancelled]);
    OrderItem::factory()->for($order)->create(['status' => OrderItemStatus::Cancelled]);

    expect(ActorLifecycle::userHasActiveOrders($buyer))->toBeFalse();
});

test('assertCanPromoteToSeller throws when active orders exist', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $order = Order::factory()->for($buyer)->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->for($order)->create(['status' => OrderItemStatus::Packed]);

    ActorLifecycle::assertCanPromoteToSeller($buyer);
})->throws(ValidationException::class);

test('consignmentUnpaidAmount subtracts payouts', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $admin = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $up = UpJurusan::factory()->for($admin, 'adminJurusan')->create();
    $product = Product::factory()->for($seller, 'seller')->create();
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $up->id,
        'status' => UpJurusanConsignmentStatus::Completed,
    ]);
    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'product_id' => null,
        'order_id' => null,
        'user_id' => $seller->id,
        'type' => 'out',
        'quantity' => 1,
        'unit_price' => 1000,
        'gross_amount' => 1000,
        'commission_amount' => 100,
        'seller_amount' => 900,
    ]);
    UpJurusanPayout::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'seller_id' => $seller->id,
        'user_id' => $admin->id,
        'amount' => 400,
    ]);

    expect(ActorLifecycle::consignmentUnpaidAmount($consignment->id))->toBe(500)
        ->and(ActorLifecycle::userHasUnpaidPayouts($seller))->toBeTrue();
});

test('assertCanDeleteUpJurusan allows idle up', function () {
    $admin = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $up = UpJurusan::factory()->for($admin, 'adminJurusan')->create();

    ActorLifecycle::assertCanDeleteUpJurusan($up);

    expect(true)->toBeTrue();
});

test('assertCanReassignPicket throws when active items exist', function () {
    $admin = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $up = UpJurusan::factory()->for($admin, 'adminJurusan')->create();
    $product = Product::factory()->create([
        'seller_id' => null,
        'up_jurusan_id' => $up->id,
        'status' => ProductStatus::Approved,
    ]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $order = Order::factory()->for($buyer)->create();
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    ActorLifecycle::assertCanReassignPicket($up);
})->throws(ValidationException::class);
