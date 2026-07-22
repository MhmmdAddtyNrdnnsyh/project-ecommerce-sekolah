<?php

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\DomainEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\User;
use App\Support\ConsignmentTransitionService;
use App\Support\DomainEventService;
use App\Support\OrderItemCancellation;
use App\Support\OrderSettlementService;
use App\Support\PaymentTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('domain event service records event with nullable actor and metadata', function () {
    DomainEventService::record(
        DomainEventService::AGGREGATE_ORDER,
        99,
        'order_status_changed',
        null,
        ['old_status' => 'open', 'new_status' => 'paid'],
    );

    $event = DomainEvent::query()->first();

    expect($event)->not->toBeNull()
        ->and($event->aggregate_type)->toBe('order')
        ->and($event->aggregate_id)->toBe(99)
        ->and($event->event_type)->toBe('order_status_changed')
        ->and($event->actor_id)->toBeNull()
        ->and($event->metadata)->toMatchArray([
            'old_status' => 'open',
            'new_status' => 'paid',
        ]);
});

test('payment approve and reject emit domain events with status metadata', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['stock' => 5]);
    $order = Order::factory()->create(['user_id' => $buyer->id, 'payment_status' => PaymentStatus::Unpaid]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    $product->update(['stock' => 4]);

    PaymentTransitionService::approve($item, $seller);

    expect(DomainEvent::query()->where('event_type', 'payment_approved')->count())->toBe(1);
    $approved = DomainEvent::query()->where('event_type', 'payment_approved')->first();
    expect($approved->metadata)->toMatchArray([
        'from_status' => PaymentStatus::Unpaid->value,
        'to_status' => PaymentStatus::Paid->value,
    ])->and($approved->actor_id)->toBe($seller->id);

    $order2 = Order::factory()->create(['user_id' => $buyer->id, 'payment_status' => PaymentStatus::Unpaid]);
    $item2 = OrderItem::factory()->create([
        'order_id' => $order2->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    $product->update(['stock' => 3]);

    PaymentTransitionService::reject($item2, $seller, 'Tunai ditolak');

    expect(DomainEvent::query()->where('event_type', 'payment_rejected')->count())->toBe(1)
        ->and(DomainEvent::query()->where('event_type', 'order_item_cancelled')->count())->toBe(1)
        ->and(DomainEvent::query()->where('event_type', 'restock_completed')->count())->toBe(1);
});

test('duplicate payment transition does not emit duplicate events', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'payment_status' => PaymentStatus::Paid,
        'status' => OrderItemStatus::Pending,
    ]);

    expect(fn () => PaymentTransitionService::approve($item, $seller))
        ->toThrow(ValidationException::class);

    expect(DomainEvent::query()->where('event_type', 'payment_approved')->count())->toBe(0);
});

test('consignment transitions emit domain events', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->create();
    $up = UpJurusan::factory()->create();
    $actor = User::factory()->create(['role' => UserRole::PicketOfficer, 'up_jurusan_id' => $up->id]);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $up->id,
        'status' => UpJurusanConsignmentStatus::PendingApproval,
        'requested_quantity' => 10,
        'received_quantity' => 0,
        'sold_quantity' => 0,
    ]);

    ConsignmentTransitionService::approve($consignment, 10, $actor);
    expect(DomainEvent::query()->where('event_type', 'consignment_approved')->count())->toBe(1);

    ConsignmentTransitionService::receive($consignment->fresh(), 4, $actor);
    expect(DomainEvent::query()->where('event_type', 'consignment_received')->first()->metadata)
        ->toMatchArray([
            'from_status' => UpJurusanConsignmentStatus::Approved->value,
            'to_status' => UpJurusanConsignmentStatus::Received->value,
            'quantity' => 4,
        ]);

    ConsignmentTransitionService::recordSold($consignment->fresh(), 4, $actor);
    expect(DomainEvent::query()->where('event_type', 'consignment_sale_recorded')->count())->toBe(1)
        ->and($consignment->fresh()->status)->toBe(UpJurusanConsignmentStatus::Completed);
});

test('cancellation emits item cancelled and restock events with metadata', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['stock' => 5]);
    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status' => OrderStatus::Open,
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    $product->update(['stock' => 3]);

    OrderItemCancellation::cancelItem($item, $buyer, 'Batal beli');

    $cancelled = DomainEvent::query()->where('event_type', 'order_item_cancelled')->first();
    $restock = DomainEvent::query()->where('event_type', 'restock_completed')->first();

    expect($cancelled)->not->toBeNull()
        ->and($cancelled->metadata)->toMatchArray([
            'reason' => 'Batal beli',
            'restored_quantity' => 2,
        ])
        ->and($restock)->not->toBeNull()
        ->and($restock->metadata['restored_quantity'])->toBe(2);
});

test('settlement emits order_status_changed only when status changes', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status' => OrderStatus::Open,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderSettlementService::sync($order->fresh());

    expect(DomainEvent::query()->where('event_type', 'order_status_changed')->count())->toBe(1);
    $event = DomainEvent::query()->where('event_type', 'order_status_changed')->first();
    expect($event->metadata)->toMatchArray([
        'old_status' => OrderStatus::Open->value,
        'new_status' => OrderStatus::Paid->value,
    ])->and($event->actor_id)->toBeNull();

    OrderSettlementService::sync($order->fresh());
    expect(DomainEvent::query()->where('event_type', 'order_status_changed')->count())->toBe(1);
});

test('rollback does not persist domain events', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'payment_status' => PaymentStatus::Unpaid,
        'status' => OrderItemStatus::Pending,
    ]);

    try {
        DB::transaction(function () use ($item, $seller) {
            DomainEventService::record(
                DomainEventService::AGGREGATE_ORDER_ITEM,
                $item->id,
                'payment_approved',
                $seller,
                ['from_status' => 'unpaid', 'to_status' => 'paid'],
            );

            throw new RuntimeException('force rollback');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect(DomainEvent::query()->count())->toBe(0);
});

test('illegal consignment transition does not emit event', function () {
    $consignment = UpJurusanConsignment::factory()->create([
        'status' => UpJurusanConsignmentStatus::Rejected,
    ]);

    expect(fn () => ConsignmentTransitionService::approve($consignment, 10))
        ->toThrow(ValidationException::class);

    expect(DomainEvent::query()->count())->toBe(0);
});
