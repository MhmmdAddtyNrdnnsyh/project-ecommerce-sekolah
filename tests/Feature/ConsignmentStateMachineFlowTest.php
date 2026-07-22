<?php

use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\User;

test('seller create then admin approve then picket receive and complete flow', function () {
    $admin = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $up = UpJurusan::factory()->create(['admin_jurusan_id' => $admin->id]);
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $up->id,
    ]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();

    $this->actingAs($seller)
        ->post(route('seller.products.store'), [
            'name' => 'Keripik State Machine',
            'category_id' => $category->id,
            'description' => 'Titipan seller full flow',
            'price' => 5000,
            'sales_method' => 'up_jurusan',
            'up_jurusan_id' => $up->id,
            'requested_quantity' => 6,
        ])
        ->assertRedirect(route('seller.products.index'));

    $consignment = UpJurusanConsignment::query()
        ->where('seller_id', $seller->id)
        ->where('up_jurusan_id', $up->id)
        ->firstOrFail();

    expect($consignment->status)->toBe(UpJurusanConsignmentStatus::PendingApproval);

    $this->actingAs($admin)
        ->post(route('admin-jurusan.consignments.approve', $consignment), [
            'commission_rate' => 10,
        ])
        ->assertRedirect(route('admin-jurusan.consignments.index'));

    expect($consignment->fresh()->status)->toBe(UpJurusanConsignmentStatus::Approved);

    $this->actingAs($picket)
        ->post(route('picket.up-jurusan.consignments.receive', $consignment), [
            'quantity' => 4,
        ])
        ->assertRedirect(route('picket.dashboard'));

    expect($consignment->fresh()->status)->toBe(UpJurusanConsignmentStatus::Received)
        ->and($consignment->fresh()->received_quantity)->toBe(4);

    $this->actingAs($picket)
        ->post(route('picket.up-jurusan.consignments.release', $consignment), [
            'quantity' => 4,
        ])
        ->assertRedirect(route('picket.pos'));

    expect($consignment->fresh()->status)->toBe(UpJurusanConsignmentStatus::Completed)
        ->and($consignment->fresh()->sold_quantity)->toBe(4);
});

test('admin jurusan reject flow marks product rejected', function () {
    $admin = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $up = UpJurusan::factory()->create(['admin_jurusan_id' => $admin->id]);
    $product = Product::factory()->create(['status' => ProductStatus::Pending]);
    $consignment = UpJurusanConsignment::factory()->create([
        'product_id' => $product->id,
        'up_jurusan_id' => $up->id,
        'status' => UpJurusanConsignmentStatus::PendingApproval,
    ]);

    $this->actingAs($admin)
        ->post(route('admin-jurusan.consignments.reject', $consignment), [
            'rejection_reason' => 'Tidak lolos QC',
        ])
        ->assertRedirect(route('admin-jurusan.consignments.index'));

    expect($consignment->fresh()->status)->toBe(UpJurusanConsignmentStatus::Rejected)
        ->and($product->fresh()->status)->toBe(ProductStatus::Rejected);
});

test('admin jurusan cancel approved consignment', function () {
    $admin = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $up = UpJurusan::factory()->create(['admin_jurusan_id' => $admin->id]);
    $consignment = UpJurusanConsignment::factory()->create([
        'up_jurusan_id' => $up->id,
        'status' => UpJurusanConsignmentStatus::Approved,
        'commission_rate' => 10,
        'received_quantity' => 0,
        'sold_quantity' => 0,
    ]);

    $this->actingAs($admin)
        ->post(route('admin-jurusan.consignments.cancel', $consignment), [
            'note' => 'Seller mundur',
        ])
        ->assertRedirect(route('admin-jurusan.consignments.index'));

    expect($consignment->fresh()->status)->toBe(UpJurusanConsignmentStatus::Cancelled);
});

test('illegal http approve twice and cancel after receive fail', function () {
    $admin = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $up = UpJurusan::factory()->create(['admin_jurusan_id' => $admin->id]);
    $product = Product::factory()->create(['status' => ProductStatus::Pending]);
    $consignment = UpJurusanConsignment::factory()->create([
        'product_id' => $product->id,
        'up_jurusan_id' => $up->id,
        'status' => UpJurusanConsignmentStatus::PendingApproval,
    ]);

    $this->actingAs($admin)
        ->post(route('admin-jurusan.consignments.approve', $consignment), [
            'commission_rate' => 10,
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->from(route('admin-jurusan.consignments.show', $consignment))
        ->post(route('admin-jurusan.consignments.approve', $consignment->fresh()), [
            'commission_rate' => 5,
        ])
        ->assertSessionHasErrors('status');

    $received = UpJurusanConsignment::factory()->create([
        'up_jurusan_id' => $up->id,
        'status' => UpJurusanConsignmentStatus::Received,
        'commission_rate' => 10,
        'received_quantity' => 3,
        'sold_quantity' => 0,
    ]);

    $this->actingAs($admin)
        ->from(route('admin-jurusan.consignments.show', $received))
        ->post(route('admin-jurusan.consignments.cancel', $received))
        ->assertSessionHasErrors('status');
});
