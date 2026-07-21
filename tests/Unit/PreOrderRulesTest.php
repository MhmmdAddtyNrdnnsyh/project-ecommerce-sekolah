<?php

use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\OrderItemFulfillment;
use App\Support\PreOrderRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('deadline passed only after pre order deadline day', function () {
    $product = Product::factory()->approved()->preOrder()->create([
        'pre_order_deadline' => now()->toDateString(),
        'pre_order_min_quantity' => null,
    ]);

    expect(PreOrderRules::isDeadlinePassed($product))->toBeFalse();

    $product->update(['pre_order_deadline' => now()->subDay()->toDateString()]);

    expect(PreOrderRules::isDeadlinePassed($product->fresh()))->toBeTrue();
});

test('minimum quantity ignored when null', function () {
    $product = Product::factory()->approved()->preOrder()->create([
        'pre_order_min_quantity' => null,
        'pre_order_deadline' => now()->addDay()->toDateString(),
    ]);

    expect(PreOrderRules::isBelowMinimumQuantity($product, 1))->toBeFalse()
        ->and(PreOrderRules::isValid($product, 1))->toBeTrue();
});

test('ready stock products skip pre order rules', function () {
    $product = Product::factory()->approved()->create([
        'pre_order_deadline' => now()->subYear()->toDateString(),
        'pre_order_min_quantity' => 100,
    ]);

    expect(PreOrderRules::isValid($product, 1))->toBeTrue()
        ->and(PreOrderRules::invalidReasons($product, 1))->toBe([]);
});

test('shared fulfillment helper never advances pre order to packed', function () {
    $item = new OrderItem([
        'is_pre_order' => true,
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
    ]);

    expect(OrderItemFulfillment::expectedNext($item))->toBe(OrderItemStatus::InProduction)
        ->and(OrderItemFulfillment::allowedFulfillmentStatusValues(true))
        ->not->toContain(OrderItemStatus::Packed->value);
});
