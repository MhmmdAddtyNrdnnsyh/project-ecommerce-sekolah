<?php

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductSalesMethod;
use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerApplication;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanPayout;
use App\Models\UpJurusanStockMovement;
use App\Models\User;

test('admin cannot approve seller application while buyer has active orders', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create();
    $order = Order::factory()->for($buyer)->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    $application = SellerApplication::factory()->create([
        'user_id' => $buyer->id,
        'status' => SellerApplication::PENDING,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.seller-applications.index'))
        ->post(route('admin.seller-applications.approve', $application))
        ->assertRedirect(route('admin.seller-applications.index'))
        ->assertSessionHasErrors('application');

    expect($buyer->fresh()->role)->toBe(UserRole::Buyer)
        ->and($application->fresh()->status)->toBe(SellerApplication::PENDING);
});

test('admin can approve seller application when buyer orders are terminal', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create();
    $order = Order::factory()->for($buyer)->create(['status' => OrderStatus::Completed]);
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Completed,
        'payment_status' => PaymentStatus::Paid,
    ]);
    $application = SellerApplication::factory()->create([
        'user_id' => $buyer->id,
        'status' => SellerApplication::PENDING,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.seller-applications.approve', $application))
        ->assertRedirect(route('admin.seller-applications.index'));

    expect($buyer->fresh()->role)->toBe(UserRole::Seller)
        ->and($application->fresh()->status)->toBe(SellerApplication::APPROVED);
});

test('seller cannot delete account with unpaid consignment payout', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->for($adminJurusan, 'adminJurusan')->create();
    $product = Product::factory()->for($seller, 'seller')->approved()->create([
        'sales_method' => ProductSalesMethod::UpJurusan,
    ]);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
        'status' => UpJurusanConsignmentStatus::Completed,
        'received_quantity' => 5,
        'sold_quantity' => 5,
    ]);
    UpJurusanStockMovement::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'product_id' => null,
        'order_id' => null,
        'user_id' => $seller->id,
        'type' => 'out',
        'quantity' => 5,
        'unit_price' => 1000,
        'gross_amount' => 5000,
        'commission_amount' => 500,
        'seller_amount' => 4500,
    ]);

    $this->actingAs($seller)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('password');

    expect($seller->fresh())->not->toBeNull();
});

test('seller cannot delete account with open consignment', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->for($adminJurusan, 'adminJurusan')->create();
    $product = Product::factory()->for($seller, 'seller')->approved()->create([
        'sales_method' => ProductSalesMethod::UpJurusan,
    ]);
    UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
        'status' => UpJurusanConsignmentStatus::Received,
        'received_quantity' => 3,
        'sold_quantity' => 0,
    ]);

    $this->actingAs($seller)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('password');
});

test('cannot unassign picket while up has active order items', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->for($adminJurusan, 'adminJurusan')->create();
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $upJurusan->id,
    ]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create([
        'sales_method' => ProductSalesMethod::UpJurusan,
        'up_jurusan_id' => null,
    ]);
    UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
        'status' => UpJurusanConsignmentStatus::Received,
        'received_quantity' => 2,
        'sold_quantity' => 1,
    ]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $order = Order::factory()->for($buyer)->create();
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    $this->actingAs($adminJurusan)
        ->from(route('admin-jurusan.up-jurusan.index'))
        ->post(route('admin-jurusan.up-jurusan.unassign-picket', $upJurusan))
        ->assertRedirect(route('admin-jurusan.up-jurusan.index'))
        ->assertSessionHasErrors('picket_id');

    expect($picket->fresh()->up_jurusan_id)->toBe($upJurusan->id);
});

test('can unassign picket when up has no active order items', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->for($adminJurusan, 'adminJurusan')->create();
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $upJurusan->id,
    ]);

    $this->actingAs($adminJurusan)
        ->post(route('admin-jurusan.up-jurusan.unassign-picket', $upJurusan))
        ->assertRedirect(route('admin-jurusan.up-jurusan.index'))
        ->assertSessionHas('success');

    expect($picket->fresh()->up_jurusan_id)->toBeNull();
});

test('cannot reassign picket while up has active order items', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->for($adminJurusan, 'adminJurusan')->create();
    $current = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $upJurusan->id,
    ]);
    $replacement = User::factory()->create(['role' => UserRole::PicketOfficer]);
    $product = Product::factory()->create([
        'seller_id' => null,
        'up_jurusan_id' => $upJurusan->id,
        'status' => ProductStatus::Approved,
    ]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $order = Order::factory()->for($buyer)->create();
    OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Packed,
        'payment_status' => PaymentStatus::Paid,
    ]);

    $this->actingAs($adminJurusan)
        ->from(route('admin-jurusan.up-jurusan.index'))
        ->post(route('admin-jurusan.up-jurusan.assign-picket', $upJurusan), [
            'picket_id' => $replacement->id,
        ])
        ->assertRedirect(route('admin-jurusan.up-jurusan.index'))
        ->assertSessionHasErrors('picket_id');

    expect($current->fresh()->up_jurusan_id)->toBe($upJurusan->id)
        ->and($replacement->fresh()->up_jurusan_id)->toBeNull();
});

test('cannot delete up jurusan with open consignments', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->for($adminJurusan, 'adminJurusan')->create();
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create();
    UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
        'status' => UpJurusanConsignmentStatus::Approved,
    ]);

    $this->actingAs($adminJurusan)
        ->from(route('admin-jurusan.up-jurusan.index'))
        ->delete(route('admin-jurusan.up-jurusan.destroy', $upJurusan))
        ->assertRedirect(route('admin-jurusan.up-jurusan.index'))
        ->assertSessionHasErrors('up_jurusan');

    expect(UpJurusan::query()->whereKey($upJurusan->id)->exists())->toBeTrue();
});

test('cannot delete up jurusan with unpaid payouts', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->for($adminJurusan, 'adminJurusan')->create();
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create();
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
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
        'quantity' => 2,
        'unit_price' => 1000,
        'gross_amount' => 2000,
        'commission_amount' => 200,
        'seller_amount' => 1800,
    ]);
    UpJurusanPayout::query()->create([
        'up_jurusan_consignment_id' => $consignment->id,
        'seller_id' => $seller->id,
        'user_id' => $adminJurusan->id,
        'amount' => 500,
    ]);

    $this->actingAs($adminJurusan)
        ->from(route('admin-jurusan.up-jurusan.index'))
        ->delete(route('admin-jurusan.up-jurusan.destroy', $upJurusan))
        ->assertRedirect(route('admin-jurusan.up-jurusan.index'))
        ->assertSessionHasErrors('up_jurusan');
});

test('can delete idle up jurusan', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->for($adminJurusan, 'adminJurusan')->create();

    $this->actingAs($adminJurusan)
        ->delete(route('admin-jurusan.up-jurusan.destroy', $upJurusan))
        ->assertRedirect(route('admin-jurusan.up-jurusan.index'))
        ->assertSessionHas('success');

    expect(UpJurusan::query()->whereKey($upJurusan->id)->exists())->toBeFalse();
});
