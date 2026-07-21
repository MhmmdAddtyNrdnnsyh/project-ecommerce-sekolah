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
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('filter values include required admin buckets', function () {
    expect(OrderLivenessService::filterValues())
        ->toBe(['active', 'expired', 'stuck', 'requires_action']);
});

test('stuck reasons empty for healthy order', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $order = Order::factory()->for($buyer)->create([
        'status' => OrderStatus::Pending,
        'expires_at' => now()->addDay(),
        'payment_status' => PaymentStatus::Paid,
    ]);
    OrderItem::factory()->for($order)->for(Product::factory()->approved())->create([
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
        'status_changed_at' => now(),
        'payment_confirmed_at' => now(),
    ]);

    expect(OrderLivenessService::stuckReasonsFor($order->fresh(['items'])))->toBe([]);
});

test('status change updates status_changed_at', function () {
    $item = OrderItem::factory()->create([
        'status' => OrderItemStatus::Pending,
        'status_changed_at' => now()->subDay(),
    ]);

    $previous = $item->status_changed_at->copy();
    $this->travel(2)->hours();
    $item->update(['status' => OrderItemStatus::Packed]);

    expect($item->fresh()->status_changed_at->greaterThan($previous))->toBeTrue();
});
