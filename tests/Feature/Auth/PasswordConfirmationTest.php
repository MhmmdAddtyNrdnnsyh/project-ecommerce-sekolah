<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('password.confirm'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/confirm-password'),
    );
});

test('password confirmation requires authentication', function () {
    $response = $this->get(route('password.confirm'));

    $response->assertRedirect(route('login'));
});

test('seller password confirmation does not redirect to the admin dashboard from intended url', function () {
    $user = User::factory()->create([
        'role' => UserRole::Seller,
    ]);

    $response = $this
        ->actingAs($user)
        ->withSession(['url.intended' => route('dashboard')])
        ->post(route('password.confirm.store'), [
            'password' => 'password',
        ]);

    $response->assertRedirect(route('seller.dashboard', absolute: false));
});

test('admin password confirmation does not redirect to the seller dashboard from intended url', function () {
    $user = User::factory()->create([
        'role' => UserRole::Admin,
    ]);

    $response = $this
        ->actingAs($user)
        ->withSession(['url.intended' => route('seller.dashboard')])
        ->post(route('password.confirm.store'), [
            'password' => 'password',
        ]);

    $response->assertRedirect(route('dashboard', absolute: false));
});
