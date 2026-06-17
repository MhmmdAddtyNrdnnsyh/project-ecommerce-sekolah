<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('guests are redirected from the seller dashboard to the login page', function () {
    $response = $this->get(route('seller.dashboard'));
    $response->assertRedirect(route('login'));
});

test('admin users can visit the dashboard', function () {
    $user = User::factory()->create([
        'role' => UserRole::Admin,
    ]);
    User::factory()->unverified()->create([
        'name' => 'Seller Pending',
        'role' => UserRole::Seller,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('dashboard.stats', 4)
            ->has('dashboard.userGrowthData', 8)
            ->has('dashboard.roleDistributionData', 4)
            ->has('dashboard.adminQueue', 1)
            ->where('dashboard.adminQueue.0.owner', 'Seller Pending')
            ->has('dashboard.platformHealth', 4)
            ->has('dashboard.activities'),
        );
});

test('unverified admin users are redirected to the email verification prompt', function () {
    $user = User::factory()->unverified()->create([
        'role' => UserRole::Admin,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('verification.notice'));
});

test('non admin users cannot visit the dashboard', function (UserRole $role) {
    $user = User::factory()->create([
        'role' => $role,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertForbidden();
})->with([
    UserRole::Buyer,
    UserRole::Seller,
    UserRole::PicketOfficer,
]);

test('seller users can visit the seller dashboard', function () {
    $user = User::factory()->create([
        'role' => UserRole::Seller,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('seller.dashboard'));
    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('seller/dashboard')
            ->has('dashboard.stats', 4)
            ->has('dashboard.salesData', 7)
            ->has('dashboard.orderMixData', 4)
            ->where('dashboard.orders', [])
            ->where('dashboard.topProducts', [])
            ->where('dashboard.stockAlerts', [])
            ->has('dashboard.tasks', 3),
        );
});

test('unverified seller users are redirected to the email verification prompt', function () {
    $user = User::factory()->unverified()->create([
        'role' => UserRole::Seller,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('seller.dashboard'));
    $response->assertRedirect(route('verification.notice'));
});

test('non seller users cannot visit the seller dashboard', function (UserRole $role) {
    $user = User::factory()->create([
        'role' => $role,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('seller.dashboard'));
    $response->assertForbidden();
})->with([
    UserRole::Admin,
    UserRole::Buyer,
    UserRole::PicketOfficer,
]);
