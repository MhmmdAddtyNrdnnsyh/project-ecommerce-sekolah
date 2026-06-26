<?php

use App\Enums\OrderStatus;
use App\Enums\ProductSalesMethod;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\User;

test('authenticated buyer can checkout and convert cart into an order', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()
        ->approved()
        ->create([
            'name' => 'Pulpen Gel Hitam',
            'slug' => 'pulpen-gel-hitam',
            'price' => 5000,
            'stock' => 10,
        ]);

    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($buyer);

    $response = $this->from(route('cart.index'))->post(route('checkout'));

    $response
        ->assertRedirect(route('cart.index'))
        ->assertSessionHas('success', 'Pesanan berhasil dibuat.');

    $this->assertDatabaseHas('orders', [
        'user_id' => $buyer->id,
        'status' => OrderStatus::Pending->value,
        'total_price' => 10000,
    ]);

    $order = $buyer->orders()->first();

    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => 'Pulpen Gel Hitam',
        'price' => 5000,
        'quantity' => 2,
        'subtotal' => 10000,
    ]);

    expect($order->items)->toHaveCount(1);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock' => 8,
    ]);

    $this->assertDatabaseMissing('cart_items', [
        'user_id' => $buyer->id,
    ]);
});

test('checkout stores pickup method details', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create(['price' => 5000, 'stock' => 3]);

    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->actingAs($buyer)
        ->from(route('checkout.confirm'))
        ->post(route('checkout'), [
            'pickup_method' => 'delivery',
            'pickup_location' => 'Titip di meja piket.',
        ])
        ->assertRedirect(route('cart.index'));

    $this->assertDatabaseHas('orders', [
        'user_id' => $buyer->id,
        'pickup_method' => 'delivery',
        'pickup_location' => 'Titip di meja piket.',
    ]);
});

test('delivery checkout requires pickup location', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create(['stock' => 3]);

    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->actingAs($buyer)
        ->from(route('checkout.confirm'))
        ->post(route('checkout'), [
            'pickup_method' => 'delivery',
        ])
        ->assertRedirect(route('checkout.confirm'))
        ->assertSessionHasErrors('pickup_location');

    $this->assertDatabaseMissing('orders', [
        'user_id' => $buyer->id,
    ]);
});

test('authenticated buyer can view payment confirmation page', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create(['price' => 5000, 'stock' => 3]);

    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->actingAs($buyer)
        ->get(route('checkout.confirm'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('checkout/confirm')
            ->where('summary.total_items', 1)
            ->where('summary.total_price', 5000));
});

test('payment confirmation shows pickup place for up jurusan products', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $upJurusan = UpJurusan::factory()->create(['name' => 'UP RPL']);
    $product = Product::factory()
        ->for($seller, 'seller')
        ->approved()
        ->create(['price' => 5000, 'stock' => 3]);

    UpJurusanConsignment::factory()
        ->for($seller, 'seller')
        ->for($product)
        ->for($upJurusan)
        ->create();
    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->actingAs($buyer)
        ->get(route('checkout.confirm'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('checkout/confirm')
            ->where('items.0.product.pickup_place.name', 'UP RPL'));
});

test('payment confirmation can be scoped to selected cart items', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $selectedProduct = Product::factory()->approved()->create(['price' => 5000, 'stock' => 3]);
    $otherProduct = Product::factory()->approved()->create(['price' => 9000, 'stock' => 3]);
    $selectedItem = CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $selectedProduct->id,
        'quantity' => 1,
    ]);
    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $otherProduct->id,
        'quantity' => 1,
    ]);

    $this->actingAs($buyer)
        ->get(route('checkout.confirm', ['items' => (string) $selectedItem->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('checkout/confirm')
            ->has('items', 1)
            ->where('items.0.id', $selectedItem->id)
            ->where('summary.total_items', 1)
            ->where('summary.total_price', 5000));
});

test('payment confirmation can be opened for buy now product without cart item', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create([
        'name' => 'Mie Goreng',
        'slug' => 'mie-goreng',
        'price' => 6000,
        'stock' => 4,
    ]);

    $this->actingAs($buyer)
        ->get(route('checkout.confirm', ['product' => $product->slug]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('checkout/confirm')
            ->has('items', 1)
            ->where('items.0.product.name', 'Mie Goreng')
            ->where('items.0.source', 'buy_now')
            ->where('summary.total_items', 1)
            ->where('summary.total_price', 6000));

    $this->assertDatabaseMissing('cart_items', [
        'user_id' => $buyer->id,
        'product_id' => $product->id,
    ]);
});

test('buy now checkout creates order without adding product to cart', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->approved()->create([
        'name' => 'Mie Goreng',
        'price' => 6000,
        'stock' => 4,
    ]);

    $this->actingAs($buyer)
        ->from(route('checkout.confirm', ['product' => $product->slug]))
        ->post(route('checkout'), [
            'pickup_method' => 'pickup',
            'buy_now_product_id' => $product->id,
            'buy_now_quantity' => 1,
        ])
        ->assertRedirect(route('cart.index'));

    $this->assertDatabaseHas('orders', [
        'user_id' => $buyer->id,
        'total_price' => 6000,
    ]);
    $this->assertDatabaseHas('order_items', [
        'product_id' => $product->id,
        'product_name' => 'Mie Goreng',
        'quantity' => 1,
        'subtotal' => 6000,
    ]);
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock' => 3,
    ]);
    $this->assertDatabaseMissing('cart_items', [
        'user_id' => $buyer->id,
        'product_id' => $product->id,
    ]);
});

test('checkout only converts selected cart items into an order', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $selectedProduct = Product::factory()->approved()->create(['price' => 5000, 'stock' => 3]);
    $otherProduct = Product::factory()->approved()->create(['price' => 9000, 'stock' => 3]);
    $selectedItem = CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $selectedProduct->id,
        'quantity' => 1,
    ]);
    $otherItem = CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $otherProduct->id,
        'quantity' => 1,
    ]);

    $this->actingAs($buyer)
        ->from(route('checkout.confirm', ['items' => (string) $selectedItem->id]))
        ->post(route('checkout'), [
            'pickup_method' => 'pickup',
            'selected_cart_item_ids' => [$selectedItem->id],
        ])
        ->assertRedirect(route('cart.index'));

    $this->assertDatabaseHas('orders', [
        'user_id' => $buyer->id,
        'total_price' => 5000,
    ]);
    $this->assertDatabaseHas('order_items', [
        'product_id' => $selectedProduct->id,
        'subtotal' => 5000,
    ]);
    $this->assertDatabaseHas('cart_items', [
        'id' => $otherItem->id,
        'user_id' => $buyer->id,
    ]);
    $this->assertDatabaseMissing('cart_items', [
        'id' => $selectedItem->id,
    ]);
});

test('checkout uses up jurusan consignment stock without changing seller product stock', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $upJurusan = UpJurusan::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->approved()
        ->create([
            'name' => 'Risol Titipan',
            'price' => 3000,
            'sales_method' => ProductSalesMethod::UpJurusan,
            'stock' => 0,
        ]);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
        'received_quantity' => 8,
        'sold_quantity' => 3,
        'commission_rate' => 10,
    ]);
    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($buyer)
        ->post(route('checkout'), ['pickup_method' => 'pickup'])
        ->assertRedirect(route('cart.index'));

    $this->assertDatabaseHas('up_jurusan_consignments', [
        'id' => $consignment->id,
        'sold_quantity' => 5,
    ]);
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock' => 0,
    ]);
    $this->assertDatabaseHas('up_jurusan_stock_movements', [
        'up_jurusan_consignment_id' => $consignment->id,
        'user_id' => $buyer->id,
        'type' => 'out',
        'quantity' => 2,
        'gross_amount' => 6000,
        'commission_amount' => 600,
        'seller_amount' => 5400,
    ]);
});

test('checkout rejects an empty cart and creates no order', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);

    $this->actingAs($buyer);

    $response = $this->from(route('cart.index'))->post(route('checkout'));

    $response->assertRedirect(route('cart.index'));
    $response->assertSessionHasErrors('cart');

    $this->assertDatabaseMissing('orders', [
        'user_id' => $buyer->id,
    ]);
});

test('checkout rejects quantity exceeding stock and rolls back', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()
        ->approved()
        ->create([
            'price' => 7000,
            'stock' => 3,
        ]);

    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $this->actingAs($buyer);

    $response = $this->from(route('cart.index'))->post(route('checkout'));

    $response->assertRedirect(route('cart.index'));
    $response->assertSessionHasErrors('cart');

    $this->assertDatabaseMissing('orders', [
        'user_id' => $buyer->id,
    ]);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock' => 3,
    ]);

    $this->assertDatabaseHas('cart_items', [
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);
});

test('checkout rolls back stock and order items when a later cart item is out of stock', function () {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $availableProduct = Product::factory()
        ->approved()
        ->create([
            'price' => 5000,
            'stock' => 10,
        ]);
    $staleProduct = Product::factory()
        ->approved()
        ->create([
            'price' => 7000,
            'stock' => 1,
        ]);

    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $availableProduct->id,
        'quantity' => 2,
    ]);
    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $staleProduct->id,
        'quantity' => 2,
    ]);

    $this->actingAs($buyer);

    $response = $this->from(route('cart.index'))->post(route('checkout'));

    $response->assertRedirect(route('cart.index'));
    $response->assertSessionHasErrors('cart');

    $this->assertDatabaseMissing('orders', [
        'user_id' => $buyer->id,
    ]);
    $this->assertDatabaseMissing('order_items', [
        'product_id' => $availableProduct->id,
    ]);
    $this->assertDatabaseHas('products', [
        'id' => $availableProduct->id,
        'stock' => 10,
    ]);
    $this->assertDatabaseHas('products', [
        'id' => $staleProduct->id,
        'stock' => 1,
    ]);
    $this->assertDatabaseHas('cart_items', [
        'user_id' => $buyer->id,
        'product_id' => $availableProduct->id,
        'quantity' => 2,
    ]);
    $this->assertDatabaseHas('cart_items', [
        'user_id' => $buyer->id,
        'product_id' => $staleProduct->id,
        'quantity' => 2,
    ]);
});

test('checkout rejects non approved products and rolls back', function (ProductStatus $status) {
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $product = Product::factory()->create([
        'status' => $status,
        'stock' => 5,
    ]);

    CartItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->actingAs($buyer);

    $response = $this->from(route('cart.index'))->post(route('checkout'));

    $response->assertRedirect(route('cart.index'));
    $response->assertSessionHasErrors('cart');

    $this->assertDatabaseMissing('orders', [
        'user_id' => $buyer->id,
    ]);
})->with([
    ProductStatus::Draft,
    ProductStatus::Pending,
    ProductStatus::Rejected,
]);

test('guest is redirected from the checkout endpoint', function () {
    $this->from(route('cart.index'))->post(route('checkout'))->assertRedirect(route('login'));
});

test('guest is redirected from the payment confirmation page', function () {
    $this->get(route('checkout.confirm'))->assertRedirect(route('login'));
});

test('non buyer users cannot checkout', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->post(route('checkout'))
        ->assertForbidden();
})->with([
    UserRole::Admin,
    UserRole::Seller,
    UserRole::PicketOfficer,
]);

test('non buyer users cannot view payment confirmation', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route('checkout.confirm'))
        ->assertForbidden();
})->with([
    UserRole::Admin,
    UserRole::Seller,
    UserRole::PicketOfficer,
]);
