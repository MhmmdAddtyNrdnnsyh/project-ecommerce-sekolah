<?php

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Support\PurchasableProductService;
use Inertia\Testing\AssertableInertia as Assert;

test('cart marks rejected product invalid without deleting cart row', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create(['stock' => 5]);
    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $product->update(['status' => ProductStatus::Rejected]);

    $this->actingAs($buyer)
        ->get(route('cart.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cart/index')
            ->where('items.0.is_valid', false)
            ->where('summary.has_invalid_items', true)
            ->where('items.0.invalid_reasons.0', PurchasableProductService::REASON_PRODUCT_REJECTED),
        );

    $this->assertDatabaseHas('cart_items', [
        'user_id' => $buyer->id,
        'product_id' => $product->id,
    ]);
});

test('cart marks out of stock product invalid', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create(['stock' => 5]);
    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    $product->update(['stock' => 1]);

    $this->actingAs($buyer)
        ->get(route('cart.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.is_valid', false)
            ->where('items.0.invalid_reasons.0', PurchasableProductService::REASON_OUT_OF_STOCK),
        );
});

test('checkout rejects stale cart after product moderation rejection', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create(['stock' => 5, 'price' => 1000]);
    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $product->update(['status' => ProductStatus::Rejected]);

    $this->actingAs($buyer)
        ->from(route('checkout.confirm'))
        ->post(route('checkout'), ['pickup_method' => 'pickup'])
        ->assertRedirect(route('checkout.confirm'))
        ->assertSessionHasErrors('cart');

    $this->assertDatabaseMissing('orders', ['user_id' => $buyer->id]);
});

test('checkout confirm surfaces same invalid codes as cart', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create(['stock' => 5]);
    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);
    $product->update(['status' => ProductStatus::Draft]);

    $this->actingAs($buyer)
        ->get(route('checkout.confirm'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('checkout/confirm')
            ->where('items.0.is_valid', false)
            ->where('items.0.invalid_reasons.0', PurchasableProductService::REASON_PRODUCT_REJECTED)
            ->where('summary.has_invalid_items', true),
        );
});
