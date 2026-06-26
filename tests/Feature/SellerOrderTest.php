<?php

use App\Enums\OrderItemStatus;
use App\Enums\ProductSalesMethod;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->category = Category::factory()->create();
});

test('seller can view their order items index', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();

    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($this->category)
        ->approved()
        ->create(['name' => 'Pulpen']);

    $order = Order::factory()->create(['user_id' => $buyer->id]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => 'Pulpen',
        'price' => 5000,
        'quantity' => 2,
        'subtotal' => 10000,
    ]);

    $this->actingAs($seller);

    $response = $this->get(route('seller.orders.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('seller/orders/index')
            ->has('orderItems.data', 1)
            ->where('orderItems.data.0.product_name', 'Pulpen'),
        );
});

test('seller only sees their own order items', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $otherSeller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();

    $myProduct = Product::factory()->for($seller, 'seller')->for($this->category)->approved()->create();
    $otherProduct = Product::factory()->for($otherSeller, 'seller')->for($this->category)->approved()->create();

    $order = Order::factory()->create(['user_id' => $buyer->id]);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $myProduct->id, 'product_name' => 'Produk Saya']);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $otherProduct->id, 'product_name' => 'Produk Lain']);

    $this->actingAs($seller);

    $response = $this->get(route('seller.orders.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orderItems.data', 1)
            ->where('orderItems.data.0.product_name', 'Produk Saya'),
        );
});

test('seller orders can be searched by product name', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();

    $productA = Product::factory()->for($seller, 'seller')->for($this->category)->approved()->create();
    $productB = Product::factory()->for($seller, 'seller')->for($this->category)->approved()->create();

    $order = Order::factory()->create(['user_id' => $buyer->id]);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $productA->id, 'product_name' => 'Pulpen Biru']);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $productB->id, 'product_name' => 'Buku Tulis']);

    $this->actingAs($seller);

    $response = $this->get(route('seller.orders.index', ['q' => 'Pulpen']));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orderItems.data', 1)
            ->where('orderItems.data.0.product_name', 'Pulpen Biru'),
        );
});

test('seller orders can be searched by order number or buyer name', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->for($this->category)->approved()->create();
    $matchedBuyer = User::factory()->create(['name' => 'Budi Santoso']);
    $otherBuyer = User::factory()->create(['name' => 'Siti Aminah']);
    $matchedOrder = Order::factory()->create(['user_id' => $matchedBuyer->id]);
    $otherOrder = Order::factory()->create(['user_id' => $otherBuyer->id]);

    OrderItem::factory()->create(['order_id' => $matchedOrder->id, 'product_id' => $product->id]);
    OrderItem::factory()->create(['order_id' => $otherOrder->id, 'product_id' => $product->id]);

    $this->actingAs($seller);

    $this->get(route('seller.orders.index', ['q' => (string) $matchedOrder->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orderItems.data', 1)
            ->where('orderItems.data.0.order_id', $matchedOrder->id),
        );

    $this->get(route('seller.orders.index', ['q' => 'Siti']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orderItems.data', 1)
            ->where('orderItems.data.0.order_id', $otherOrder->id),
        );
});

test('seller orders are paginated by ten and preserve filters', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();
    $product = Product::factory()->for($seller, 'seller')->for($this->category)->approved()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id]);

    OrderItem::factory()->count(11)->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'status' => OrderItemStatus::Pending,
    ]);

    $this->actingAs($seller);

    $this->get(route('seller.orders.index', ['status' => OrderItemStatus::Pending->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orderItems.data', 10)
            ->where('orderItems.total', 11)
            ->where('filters.status', OrderItemStatus::Pending->value)
            ->where('orderItems.next_page_url', fn (string $url) => str_contains($url, 'status=pending')),
        );
});

test('seller orders can be filtered by status', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();

    $product = Product::factory()->for($seller, 'seller')->for($this->category)->approved()->create();

    $order = Order::factory()->create(['user_id' => $buyer->id]);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'status' => OrderItemStatus::Pending]);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'status' => OrderItemStatus::Packed]);

    $this->actingAs($seller);

    $response = $this->get(route('seller.orders.index', ['status' => OrderItemStatus::Packed->value]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orderItems.data', 1)
            ->where('orderItems.data.0.status.code', OrderItemStatus::Packed->value),
        );
});

test('seller can view their order item detail', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();

    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($this->category)
        ->approved()
        ->create(['name' => 'Pulpen', 'price' => 5000]);

    $order = Order::factory()->create(['user_id' => $buyer->id]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => 'Pulpen',
        'price' => 5000,
        'quantity' => 2,
        'subtotal' => 10000,
    ]);

    $this->actingAs($seller);

    $response = $this->get(route('seller.orders.show', $orderItem));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('seller/orders/show')
            ->where('orderItem.id', $orderItem->id)
            ->where('orderItem.price', 5000),
        );
});

test('seller cannot view another sellers order item detail', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $otherSeller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();

    $product = Product::factory()->for($otherSeller, 'seller')->for($this->category)->approved()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id]);
    $orderItem = OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

    $this->actingAs($seller);

    $this->get(route('seller.orders.show', $orderItem))->assertForbidden();
});

test('seller can update status from pending to packed', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();

    $product = Product::factory()->for($seller, 'seller')->for($this->category)->approved()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'status' => OrderItemStatus::Pending,
    ]);

    $this->actingAs($seller);

    $response = $this->from(route('seller.orders.index'))
        ->put(route('seller.orders.update-status', $orderItem), [
            'status' => OrderItemStatus::Packed->value,
        ]);

    $response->assertRedirect(route('seller.orders.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('order_items', [
        'id' => $orderItem->id,
        'status' => OrderItemStatus::Packed->value,
    ]);
});

test('seller can update status from packed to sent', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();

    $product = Product::factory()->for($seller, 'seller')->for($this->category)->approved()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'status' => OrderItemStatus::Packed,
    ]);

    $this->actingAs($seller);

    $response = $this->from(route('seller.orders.index'))
        ->put(route('seller.orders.update-status', $orderItem), [
            'status' => OrderItemStatus::Sent->value,
        ]);

    $response->assertRedirect(route('seller.orders.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('order_items', [
        'id' => $orderItem->id,
        'status' => OrderItemStatus::Sent->value,
    ]);
});

test('seller cannot skip status from pending to sent', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();

    $product = Product::factory()->for($seller, 'seller')->for($this->category)->approved()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'status' => OrderItemStatus::Pending,
    ]);

    $this->actingAs($seller);

    $response = $this->from(route('seller.orders.index'))
        ->put(route('seller.orders.update-status', $orderItem), [
            'status' => OrderItemStatus::Sent->value,
        ]);

    $response->assertRedirect(route('seller.orders.index'));
    $response->assertSessionHasErrors('status');
    $this->assertDatabaseHas('order_items', [
        'id' => $orderItem->id,
        'status' => OrderItemStatus::Pending->value,
    ]);
});

test('seller cannot go backwards from packed to pending', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();

    $product = Product::factory()->for($seller, 'seller')->for($this->category)->approved()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'status' => OrderItemStatus::Packed,
    ]);

    $this->actingAs($seller);

    $response = $this->from(route('seller.orders.index'))
        ->put(route('seller.orders.update-status', $orderItem), [
            'status' => OrderItemStatus::Pending->value,
        ]);

    $response->assertRedirect(route('seller.orders.index'));
    $response->assertSessionHasErrors('status');
    $this->assertDatabaseHas('order_items', [
        'id' => $orderItem->id,
        'status' => OrderItemStatus::Packed->value,
    ]);
});

test('seller cannot repeat the current order item status', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->for($this->category)->approved()->create();
    $orderItem = OrderItem::factory()->create([
        'product_id' => $product->id,
        'status' => OrderItemStatus::Packed,
    ]);

    $this->actingAs($seller);

    $this->from(route('seller.orders.index'))
        ->put(route('seller.orders.update-status', $orderItem), [
            'status' => OrderItemStatus::Packed->value,
        ])
        ->assertRedirect(route('seller.orders.index'))
        ->assertSessionHasErrors('status');

    expect($orderItem->fresh()->status)->toBe(OrderItemStatus::Packed);
});

test('seller receives an Indonesian validation error for an invalid status', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->for($this->category)->approved()->create();
    $orderItem = OrderItem::factory()->create(['product_id' => $product->id]);

    $this->actingAs($seller);

    $this->from(route('seller.orders.index'))
        ->put(route('seller.orders.update-status', $orderItem), ['status' => 'cancelled'])
        ->assertRedirect(route('seller.orders.index'))
        ->assertSessionHasErrors([
            'status' => 'Status pesanan tidak valid.',
        ]);
});

test('seller cannot update another sellers order item status', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $otherSeller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();

    $product = Product::factory()->for($otherSeller, 'seller')->for($this->category)->approved()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'status' => OrderItemStatus::Pending,
    ]);

    $this->actingAs($seller);

    $this->put(route('seller.orders.update-status', $orderItem), [
        'status' => OrderItemStatus::Packed->value,
    ])->assertForbidden();
});

test('seller cannot update status for product consigned to up jurusan', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();
    $upJurusan = UpJurusan::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($this->category)
        ->approved()
        ->create(['sales_method' => ProductSalesMethod::UpJurusan]);
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

    $this->actingAs($seller)
        ->from(route('seller.orders.index'))
        ->put(route('seller.orders.update-status', $orderItem), [
            'status' => OrderItemStatus::Packed->value,
        ])
        ->assertRedirect(route('seller.orders.index'))
        ->assertSessionHasErrors('status');

    expect($orderItem->fresh()->status)->toBe(OrderItemStatus::Pending);
});

test('non seller users cannot access orders', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->for($this->category)->approved()->create();
    $orderItem = OrderItem::factory()->create(['product_id' => $product->id]);

    $this->actingAs($user);

    $this->get(route('seller.orders.index'))->assertForbidden();
    $this->get(route('seller.orders.show', $orderItem))->assertForbidden();
    $this->put(route('seller.orders.update-status', $orderItem), [
        'status' => OrderItemStatus::Packed->value,
    ])->assertForbidden();
})->with([
    UserRole::Admin,
    UserRole::Buyer,
    UserRole::PicketOfficer,
]);
