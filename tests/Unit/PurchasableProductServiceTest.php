<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Support\PurchasableProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('approved ready stock product is valid', function () {
    $product = Product::factory()->approved()->create(['stock' => 5]);

    expect(PurchasableProductService::isValid($product, 2))->toBeTrue()
        ->and(PurchasableProductService::invalidReasonCodes($product, 2))->toBe([]);
});

test('rejected product is invalid', function () {
    $product = Product::factory()->create([
        'status' => ProductStatus::Rejected,
        'stock' => 5,
    ]);

    expect(PurchasableProductService::invalidReasonCodes($product, 1))
        ->toContain(PurchasableProductService::REASON_PRODUCT_REJECTED)
        ->and(PurchasableProductService::isValid($product, 1))->toBeFalse();
});

test('deleted product is invalid', function () {
    expect(PurchasableProductService::invalidReasonCodes(null, 1))
        ->toBe([PurchasableProductService::REASON_PRODUCT_DELETED]);
});

test('out of stock ready product is invalid', function () {
    $product = Product::factory()->approved()->create(['stock' => 1]);

    expect(PurchasableProductService::invalidReasonCodes($product, 2))
        ->toContain(PurchasableProductService::REASON_OUT_OF_STOCK);
});

test('preorder deadline and min quantity invalidate', function () {
    $product = Product::factory()->approved()->preOrder()->create([
        'pre_order_deadline' => now()->subDay()->toDateString(),
        'pre_order_min_quantity' => 5,
        'stock' => 0,
    ]);

    $codes = PurchasableProductService::invalidReasonCodes($product, 1);

    expect($codes)
        ->toContain(PurchasableProductService::REASON_PREORDER_DEADLINE_PASSED)
        ->toContain(PurchasableProductService::REASON_PREORDER_MIN_QUANTITY);
});

test('ownership invalid when no seller and no up jurusan', function () {
    $product = Product::factory()->approved()->create([
        'seller_id' => null,
        'up_jurusan_id' => null,
        'stock' => 5,
    ]);

    expect(PurchasableProductService::invalidReasonCodes($product, 1))
        ->toContain(PurchasableProductService::REASON_OWNERSHIP_INVALID);
});

test('assert purchasable throws for invalid product', function () {
    $product = Product::factory()->create(['status' => ProductStatus::Draft, 'stock' => 5]);

    expect(fn () => PurchasableProductService::assertPurchasable($product, 1))
        ->toThrow(ValidationException::class);
});
