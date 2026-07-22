<?php

use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UpJurusanStatus;
use App\Enums\UserRole;
use App\Models\DomainEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanPayout;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use App\Support\OrganizationLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('close up succeeds when idle and emits event', function () {
    $admin = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $up = UpJurusan::factory()->for($admin, 'adminJurusan')->create();

    OrganizationLifecycleService::closeUpJurusan($up, $admin);

    expect($up->fresh()->status)->toBe(UpJurusanStatus::Closed)
        ->and(DomainEvent::query()->where('event_type', 'up_closed')->count())->toBe(1);
});

test('close up fails with active order', function () {
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
        'status' => OrderItemStatus::Packed,
        'payment_status' => PaymentStatus::Paid,
    ]);

    expect(fn () => OrganizationLifecycleService::closeUpJurusan($up, $admin))
        ->toThrow(ValidationException::class);

    expect($up->fresh()->status)->toBe(UpJurusanStatus::Active)
        ->and(DomainEvent::query()->count())->toBe(0);
});

test('close up fails with unpaid payout', function () {
    $admin = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $up = UpJurusan::factory()->for($admin, 'adminJurusan')->create();
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create();
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $up->id,
        'status' => UpJurusanConsignmentStatus::Completed,
        'received_quantity' => 2,
        'sold_quantity' => 2,
    ]);
    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'product_id' => null,
        'order_id' => null,
        'user_id' => $seller->id,
        'type' => 'out',
        'source' => 'pos_sale',
        'quantity' => 2,
        'unit_price' => 1000,
        'gross_amount' => 2000,
        'commission_amount' => 200,
        'seller_amount' => 1800,
    ]);
    UpJurusanPayout::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'seller_id' => $seller->id,
        'user_id' => $admin->id,
        'amount' => 500,
    ]);

    expect(fn () => OrganizationLifecycleService::closeUpJurusan($up, $admin))
        ->toThrow(ValidationException::class);
});

test('admin reassignment atomic and emits event', function () {
    $admin = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $next = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $up = UpJurusan::factory()->for($admin, 'adminJurusan')->create();

    OrganizationLifecycleService::reassignAdmin($up, $next, $admin);

    expect($up->fresh()->admin_jurusan_id)->toBe($next->id);
    $event = DomainEvent::query()->where('event_type', 'admin_reassigned')->first();
    expect($event)->not->toBeNull()
        ->and($event->metadata)->toMatchArray([
            'from_admin_id' => $admin->id,
            'to_admin_id' => $next->id,
        ]);
});

test('admin reassignment blocked when active process exists', function () {
    $admin = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $next = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $up = UpJurusan::factory()->for($admin, 'adminJurusan')->create();
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create();
    UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $up->id,
        'status' => UpJurusanConsignmentStatus::Received,
    ]);

    expect(fn () => OrganizationLifecycleService::reassignAdmin($up, $next, $admin))
        ->toThrow(ValidationException::class);

    expect($up->fresh()->admin_jurusan_id)->toBe($admin->id);
});

test('picket assign reassign unassign emit events and prevent orphan', function () {
    $admin = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $up = UpJurusan::factory()->for($admin, 'adminJurusan')->create();
    $picketA = User::factory()->create(['role' => UserRole::PicketOfficer]);
    $picketB = User::factory()->create(['role' => UserRole::PicketOfficer]);

    OrganizationLifecycleService::assignPicket($up, $picketA, $admin);
    expect($picketA->fresh()->up_jurusan_id)->toBe($up->id)
        ->and(DomainEvent::query()->where('event_type', 'picket_assigned')->count())->toBe(1);

    OrganizationLifecycleService::reassignPicket($up, $picketB, $admin);
    expect($picketA->fresh()->up_jurusan_id)->toBeNull()
        ->and($picketB->fresh()->up_jurusan_id)->toBe($up->id)
        ->and(DomainEvent::query()->where('event_type', 'picket_reassigned')->count())->toBe(1);

    OrganizationLifecycleService::unassignPicket($up, $admin);
    expect($picketB->fresh()->up_jurusan_id)->toBeNull()
        ->and(DomainEvent::query()->where('event_type', 'picket_unassigned')->count())->toBe(1);
});

test('picket unassign fails when active orders exist', function () {
    $admin = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $up = UpJurusan::factory()->for($admin, 'adminJurusan')->create();
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $up->id,
    ]);
    $product = Product::factory()->create([
        'seller_id' => null,
        'up_jurusan_id' => $up->id,
        'status' => ProductStatus::Approved,
    ]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $order = Order::factory()->for($buyer)->create();
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Packed,
        'payment_status' => PaymentStatus::Paid,
    ]);

    expect(fn () => OrganizationLifecycleService::unassignPicket($up, $admin))
        ->toThrow(ValidationException::class);

    expect($picket->fresh()->up_jurusan_id)->toBe($up->id);
});

test('delete up closes then removes and clears picket', function () {
    $admin = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $up = UpJurusan::factory()->for($admin, 'adminJurusan')->create();
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $up->id,
    ]);

    OrganizationLifecycleService::deleteUpJurusan($up, $admin);

    expect(UpJurusan::query()->whereKey($up->id)->exists())->toBeFalse()
        ->and($picket->fresh()->up_jurusan_id)->toBeNull()
        ->and(DomainEvent::query()->where('event_type', 'up_closed')->count())->toBe(1);
});
