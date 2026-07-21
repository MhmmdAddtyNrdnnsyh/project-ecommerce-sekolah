<?php

use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductSalesMethod;
use App\Enums\UserRole;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\User;
use App\Support\OrderItemFulfillment;
use App\Support\PreOrderRules;
use Inertia\Testing\AssertableInertia as Assert;

test('checkout succeeds for pre-order before deadline at minimum quantity', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->preOrder(5)->create([
        'price' => 1000,
        'pre_order_deadline' => now()->addDay()->toDateString(),
        'pre_order_min_quantity' => 5,
    ]);
    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $this->actingAs($buyer)
        ->post(route('checkout'), ['pickup_method' => 'pickup'])
        ->assertRedirect();

    $this->assertDatabaseHas('order_items', [
        'product_id' => $product->id,
        'quantity' => 5,
        'is_pre_order' => true,
    ]);
});

test('checkout rejects pre-order after deadline', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->preOrder(5)->create([
        'pre_order_deadline' => now()->subDay()->toDateString(),
        'pre_order_min_quantity' => 1,
    ]);
    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($buyer)
        ->from(route('checkout.confirm'))
        ->post(route('checkout'), ['pickup_method' => 'pickup'])
        ->assertRedirect(route('checkout.confirm'))
        ->assertSessionHasErrors('cart');

    $this->assertDatabaseMissing('orders', ['user_id' => $buyer->id]);
});

test('checkout rejects pre-order quantity below minimum', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->preOrder(5)->create([
        'pre_order_deadline' => now()->addDays(3)->toDateString(),
        'pre_order_min_quantity' => 10,
    ]);
    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 9,
    ]);

    $this->actingAs($buyer)
        ->from(route('checkout.confirm'))
        ->post(route('checkout'), ['pickup_method' => 'pickup'])
        ->assertRedirect(route('checkout.confirm'))
        ->assertSessionHasErrors('cart');
});

test('cart add rejects pre-order after deadline', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->preOrder()->create([
        'pre_order_deadline' => now()->subDays(2)->toDateString(),
        'pre_order_min_quantity' => 1,
    ]);

    $this->actingAs($buyer)
        ->from(route('catalog.show', $product->slug))
        ->post(route('cart.items.store', $product->slug), ['quantity' => 1])
        ->assertRedirect(route('catalog.show', $product->slug))
        ->assertSessionHasErrors('quantity');
});

test('cart add rejects pre-order quantity below minimum', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->preOrder()->create([
        'pre_order_deadline' => now()->addWeek()->toDateString(),
        'pre_order_min_quantity' => 8,
    ]);

    $this->actingAs($buyer)
        ->from(route('catalog.show', $product->slug))
        ->post(route('cart.items.store', $product->slug), ['quantity' => 3])
        ->assertRedirect(route('catalog.show', $product->slug))
        ->assertSessionHasErrors('quantity');
});

test('cart update rejects pre-order quantity below minimum', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->preOrder()->create([
        'pre_order_deadline' => now()->addWeek()->toDateString(),
        'pre_order_min_quantity' => 5,
    ]);
    $cartItem = CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $this->actingAs($buyer)
        ->from(route('cart.index'))
        ->put(route('cart.items.update', $cartItem), ['quantity' => 2])
        ->assertRedirect(route('cart.index'))
        ->assertSessionHasErrors('quantity');

    expect($cartItem->fresh()->quantity)->toBe(5);
});

test('cart index marks stale pre-order item when deadline passed', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->preOrder()->create([
        'pre_order_deadline' => now()->addWeek()->toDateString(),
        'pre_order_min_quantity' => 1,
    ]);
    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->update(['pre_order_deadline' => now()->subDay()->toDateString()]);

    $this->actingAs($buyer)
        ->get(route('cart.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cart/index')
            ->where('items.0.is_valid', false)
            ->where('summary.has_invalid_items', true)
            ->has('items.0.invalid_reasons', 1),
        );
});

test('cart index marks stale pre-order item when minimum quantity increases', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->preOrder()->create([
        'pre_order_deadline' => now()->addWeek()->toDateString(),
        'pre_order_min_quantity' => 2,
    ]);
    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->update(['pre_order_min_quantity' => 10]);

    $this->actingAs($buyer)
        ->get(route('cart.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.is_valid', false)
            ->where('summary.has_invalid_items', true),
        );
});

test('checkout rejects cart with stale pre-order items', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->preOrder()->create([
        'pre_order_deadline' => now()->addWeek()->toDateString(),
        'pre_order_min_quantity' => 1,
    ]);
    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->update(['pre_order_deadline' => now()->subDays(3)->toDateString()]);

    $this->actingAs($buyer)
        ->from(route('checkout.confirm'))
        ->post(route('checkout'), ['pickup_method' => 'pickup'])
        ->assertRedirect(route('checkout.confirm'))
        ->assertSessionHasErrors('cart');
});

test('seller pre-order transition uses production path not packed', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->for($seller, 'seller')->approved()->preOrder()->create();
    $order = Order::factory()->for($buyer)->create();
    $item = OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
        'is_pre_order' => true,
    ]);

    expect(OrderItemFulfillment::expectedNext($item))->toBe(OrderItemStatus::InProduction)
        ->and(OrderItemFulfillment::allowedNextStatusValues($item))->toBe([OrderItemStatus::InProduction->value]);

    $this->actingAs($seller)
        ->put(route('seller.orders.update-status', $item), [
            'status' => OrderItemStatus::Packed->value,
        ])
        ->assertSessionHasErrors('status');

    $this->actingAs($seller)
        ->put(route('seller.orders.update-status', $item), [
            'status' => OrderItemStatus::InProduction->value,
        ])
        ->assertRedirect(route('seller.orders.index'));

    expect($item->fresh()->status)->toBe(OrderItemStatus::InProduction);
});

test('picket pre-order transition uses production path not packed', function () {
    $adminJurusan = User::factory()->create(['role' => UserRole::AdminJurusan]);
    $upJurusan = UpJurusan::factory()->for($adminJurusan, 'adminJurusan')->create();
    $picket = User::factory()->create([
        'role' => UserRole::PicketOfficer,
        'up_jurusan_id' => $upJurusan->id,
    ]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->preOrder()->create([
        'sales_method' => ProductSalesMethod::UpJurusan,
    ]);
    UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
    ]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $order = Order::factory()->for($buyer)->create();
    $item = OrderItem::factory()->for($order)->for($product)->create([
        'status' => OrderItemStatus::Pending,
        'payment_status' => PaymentStatus::Paid,
        'is_pre_order' => true,
    ]);

    $this->actingAs($picket)
        ->put(route('picket.orders.update-status', $item), [
            'status' => OrderItemStatus::Packed->value,
        ])
        ->assertSessionHasErrors('status');

    $this->actingAs($picket)
        ->put(route('picket.orders.update-status', $item), [
            'status' => OrderItemStatus::InProduction->value,
        ])
        ->assertRedirect(route('picket.orders'));

    expect($item->fresh()->status)->toBe(OrderItemStatus::InProduction);
});

test('ready stock checkout regression still succeeds', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create(['stock' => 4, 'price' => 2500]);
    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($buyer)
        ->post(route('checkout'), ['pickup_method' => 'pickup'])
        ->assertRedirect();

    expect($product->fresh()->stock)->toBe(2);
});

test('pre order rules helper unit cases', function () {
    $product = Product::factory()->approved()->preOrder()->create([
        'pre_order_deadline' => now()->subDay()->toDateString(),
        'pre_order_min_quantity' => 5,
    ]);

    expect(PreOrderRules::isValid($product, 5))->toBeFalse()
        ->and(PreOrderRules::isDeadlinePassed($product))->toBeTrue()
        ->and(PreOrderRules::isBelowMinimumQuantity($product, 3))->toBeTrue();

    $product->update([
        'pre_order_deadline' => now()->addDay()->toDateString(),
        'pre_order_min_quantity' => 5,
    ]);

    expect(PreOrderRules::isValid($product->fresh(), 5))->toBeTrue();
});
