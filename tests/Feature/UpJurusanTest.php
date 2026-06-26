<?php

use App\Enums\OrderItemStatus;
use App\Enums\ProductSalesMethod;
use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('admin jurusan can create an up jurusan', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);

    $this->actingAs($adminJurusan)
        ->post(route('admin-jurusan.up-jurusan.store'), [
            'name' => 'UP RPL',
            'description' => 'Unit produksi jurusan RPL.',
        ])
        ->assertRedirect(route('admin-jurusan.up-jurusan.index'));

    $this->assertDatabaseHas('up_jurusans', [
        'admin_jurusan_id' => $adminJurusan->id,
        'name' => 'UP RPL',
        'description' => 'Unit produksi jurusan RPL.',
    ]);
});

test('admin jurusan cannot create more than one up jurusan', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    UpJurusan::factory()->create(['admin_jurusan_id' => $adminJurusan->id]);

    $this->actingAs($adminJurusan)
        ->from(route('admin-jurusan.up-jurusan.index'))
        ->post(route('admin-jurusan.up-jurusan.store'), [
            'name' => 'UP Kedua',
            'description' => 'Tidak boleh dibuat.',
        ])
        ->assertRedirect(route('admin-jurusan.up-jurusan.index'))
        ->assertSessionHasErrors('up_jurusan');

    expect(UpJurusan::query()->where('admin_jurusan_id', $adminJurusan->id)->count())->toBe(1);
});

test('admin jurusan can assign picket officer to own up jurusan', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->create(['admin_jurusan_id' => $adminJurusan->id]);
    $picket = User::factory()->create(['role' => UserRole::PicketOfficer]);

    $this->actingAs($adminJurusan)
        ->from(route('admin-jurusan.up-jurusan.index'))
        ->post(route('admin-jurusan.up-jurusan.assign-picket', $upJurusan), [
            'picket_id' => $picket->id,
        ])
        ->assertRedirect(route('admin-jurusan.up-jurusan.index'));

    $this->assertDatabaseHas('users', [
        'id' => $picket->id,
        'up_jurusan_id' => $upJurusan->id,
    ]);
});

test('admin jurusan can create product owned by own up jurusan', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->create(['admin_jurusan_id' => $adminJurusan->id]);
    $category = Category::factory()->create();

    $this->actingAs($adminJurusan)
        ->post(route('admin-jurusan.products.store'), [
            'up_jurusan_id' => $upJurusan->id,
            'category_id' => $category->id,
            'name' => 'Produk Jurusan RPL',
            'description' => 'Produk resmi milik UP jurusan RPL.',
            'price' => 10000,
            'stock' => 5,
        ])
        ->assertRedirect(route('admin-jurusan.up-jurusan.index'));

    $this->assertDatabaseHas('products', [
        'seller_id' => null,
        'up_jurusan_id' => $upJurusan->id,
        'category_id' => $category->id,
        'name' => 'Produk Jurusan RPL',
        'stock' => 5,
        'status' => ProductStatus::Approved->value,
    ]);
});

test('admin jurusan cannot create product for another up jurusan', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $otherUpJurusan = UpJurusan::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($adminJurusan)
        ->post(route('admin-jurusan.products.store'), [
            'up_jurusan_id' => $otherUpJurusan->id,
            'category_id' => $category->id,
            'name' => 'Produk Jurusan RPL',
            'description' => 'Produk resmi milik UP jurusan RPL.',
            'price' => 10000,
            'stock' => 5,
        ])
        ->assertForbidden();
});

test('admin jurusan cannot assign picket officer to another admin jurusan up', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $otherUpJurusan = UpJurusan::factory()->create();
    $picket = User::factory()->create(['role' => UserRole::PicketOfficer]);

    $this->actingAs($adminJurusan)
        ->post(route('admin-jurusan.up-jurusan.assign-picket', $otherUpJurusan), [
            'picket_id' => $picket->id,
        ])
        ->assertForbidden();
});

test('admin jurusan can view scoped dashboard summary', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $ownUp = UpJurusan::factory()->create(['admin_jurusan_id' => $adminJurusan->id]);
    $otherUp = UpJurusan::factory()->create();

    UpJurusanConsignment::factory()->create([
        'up_jurusan_id' => $ownUp->id,
        'requested_quantity' => 10,
        'received_quantity' => 0,
        'sold_quantity' => 0,
        'status' => UpJurusanConsignmentStatus::PendingApproval,
    ]);
    UpJurusanConsignment::factory()->create([
        'up_jurusan_id' => $ownUp->id,
        'requested_quantity' => 8,
        'received_quantity' => 5,
        'sold_quantity' => 2,
        'status' => UpJurusanConsignmentStatus::Received,
    ]);
    UpJurusanConsignment::factory()->create([
        'up_jurusan_id' => $otherUp->id,
        'requested_quantity' => 99,
        'received_quantity' => 99,
        'sold_quantity' => 0,
        'status' => UpJurusanConsignmentStatus::PendingApproval,
    ]);

    $this->actingAs($adminJurusan)
        ->get(route('admin-jurusan.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin-jurusan/dashboard')
            ->where('dashboard.total_up_jurusans', 1)
            ->where('dashboard.pending_requests', 1)
            ->where('dashboard.approved_requests', 1)
            ->where('dashboard.active_stock', 3)
            ->has('dashboard.recent_requests', 2),
        );
});

test('seller can request consignment through product creation', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $upJurusan = UpJurusan::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($seller)
        ->post(route('seller.products.store'), [
            'name' => 'Risol Mayo',
            'category_id' => $category->id,
            'description' => 'Risol mayo titipan untuk kantin jurusan.',
            'price' => 3000,
            'sales_method' => 'up_jurusan',
            'up_jurusan_id' => $upJurusan->id,
            'requested_quantity' => 6,
        ])
        ->assertRedirect(route('seller.products.index'));

    $this->assertDatabaseHas('up_jurusan_consignments', [
        'seller_id' => $seller->id,
        'up_jurusan_id' => $upJurusan->id,
        'requested_quantity' => 6,
        'received_quantity' => 0,
        'sold_quantity' => 0,
        'status' => 'pending_approval',
    ]);
});

test('admin jurusan can approve own up jurusan consignment request', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->create(['admin_jurusan_id' => $adminJurusan->id]);
    $consignment = UpJurusanConsignment::factory()->create([
        'up_jurusan_id' => $upJurusan->id,
        'status' => 'pending_approval',
    ]);

    $this->actingAs($adminJurusan)
        ->post(route('admin-jurusan.consignments.approve', $consignment))
        ->assertRedirect(route('admin-jurusan.consignments.index'));

    $this->assertDatabaseHas('up_jurusan_consignments', [
        'id' => $consignment->id,
        'status' => 'approved',
    ]);
});

test('admin jurusan rejection marks seller product as rejected', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->create(['admin_jurusan_id' => $adminJurusan->id]);
    $product = Product::factory()->create(['status' => ProductStatus::Pending]);
    $consignment = UpJurusanConsignment::factory()->create([
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
        'status' => UpJurusanConsignmentStatus::PendingApproval,
    ]);

    $this->actingAs($adminJurusan)
        ->post(route('admin-jurusan.consignments.reject', $consignment))
        ->assertRedirect(route('admin-jurusan.consignments.index'));

    $this->assertDatabaseHas('up_jurusan_consignments', [
        'id' => $consignment->id,
        'status' => UpJurusanConsignmentStatus::Rejected->value,
    ]);
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'status' => ProductStatus::Rejected->value,
    ]);
});

test('admin jurusan can view own consignment request detail', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->create(['admin_jurusan_id' => $adminJurusan->id]);
    $seller = User::factory()->create(['role' => UserRole::Seller, 'name' => 'Seller Kantin']);
    $product = Product::factory()->for($seller, 'seller')->create([
        'name' => 'Risol Mayo',
        'price' => 3000,
        'stock' => 0,
        'description' => 'Risol mayo titipan.',
    ]);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
        'requested_quantity' => 12,
        'status' => UpJurusanConsignmentStatus::PendingApproval,
    ]);

    $this->actingAs($adminJurusan)
        ->get(route('admin-jurusan.consignments.show', $consignment))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin-jurusan/consignments/show')
            ->where('consignment.id', $consignment->id)
            ->where('consignment.seller.name', 'Seller Kantin')
            ->where('consignment.product.name', 'Risol Mayo')
            ->where('consignment.requested_quantity', 12),
        );
});

test('admin jurusan cannot view another up consignment request detail', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $consignment = UpJurusanConsignment::factory()->create();

    $this->actingAs($adminJurusan)
        ->get(route('admin-jurusan.consignments.show', $consignment))
        ->assertForbidden();
});

test('admin jurusan receives physical stock and picket records sales with commission split', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->create(['admin_jurusan_id' => $adminJurusan->id]);
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $upJurusan->id,
    ]);
    $product = Product::factory()->create(['price' => 3000, 'stock' => 0]);
    $consignment = UpJurusanConsignment::factory()->create([
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
        'requested_quantity' => 10,
        'received_quantity' => 0,
        'sold_quantity' => 0,
        'commission_rate' => 10,
        'status' => 'approved',
    ]);

    $this->actingAs($adminJurusan)
        ->post(route('admin-jurusan.consignments.receive', $consignment), [
            'quantity' => 8,
        ])
        ->assertRedirect(route('admin-jurusan.consignments.show', $consignment));

    $this->assertDatabaseHas('up_jurusan_consignments', [
        'id' => $consignment->id,
        'received_quantity' => 8,
        'status' => 'received',
    ]);
    $this->assertDatabaseHas('products', [
        'id' => $consignment->product_id,
        'stock' => 0,
    ]);
    $this->assertDatabaseHas('up_jurusan_stock_movements', [
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $adminJurusan->id,
        'type' => 'in',
        'quantity' => 8,
    ]);

    $this->actingAs($picket)
        ->post(route('picket.up-jurusan.consignments.release', $consignment), [
            'quantity' => 3,
        ])
        ->assertRedirect(route('picket.pos'));

    $this->assertDatabaseHas('up_jurusan_consignments', [
        'id' => $consignment->id,
        'received_quantity' => 8,
        'sold_quantity' => 3,
        'status' => 'received',
    ]);
    $this->assertDatabaseHas('products', [
        'id' => $consignment->product_id,
        'stock' => 0,
    ]);
    $this->assertDatabaseHas('up_jurusan_stock_movements', [
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $picket->id,
        'type' => 'out',
        'quantity' => 3,
        'unit_price' => 3000,
        'gross_amount' => 9000,
        'commission_amount' => 900,
        'seller_amount' => 8100,
    ]);
});

test('picket officer cannot receive consigned stock', function () {
    $upJurusan = UpJurusan::factory()->create();
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $upJurusan->id,
    ]);
    $consignment = UpJurusanConsignment::factory()->create([
        'up_jurusan_id' => $upJurusan->id,
        'status' => UpJurusanConsignmentStatus::Approved,
    ]);

    $this->actingAs($picket)
        ->post(route('picket.up-jurusan.consignments.receive', $consignment), [
            'quantity' => 1,
        ])
        ->assertForbidden();
});

test('admin jurusan records seller payout from consignment earnings', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $upJurusan = UpJurusan::factory()->create(['admin_jurusan_id' => $adminJurusan->id]);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'up_jurusan_id' => $upJurusan->id,
    ]);

    DB::table('up_jurusan_stock_movements')->insert([
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $adminJurusan->id,
        'type' => 'out',
        'quantity' => 3,
        'unit_price' => 3000,
        'gross_amount' => 9000,
        'commission_amount' => 900,
        'seller_amount' => 8100,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($adminJurusan)
        ->post(route('admin-jurusan.consignments.payout', $consignment), [
            'amount' => 5000,
            'note' => 'Transfer kas UP.',
        ])
        ->assertRedirect(route('admin-jurusan.consignments.show', $consignment));

    $this->assertDatabaseHas('up_jurusan_payouts', [
        'up_jurusan_consignment_id' => $consignment->id,
        'seller_id' => $seller->id,
        'user_id' => $adminJurusan->id,
        'amount' => 5000,
        'note' => 'Transfer kas UP.',
    ]);
});

test('picket release completes consignment when all received stock is sold', function () {
    $upJurusan = UpJurusan::factory()->create();
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $upJurusan->id,
    ]);
    $consignment = UpJurusanConsignment::factory()->create([
        'up_jurusan_id' => $upJurusan->id,
        'received_quantity' => 8,
        'sold_quantity' => 3,
        'status' => UpJurusanConsignmentStatus::Received,
    ]);

    $this->actingAs($picket)
        ->post(route('picket.up-jurusan.consignments.release', $consignment), [
            'quantity' => 5,
        ])
        ->assertRedirect(route('picket.pos'));

    $this->assertDatabaseHas('up_jurusan_consignments', [
        'id' => $consignment->id,
        'sold_quantity' => 8,
        'status' => 'completed',
    ]);
});

test('picket officer only sees and updates assigned up jurusan consignments', function () {
    $assignedUp = UpJurusan::factory()->create();
    $otherUp = UpJurusan::factory()->create();
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $assignedUp->id,
    ]);
    $assignedConsignment = UpJurusanConsignment::factory()->create([
        'up_jurusan_id' => $assignedUp->id,
        'status' => UpJurusanConsignmentStatus::Approved,
    ]);
    $otherConsignment = UpJurusanConsignment::factory()->create([
        'up_jurusan_id' => $otherUp->id,
        'status' => UpJurusanConsignmentStatus::Approved,
    ]);

    $this->actingAs($picket)
        ->get(route('picket.up-jurusan.consignments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('consignments', 1)
            ->where('consignments.0.id', $assignedConsignment->id),
        );

    $this->actingAs($picket)
        ->post(route('picket.up-jurusan.consignments.receive', $otherConsignment), [
            'quantity' => 1,
        ])
        ->assertForbidden();
});

test('picket officer sees pos products and daily sales summary for assigned up jurusan', function () {
    $this->travelTo('2026-06-25 12:00:00');

    $assignedUp = UpJurusan::factory()->create(['name' => 'UP RPL']);
    $otherUp = UpJurusan::factory()->create();
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $assignedUp->id,
    ]);
    $seller = User::factory()->create(['role' => UserRole::Seller, 'name' => 'Seller RPL']);
    $product = Product::factory()->for($seller, 'seller')->create([
        'name' => 'Risol Mayo',
        'price' => 3000,
        'stock' => 7,
    ]);
    $activeConsignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $assignedUp->id,
        'received_quantity' => 10,
        'sold_quantity' => 3,
        'status' => UpJurusanConsignmentStatus::Received,
    ]);
    UpJurusanConsignment::factory()->create([
        'up_jurusan_id' => $assignedUp->id,
        'received_quantity' => 5,
        'sold_quantity' => 5,
        'status' => UpJurusanConsignmentStatus::Completed,
    ]);
    UpJurusanConsignment::factory()->create([
        'up_jurusan_id' => $otherUp->id,
        'received_quantity' => 10,
        'sold_quantity' => 0,
        'status' => UpJurusanConsignmentStatus::Received,
    ]);

    DB::table('up_jurusan_stock_movements')->insert([
        [
            'up_jurusan_consignment_id' => $activeConsignment->id,
            'user_id' => $picket->id,
            'type' => 'out',
            'quantity' => 2,
            'created_at' => '2026-06-25 10:00:00',
            'updated_at' => '2026-06-25 10:00:00',
        ],
        [
            'up_jurusan_consignment_id' => $activeConsignment->id,
            'user_id' => $picket->id,
            'type' => 'in',
            'quantity' => 5,
            'created_at' => '2026-06-25 09:00:00',
            'updated_at' => '2026-06-25 09:00:00',
        ],
        [
            'up_jurusan_consignment_id' => $activeConsignment->id,
            'user_id' => $picket->id,
            'type' => 'out',
            'quantity' => 9,
            'created_at' => '2026-06-24 10:00:00',
            'updated_at' => '2026-06-24 10:00:00',
        ],
    ]);

    $this->actingAs($picket)
        ->get(route('picket.up-jurusan.consignments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('picket/up-jurusan/consignments/index')
            ->where('up_jurusan.name', 'UP RPL')
            ->has('pos_products', 1)
            ->where('pos_products.0.id', $activeConsignment->id)
            ->where('pos_products.0.product_name', 'Risol Mayo')
            ->where('pos_products.0.available_quantity', 7)
            ->where('pos_products.0.price', 3000)
            ->where('daily_report.date', '2026-06-25')
            ->where('daily_report.total_sold', 2)
            ->where('daily_report.total_revenue', 6000)
            ->has('daily_report.items', 1)
            ->where('daily_report.items.0.product_name', 'Risol Mayo')
            ->where('daily_report.items.0.quantity', 2)
            ->where('daily_report.items.0.subtotal', 6000),
        );
});

test('picket officer can record cart sale for assigned up jurusan', function () {
    $upJurusan = UpJurusan::factory()->create();
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $upJurusan->id,
    ]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $productA = Product::factory()->for($seller, 'seller')->create(['price' => 5000]);
    $productB = Product::factory()->for($seller, 'seller')->create(['price' => 7000]);
    $consignmentA = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $productA->id,
        'up_jurusan_id' => $upJurusan->id,
        'received_quantity' => 5,
        'sold_quantity' => 1,
        'commission_rate' => 10,
        'status' => UpJurusanConsignmentStatus::Received,
    ]);
    $consignmentB = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $productB->id,
        'up_jurusan_id' => $upJurusan->id,
        'received_quantity' => 3,
        'sold_quantity' => 0,
        'commission_rate' => 20,
        'status' => UpJurusanConsignmentStatus::Received,
    ]);

    $this->actingAs($picket)
        ->post(route('picket.up-jurusan.sales.store'), [
            'items' => [
                ['id' => $consignmentA->id, 'quantity' => 2],
                ['id' => $consignmentB->id, 'quantity' => 1],
            ],
        ])
        ->assertRedirect(route('picket.pos'));

    $this->assertDatabaseHas('up_jurusan_consignments', [
        'id' => $consignmentA->id,
        'sold_quantity' => 3,
    ]);
    $this->assertDatabaseHas('up_jurusan_consignments', [
        'id' => $consignmentB->id,
        'sold_quantity' => 1,
    ]);
    $this->assertDatabaseHas('up_jurusan_stock_movements', [
        'up_jurusan_consignment_id' => $consignmentA->id,
        'user_id' => $picket->id,
        'type' => 'out',
        'quantity' => 2,
        'gross_amount' => 10000,
        'commission_amount' => 1000,
        'seller_amount' => 9000,
    ]);
    $this->assertDatabaseHas('up_jurusan_stock_movements', [
        'up_jurusan_consignment_id' => $consignmentB->id,
        'user_id' => $picket->id,
        'type' => 'out',
        'quantity' => 1,
        'gross_amount' => 7000,
        'commission_amount' => 1400,
        'seller_amount' => 5600,
    ]);
});

test('picket officer can update assigned up jurusan order item status', function () {
    $upJurusan = UpJurusan::factory()->create();
    $otherUpJurusan = UpJurusan::factory()->create();
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $upJurusan->id,
    ]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->create([
        'sales_method' => ProductSalesMethod::UpJurusan,
    ]);
    UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
    ]);
    $order = Order::factory()->create(['user_id' => $buyer->id]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'status' => OrderItemStatus::Pending,
    ]);

    $this->actingAs($picket)
        ->put(route('picket.orders.update-status', $orderItem), [
            'status' => OrderItemStatus::Packed->value,
        ])
        ->assertRedirect(route('picket.orders'));

    expect($orderItem->fresh()->status)->toBe(OrderItemStatus::Packed);

    $picket->update(['up_jurusan_id' => $otherUpJurusan->id]);

    $this->actingAs($picket)
        ->put(route('picket.orders.update-status', $orderItem), [
            'status' => OrderItemStatus::Sent->value,
        ])
        ->assertForbidden();
});

test('picket officer can submit daily sales report', function () {
    $this->travelTo('2026-06-25 12:00:00');

    $upJurusan = UpJurusan::factory()->create();
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $upJurusan->id,
    ]);
    $consignment = UpJurusanConsignment::factory()->create(['up_jurusan_id' => $upJurusan->id]);

    DB::table('up_jurusan_stock_movements')->insert([
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $picket->id,
        'type' => 'out',
        'quantity' => 2,
        'created_at' => '2026-06-25 10:00:00',
        'updated_at' => '2026-06-25 10:00:00',
    ]);

    $this->actingAs($picket)
        ->post(route('picket.up-jurusan.report.store'))
        ->assertRedirect(route('picket.reports'))
        ->assertSessionHas('success', 'Laporan penjualan hari ini sudah dibuat.');
});

test('admin jurusan can view scoped daily stock movement report', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $ownUp = UpJurusan::factory()->create(['admin_jurusan_id' => $adminJurusan->id]);
    $otherUp = UpJurusan::factory()->create();
    $ownConsignment = UpJurusanConsignment::factory()->create(['up_jurusan_id' => $ownUp->id]);
    $otherConsignment = UpJurusanConsignment::factory()->create(['up_jurusan_id' => $otherUp->id]);
    $picket = User::factory()->create(['role' => UserRole::PicketOfficer]);

    DB::table('up_jurusan_stock_movements')->insert([
        [
            'up_jurusan_consignment_id' => $ownConsignment->id,
            'user_id' => $picket->id,
            'type' => 'in',
            'quantity' => 10,
            'created_at' => '2026-06-25 08:00:00',
            'updated_at' => '2026-06-25 08:00:00',
        ],
        [
            'up_jurusan_consignment_id' => $ownConsignment->id,
            'user_id' => $picket->id,
            'type' => 'out',
            'quantity' => 4,
            'created_at' => '2026-06-25 10:00:00',
            'updated_at' => '2026-06-25 10:00:00',
        ],
        [
            'up_jurusan_consignment_id' => $otherConsignment->id,
            'user_id' => $picket->id,
            'type' => 'out',
            'quantity' => 99,
            'created_at' => '2026-06-25 10:00:00',
            'updated_at' => '2026-06-25 10:00:00',
        ],
    ]);

    $this->actingAs($adminJurusan)
        ->get(route('admin-jurusan.reports.index', ['date' => '2026-06-25']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin-jurusan/reports/index')
            ->where('filters.date', '2026-06-25')
            ->where('summary.in', 10)
            ->where('summary.out', 4)
            ->has('movements', 2),
        );
});
