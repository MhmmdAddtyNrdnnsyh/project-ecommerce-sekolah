<?php

use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanPayout;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use App\Support\MoneyCalculationService;

test('money calculation service is single source for consignment splits', function () {
    expect(MoneyCalculationService::consignmentSaleSplit(3000, 2, 10))->toBe([
        'unit_price' => 3000,
        'gross_amount' => 6000,
        'commission_amount' => 600,
        'seller_amount' => 5400,
    ]);

    expect(MoneyCalculationService::consignmentSaleSplit(5000, 3, 0))->toBe([
        'unit_price' => 5000,
        'gross_amount' => 15000,
        'commission_amount' => 0,
        'seller_amount' => 15000,
    ]);

    expect(MoneyCalculationService::upOwnedProductSaleSplit(50000, 2))->toBe([
        'unit_price' => 50000,
        'gross_amount' => 100000,
        'commission_amount' => 100000,
        'seller_amount' => 0,
    ]);
});

test('admin jurusan must explicitly set commission rate on approve including zero', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->create(['admin_jurusan_id' => $adminJurusan->id]);
    $product = Product::factory()->create(['status' => ProductStatus::Draft]);
    $consignment = UpJurusanConsignment::factory()->create([
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
        'status' => UpJurusanConsignmentStatus::PendingApproval,
        'commission_rate' => null,
    ]);

    $this->actingAs($adminJurusan)
        ->post(route('admin-jurusan.consignments.approve', $consignment))
        ->assertSessionHasErrors('commission_rate');

    $this->actingAs($adminJurusan)
        ->post(route('admin-jurusan.consignments.approve', $consignment), [
            'commission_rate' => 0,
        ])
        ->assertRedirect(route('admin-jurusan.consignments.index'));

    $this->assertDatabaseHas('up_jurusan_consignments', [
        'id' => $consignment->id,
        'status' => UpJurusanConsignmentStatus::Approved->value,
        'commission_rate' => 0,
    ]);
});

test('pos and checkout consignment money use identical formula', function () {
    $splitPos = MoneyCalculationService::consignmentSaleSplit(7000, 1, 20);
    $splitCheckout = MoneyCalculationService::consignmentSaleSplit(7000, 1, 20);

    expect($splitPos)->toBe($splitCheckout)
        ->and($splitPos['commission_amount'])->toBe(1400)
        ->and($splitPos['seller_amount'])->toBe(5600);
});

test('payout unpaid balance reads stored seller_amount without recomputing rate', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $upJurusan = UpJurusan::factory()->create(['admin_jurusan_id' => $adminJurusan->id]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['price' => 10000]);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
        'status' => UpJurusanConsignmentStatus::Received,
        'commission_rate' => 10,
        'received_quantity' => 5,
        'sold_quantity' => 2,
    ]);

    $money = MoneyCalculationService::consignmentSaleSplit(10000, 2, 10);

    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $adminJurusan->id,
        'type' => 'out',
        'source' => 'pos_sale',
        'quantity' => 2,
        ...$money,
    ]);

    expect(MoneyCalculationService::sellerEarningsFromOutMovements($consignment->id))->toBe(18000)
        ->and(MoneyCalculationService::unpaidSellerAmount($consignment->id))->toBe(18000);

    UpJurusanPayout::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'seller_id' => $seller->id,
        'user_id' => $adminJurusan->id,
        'amount' => 5000,
        'note' => 'Partial',
    ]);

    expect(MoneyCalculationService::unpaidSellerAmount($consignment->id))->toBe(13000);

    $this->actingAs($adminJurusan)
        ->post(route('admin-jurusan.consignments.payout', $consignment), [
            'amount' => 13000,
            'note' => 'Pelunasan',
        ])
        ->assertRedirect(route('admin-jurusan.consignments.show', $consignment));

    expect(MoneyCalculationService::unpaidSellerAmount($consignment->id))->toBe(0);
});

test('reverse movement split preserves full amounts and scales partial', function () {
    $movement = new UpJurusanStockMovement([
        'unit_price' => 3000,
        'quantity' => 3,
        'gross_amount' => 9000,
        'commission_amount' => 900,
        'seller_amount' => 8100,
    ]);

    expect(MoneyCalculationService::reverseMovementSplit($movement, 3))->toBe([
        'unit_price' => 3000,
        'gross_amount' => 9000,
        'commission_amount' => 900,
        'seller_amount' => 8100,
    ]);

    expect(MoneyCalculationService::reverseMovementSplit($movement, 1))->toBe([
        'unit_price' => 3000,
        'gross_amount' => 3000,
        'commission_amount' => 300,
        'seller_amount' => 2700,
    ]);
});
