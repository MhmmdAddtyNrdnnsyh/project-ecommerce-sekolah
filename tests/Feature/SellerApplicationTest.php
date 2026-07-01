<?php

use App\Enums\UserRole;
use App\Models\SellerApplication;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('buyer can submit a seller application', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);

    $this->actingAs($buyer)
        ->get(route('seller-application.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('seller-application/index')
            ->where('application', null)
        );

    $this->actingAs($buyer)
        ->post(route('seller-application.store'), [
            'store_name' => 'Toko ATK XI RPL',
            'phone' => '08123456789',
            'product_plan' => 'Alat tulis dan karya jurusan.',
            'reason' => 'Ingin latihan jualan.',
        ])
        ->assertRedirect(route('seller-application.index'));

    $this->assertDatabaseHas('seller_applications', [
        'user_id' => $buyer->id,
        'store_name' => 'Toko ATK XI RPL',
        'status' => SellerApplication::PENDING,
    ]);
});

test('buyer cannot submit another pending seller application', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    SellerApplication::factory()->create([
        'user_id' => $buyer->id,
        'status' => SellerApplication::PENDING,
    ]);

    $this->actingAs($buyer)
        ->from(route('seller-application.index'))
        ->post(route('seller-application.store'), [
            'store_name' => 'Toko Kedua',
            'phone' => '08123456789',
            'product_plan' => 'Produk lain.',
        ])
        ->assertRedirect(route('seller-application.index'))
        ->assertSessionHasErrors('store_name');
});

test('admin can approve seller application and promote buyer to seller', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $application = SellerApplication::factory()->create([
        'user_id' => $buyer->id,
        'status' => SellerApplication::PENDING,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.seller-applications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/seller-applications/index')
            ->has('sellerApplications', 1)
            ->where('sellerApplications.0.id', $application->id)
        );

    $this->actingAs($admin)
        ->post(route('admin.seller-applications.approve', $application))
        ->assertRedirect(route('admin.seller-applications.index'));

    $this->assertDatabaseHas('seller_applications', [
        'id' => $application->id,
        'status' => SellerApplication::APPROVED,
        'reviewed_by' => $admin->id,
    ]);
    $this->assertDatabaseHas('users', [
        'id' => $buyer->id,
        'role' => UserRole::Seller->value,
    ]);
});

test('admin can reject seller application', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $application = SellerApplication::factory()->create([
        'user_id' => $buyer->id,
        'status' => SellerApplication::PENDING,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.seller-applications.reject', $application), [
            'rejection_reason' => 'Data belum lengkap.',
        ])
        ->assertRedirect(route('admin.seller-applications.index'));

    $this->assertDatabaseHas('seller_applications', [
        'id' => $application->id,
        'status' => SellerApplication::REJECTED,
        'reviewed_by' => $admin->id,
        'rejection_reason' => 'Data belum lengkap.',
    ]);
    $this->assertDatabaseHas('users', [
        'id' => $buyer->id,
        'role' => UserRole::Buyer->value,
    ]);
});
