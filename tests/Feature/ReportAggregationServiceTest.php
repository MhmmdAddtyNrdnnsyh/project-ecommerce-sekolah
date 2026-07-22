<?php

use App\Enums\ProductStatus;
use App\Enums\StockMovementSource;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use App\Support\MoneyCalculationService;
use App\Support\ReportAggregationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

test('pos report aggregates only pos_sale movements for picket', function () {
    $this->travelTo('2026-06-25 15:00:00');

    $up = UpJurusan::factory()->create();
    $picket = User::factory()->create(['role' => UserRole::PicketOfficer, 'up_jurusan_id' => $up->id]);
    $otherPicket = User::factory()->create(['role' => UserRole::PicketOfficer, 'up_jurusan_id' => $up->id]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['price' => 3000]);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $up->id,
        'status' => UpJurusanConsignmentStatus::Received,
        'commission_rate' => 10,
        'received_quantity' => 20,
        'sold_quantity' => 5,
    ]);

    $money = MoneyCalculationService::consignmentSaleSplit(3000, 2, 10);
    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $picket->id,
        'type' => 'out',
        'source' => StockMovementSource::PosSale,
        'quantity' => 2,
        ...$money,
        'created_at' => '2026-06-25 10:00:00',
        'updated_at' => '2026-06-25 10:00:00',
    ]);
    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $otherPicket->id,
        'type' => 'out',
        'source' => StockMovementSource::PosSale,
        'quantity' => 1,
        ...MoneyCalculationService::consignmentSaleSplit(3000, 1, 10),
        'created_at' => '2026-06-25 11:00:00',
        'updated_at' => '2026-06-25 11:00:00',
    ]);

    $summary = ReportAggregationService::picketDailyOpenSummary($up->id, $picket->id, '2026-06-25');

    // POS lines filtered by picket actor; other picket POS excluded from this picket open report items count for POS
    // but online is UP-wide. Here only POS: picket sees own 2 units; other picket's POS is still UP-day sales for submit.
    expect($summary['total_sold'])->toBe(2)
        ->and($summary['total_revenue'])->toBe(6000)
        ->and($summary['items'])->toHaveCount(1)
        ->and($summary['items'][0]['channel'])->toBe(StockMovementSource::PosSale->value);
});

test('online report aggregates online_order movements into daily open report', function () {
    $this->travelTo('2026-06-25 15:00:00');

    $up = UpJurusan::factory()->create();
    $picket = User::factory()->create(['role' => UserRole::PicketOfficer, 'up_jurusan_id' => $up->id]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['price' => 5000]);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $up->id,
        'status' => UpJurusanConsignmentStatus::Received,
        'commission_rate' => 10,
        'received_quantity' => 20,
        'sold_quantity' => 3,
    ]);

    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $seller->id,
        'type' => 'out',
        'source' => StockMovementSource::OnlineOrder,
        'quantity' => 3,
        ...MoneyCalculationService::consignmentSaleSplit(5000, 3, 10),
        'created_at' => '2026-06-25 12:00:00',
        'updated_at' => '2026-06-25 12:00:00',
    ]);

    $summary = ReportAggregationService::picketDailyOpenSummary($up->id, $picket->id, '2026-06-25');

    expect($summary['total_sold'])->toBe(3)
        ->and($summary['total_revenue'])->toBe(15000)
        ->and($summary['items'])->toHaveCount(1)
        ->and($summary['items'][0]['channel'])->toBe(StockMovementSource::OnlineOrder->value);
});

test('mixed pos and online sales both feed daily open report from movements', function () {
    $this->travelTo('2026-06-25 15:00:00');

    $up = UpJurusan::factory()->create();
    $picket = User::factory()->create(['role' => UserRole::PicketOfficer, 'up_jurusan_id' => $up->id]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['price' => 3000]);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $up->id,
        'status' => UpJurusanConsignmentStatus::Received,
        'commission_rate' => 10,
        'received_quantity' => 20,
        'sold_quantity' => 3,
    ]);

    $posMoney = MoneyCalculationService::consignmentSaleSplit(3000, 2, 10);
    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $picket->id,
        'type' => 'out',
        'source' => StockMovementSource::PosSale,
        'quantity' => 2,
        ...$posMoney,
        'created_at' => '2026-06-25 10:00:00',
        'updated_at' => '2026-06-25 10:00:00',
    ]);

    $onlineMoney = MoneyCalculationService::consignmentSaleSplit(3000, 1, 10);
    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'order_id' => null,
        'user_id' => $seller->id,
        'type' => 'out',
        'source' => StockMovementSource::OnlineOrder,
        'quantity' => 1,
        ...$onlineMoney,
        'created_at' => '2026-06-25 11:00:00',
        'updated_at' => '2026-06-25 11:00:00',
    ]);

    $summary = ReportAggregationService::picketDailyOpenSummary($up->id, $picket->id, '2026-06-25');

    expect($summary['total_sold'])->toBe(3)
        ->and($summary['total_revenue'])->toBe(9000)
        ->and($summary['items'])->toHaveCount(2);
});

test('reverse and correction movements are excluded from sales revenue', function () {
    $this->travelTo('2026-06-25 15:00:00');

    $up = UpJurusan::factory()->create();
    $picket = User::factory()->create(['role' => UserRole::PicketOfficer, 'up_jurusan_id' => $up->id]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['price' => 4000]);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $up->id,
        'status' => UpJurusanConsignmentStatus::Received,
        'commission_rate' => 10,
        'received_quantity' => 20,
        'sold_quantity' => 2,
    ]);

    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $picket->id,
        'type' => 'out',
        'source' => StockMovementSource::PosSale,
        'quantity' => 2,
        ...MoneyCalculationService::consignmentSaleSplit(4000, 2, 10),
        'created_at' => '2026-06-25 10:00:00',
        'updated_at' => '2026-06-25 10:00:00',
    ]);
    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $picket->id,
        'type' => 'in',
        'source' => StockMovementSource::Reverse,
        'quantity' => 1,
        'unit_price' => 4000,
        'gross_amount' => 4000,
        'commission_amount' => 400,
        'seller_amount' => 3600,
        'created_at' => '2026-06-25 11:00:00',
        'updated_at' => '2026-06-25 11:00:00',
    ]);
    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $picket->id,
        'type' => 'out',
        'source' => StockMovementSource::Correction,
        'quantity' => 5,
        'unit_price' => 4000,
        'gross_amount' => 20000,
        'commission_amount' => 2000,
        'seller_amount' => 18000,
        'created_at' => '2026-06-25 12:00:00',
        'updated_at' => '2026-06-25 12:00:00',
    ]);

    $summary = ReportAggregationService::picketDailyOpenSummary($up->id, $picket->id, '2026-06-25');
    $upToday = ReportAggregationService::upTodaySales($up->id, '2026-06-25');

    expect($summary['total_sold'])->toBe(2)
        ->and($summary['total_revenue'])->toBe(8000)
        ->and($upToday)->toBe(8000);
});

test('seller offline revenue reads stored seller_amount from pos_sale only', function () {
    $this->travelTo('2026-06-25 15:00:00');

    $up = UpJurusan::factory()->create();
    $picket = User::factory()->create(['role' => UserRole::PicketOfficer, 'up_jurusan_id' => $up->id]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['price' => 10000]);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $up->id,
        'status' => UpJurusanConsignmentStatus::Received,
        'commission_rate' => 20,
        'received_quantity' => 20,
        'sold_quantity' => 3,
    ]);

    // POS: seller_amount 8000 (10000 * 1 * 80%)
    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $picket->id,
        'type' => 'out',
        'source' => StockMovementSource::PosSale,
        'quantity' => 1,
        ...MoneyCalculationService::consignmentSaleSplit(10000, 1, 20),
        'created_at' => '2026-06-25 10:00:00',
        'updated_at' => '2026-06-25 10:00:00',
    ]);
    // Online must NOT double-count into sellerOfflineRevenue (already in OrderItem path)
    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $seller->id,
        'type' => 'out',
        'source' => StockMovementSource::OnlineOrder,
        'quantity' => 1,
        ...MoneyCalculationService::consignmentSaleSplit(10000, 1, 20),
        'created_at' => '2026-06-25 11:00:00',
        'updated_at' => '2026-06-25 11:00:00',
    ]);
    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $picket->id,
        'type' => 'in',
        'source' => StockMovementSource::Reverse,
        'quantity' => 1,
        'unit_price' => 10000,
        'gross_amount' => 10000,
        'commission_amount' => 2000,
        'seller_amount' => 8000,
        'created_at' => '2026-06-25 12:00:00',
        'updated_at' => '2026-06-25 12:00:00',
    ]);

    $from = Carbon::parse('2026-06-25 00:00:00');
    $to = Carbon::parse('2026-06-25 23:59:59');

    expect(ReportAggregationService::sellerOfflineRevenue($seller->id, $from, $to))->toBe(8000)
        ->and(ReportAggregationService::sellerOfflineSaleCount($seller->id, $from, $to))->toBe(1)
        ->and(ReportAggregationService::sellerOfflineRevenueByDate($seller->id, $from, $to)->get('2026-06-25'))->toBe(8000);

    // multi-line same receipt still counts as one transaction when pos_sale_id is shared
    $posSaleId = DB::table('up_jurusan_pos_sales')->insertGetId([
        'up_jurusan_id' => $up->id,
        'user_id' => $picket->id,
        'code' => 'POS-MULTI-LINE',
        'total_quantity' => 2,
        'total_amount' => 20000,
        'created_at' => '2026-06-25 13:00:00',
        'updated_at' => '2026-06-25 13:00:00',
    ]);
    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'up_jurusan_pos_sale_id' => $posSaleId,
        'user_id' => $picket->id,
        'type' => 'out',
        'source' => StockMovementSource::PosSale,
        'quantity' => 1,
        ...MoneyCalculationService::consignmentSaleSplit(10000, 1, 20),
        'created_at' => '2026-06-25 13:00:00',
        'updated_at' => '2026-06-25 13:00:00',
    ]);
    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'up_jurusan_pos_sale_id' => $posSaleId,
        'user_id' => $picket->id,
        'type' => 'out',
        'source' => StockMovementSource::PosSale,
        'quantity' => 1,
        ...MoneyCalculationService::consignmentSaleSplit(10000, 1, 20),
        'created_at' => '2026-06-25 13:01:00',
        'updated_at' => '2026-06-25 13:01:00',
    ]);

    expect(ReportAggregationService::sellerOfflineSaleCount($seller->id, $from, $to))->toBe(2);
});

test('admin daily report summary aggregates submitted snapshots only', function () {
    $reports = collect([
        (object) ['user_id' => 1, 'total_sold' => 2, 'total_revenue' => 6000],
        (object) ['user_id' => 1, 'total_sold' => 1, 'total_revenue' => 3000],
        (object) ['user_id' => 2, 'total_sold' => 4, 'total_revenue' => 12000],
    ]);

    $summary = ReportAggregationService::adminDailyReportsSummary(
        collect($reports)->map(fn ($r) => new class($r)
        {
            public int $user_id;

            public int $total_sold;

            public int $total_revenue;

            public function __construct(object $r)
            {
                $this->user_id = $r->user_id;
                $this->total_sold = $r->total_sold;
                $this->total_revenue = $r->total_revenue;
            }
        })
    );

    expect($summary)->toBe([
        'reports' => 3,
        'pickets' => 2,
        'items_sold' => 7,
        'gross_amount' => 21000,
    ]);
});

test('up summary revenue uses commission for consignment and gross for up-owned', function () {
    $this->travelTo('2026-06-25 15:00:00');

    $up = UpJurusan::factory()->create();
    $picket = User::factory()->create(['role' => UserRole::PicketOfficer, 'up_jurusan_id' => $up->id]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $consignmentProduct = Product::factory()->for($seller, 'seller')->approved()->create(['price' => 10000]);
    $upProduct = Product::factory()->create([
        'seller_id' => null,
        'up_jurusan_id' => $up->id,
        'price' => 7000,
        'status' => ProductStatus::Approved,
    ]);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $consignmentProduct->id,
        'up_jurusan_id' => $up->id,
        'status' => UpJurusanConsignmentStatus::Received,
        'commission_rate' => 10,
        'received_quantity' => 20,
        'sold_quantity' => 1,
    ]);

    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $picket->id,
        'type' => 'out',
        'source' => StockMovementSource::PosSale,
        'quantity' => 1,
        ...MoneyCalculationService::consignmentSaleSplit(10000, 1, 10),
        'created_at' => '2026-06-25 10:00:00',
        'updated_at' => '2026-06-25 10:00:00',
    ]);
    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => null,
        'product_id' => $upProduct->id,
        'user_id' => $picket->id,
        'type' => 'out',
        'source' => StockMovementSource::PosSale,
        'quantity' => 1,
        ...MoneyCalculationService::upOwnedProductSaleSplit(7000, 1),
        'created_at' => '2026-06-25 11:00:00',
        'updated_at' => '2026-06-25 11:00:00',
    ]);

    $from = Carbon::parse('2026-06-25 00:00:00');
    $to = Carbon::parse('2026-06-25 23:59:59');

    // commission 1000 + up-owned gross 7000
    expect(ReportAggregationService::upRevenueTotal($up->id, $from, $to))->toBe(8000)
        ->and(ReportAggregationService::upTodaySales($up->id, '2026-06-25'))->toBe(17000);
});

test('stock movement source enum covers required channels', function () {
    expect(StockMovementSource::values())->toBe([
        'pos_sale',
        'online_order',
        'reverse',
        'correction',
    ])->and(StockMovementSource::salesSources())->toBe([
        'pos_sale',
        'online_order',
    ]);
});
