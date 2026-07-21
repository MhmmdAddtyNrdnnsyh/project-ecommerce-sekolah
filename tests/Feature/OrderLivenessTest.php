<?php

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderLivenessService;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;

test('unpaid expired orders are detected', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create();
    $order = Order::factory()->for($buyer)->create([
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
        'expires_at' => now()->subHour(),
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    expect(OrderLivenessService::unpaidExpiredQuery()->whereKey($order->id)->exists())->toBeTrue()
        ->and(OrderLivenessService::livenessLabel($order))->toBe('expired');
});

test('paid packed item idle beyond sla is stuck', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create();
    $order = Order::factory()->for($buyer)->create([
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Packed,
        'payment_status' => PaymentStatus::Paid,
        'payment_confirmed_at' => now()->subHours(OrderLivenessService::FULFILLMENT_IDLE_HOURS + 2),
        'status_changed_at' => now()->subHours(OrderLivenessService::FULFILLMENT_IDLE_HOURS + 2),
    ]);

    expect(OrderLivenessService::stuckFulfillmentQuery()->whereKey($order->id)->exists())->toBeTrue()
        ->and(OrderLivenessService::livenessLabel($order->fresh(['items'])))->toBe('stuck');
});

test('sent item idle beyond sla is stuck', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create();
    $order = Order::factory()->for($buyer)->create([
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Sent,
        'payment_status' => PaymentStatus::Paid,
        'status_changed_at' => now()->subHours(OrderLivenessService::SENT_IDLE_HOURS + 1),
    ]);

    expect(OrderLivenessService::stuckSentQuery()->whereKey($order->id)->exists())->toBeTrue()
        ->and(OrderLivenessService::livenessLabel($order->fresh(['items'])))->toBe('stuck');
});

test('fresh active paid order is not stuck', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create();
    $order = Order::factory()->for($buyer)->create([
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
        'expires_at' => now()->addDay(),
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Packed,
        'payment_status' => PaymentStatus::Paid,
        'payment_confirmed_at' => now()->subHour(),
        'status_changed_at' => now()->subHour(),
    ]);

    expect(OrderLivenessService::stuckQuery()->whereKey($order->id)->exists())->toBeFalse()
        ->and(OrderLivenessService::unpaidExpiredQuery()->whereKey($order->id)->exists())->toBeFalse()
        ->and(OrderLivenessService::livenessLabel($order->fresh(['items'])))->toBe('active');
});

test('detect stuck command marks stuck reasons', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create();
    $order = Order::factory()->for($buyer)->create([
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
        'payment_confirmed_at' => now()->subHours(OrderLivenessService::FULFILLMENT_IDLE_HOURS + 5),
        'status_changed_at' => now()->subHours(OrderLivenessService::FULFILLMENT_IDLE_HOURS + 5),
    ]);

    Artisan::call('orders:detect-stuck');

    $order->refresh();
    expect($order->stuck_detected_at)->not->toBeNull()
        ->and($order->stuck_reasons)->toContain('fulfillment_idle');
});

test('admin can force cancel stuck order', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['stock' => 0]);
    $order = Order::factory()->for($buyer)->create([
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'quantity' => 2,
        'status' => OrderItemStatus::Packed,
        'payment_status' => PaymentStatus::Paid,
        'status_changed_at' => now()->subDays(5),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.orders.cancel', $order), [
            'cancel_reason' => 'Force cancel stuck',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($product->fresh()->stock)->toBe(2);
});

test('admin can force complete sent paid order', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create();
    $order = Order::factory()->for($buyer)->create([
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Sent,
        'payment_status' => PaymentStatus::Paid,
        'status_changed_at' => now()->subDays(5),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.orders.force-complete', $order))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($order->fresh()->status)->toBe(OrderStatus::Completed)
        ->and($order->items()->first()->status)->toBe(OrderItemStatus::Completed);
});

test('admin can mark order for manual review', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $order = Order::factory()->for($buyer)->create();

    $this->actingAs($admin)
        ->post(route('admin.orders.mark-review', $order), [
            'reason' => 'Buyer komplain',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $order->refresh();
    expect($order->requires_manual_review)->toBeTrue()
        ->and($order->requires_manual_review_reason)->toBe('Buyer komplain')
        ->and(OrderLivenessService::livenessLabel($order))->toBe('requires_action');
});

test('admin orders index filters by liveness', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create();

    $expired = Order::factory()->for($buyer)->create([
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
        'expires_at' => now()->subHour(),
    ]);
    OrderItem::factory()->for($expired)->for($product)->create([
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    $active = Order::factory()->for($buyer)->create([
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
        'expires_at' => now()->addDay(),
    ]);
    OrderItem::factory()->for($active)->for($product)->create([
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
        'status_changed_at' => now(),
        'payment_confirmed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['liveness' => 'expired']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/index')
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $expired->id)
            ->where('filters.liveness', 'expired'),
        );

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['liveness' => 'active']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $active->id),
        );
});

test('expire unpaid still works via liveness service', function () {
    User::factory()->create(['role' => UserRole::Admin]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['stock' => 1]);
    $order = Order::factory()->for($buyer)->create([
        'expires_at' => now()->subHour(),
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'quantity' => 1,
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    Artisan::call('orders:expire-unpaid');

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($product->fresh()->stock)->toBe(2);
});
