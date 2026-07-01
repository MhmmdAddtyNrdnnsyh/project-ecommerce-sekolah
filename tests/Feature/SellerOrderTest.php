<?php

use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductSalesMethod;
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

test('seller order list marks online consigned products as managed by up jurusan', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();
    $upJurusan = UpJurusan::factory()->create();

    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($this->category)
        ->approved()
        ->create([
            'name' => 'Risol Titipan',
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
        'product_name' => 'Risol Titipan',
        'status' => OrderItemStatus::Pending,
    ]);

    $this->actingAs($seller)
        ->get(route('seller.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('orderItems.data.0.id', $orderItem->id)
            ->where('orderItems.data.0.source', 'online')
            ->where('orderItems.data.0.managed_by_up_jurusan', true),
        );

    $this->actingAs($seller)
        ->get(route('seller.orders.show', $orderItem))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('orderItem.id', $orderItem->id)
            ->where('orderItem.managed_by_up_jurusan', true),
        );
});

test('seller can confirm cash payment for self managed order item', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($this->category)
        ->approved()
        ->create(['sales_method' => ProductSalesMethod::SelfManaged]);
    $order = Order::factory()->for($buyer)->create([
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    $orderItem = OrderItem::factory()->for($order)->for($product)->create([
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    $this->actingAs($seller)
        ->from(route('seller.orders.index'))
        ->post(route('seller.orders.payment.approve', $orderItem))
        ->assertRedirect(route('seller.orders.index'));

    $orderItem->refresh();
    $order->refresh();

    expect($orderItem->payment_status)->toBe(PaymentStatus::Paid)
        ->and($orderItem->payment_confirmed_by)->toBe($seller->id)
        ->and($orderItem->payment_confirmed_at)->not->toBeNull()
        ->and($order->payment_status)->toBe(PaymentStatus::Paid);
});

test('seller cannot confirm cash payment for up jurusan managed order item', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
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
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->for($order)->for($product)->create([
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    $this->actingAs($seller)
        ->from(route('seller.orders.index'))
        ->post(route('seller.orders.payment.approve', $orderItem))
        ->assertRedirect(route('seller.orders.index'))
        ->assertSessionHasErrors('payment');

    expect($orderItem->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('seller sees offline up jurusan consignment sales in order list', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $picket = User::factory()->create(['role' => UserRole::PicketOfficer]);
    $upJurusan = UpJurusan::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($this->category)
        ->approved()
        ->create(['name' => 'Keripik Titipan']);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
    ]);

    $movementId = DB::table('up_jurusan_stock_movements')->insertGetId([
        'up_jurusan_consignment_id' => $consignment->id,
        'product_id' => null,
        'user_id' => $picket->id,
        'type' => 'out',
        'quantity' => 2,
        'unit_price' => 10000,
        'gross_amount' => 20000,
        'commission_amount' => 2000,
        'seller_amount' => 18000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($seller)
        ->get(route('seller.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orderItems.data', 1)
            ->where('orderItems.data.0.source', 'offline')
            ->where('orderItems.data.0.detail_url', route('seller.orders.offline.show', $movementId, absolute: false))
            ->where('orderItems.data.0.product_name', 'Keripik Titipan')
            ->where('orderItems.data.0.subtotal', 18000)
            ->where('orderItems.data.0.status.label', 'Terjual offline'),
        );
});

test('seller can view offline up jurusan consignment sale detail', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $otherSeller = User::factory()->create(['role' => UserRole::Seller]);
    $picket = User::factory()->create(['role' => UserRole::PicketOfficer, 'name' => 'Picket RPL']);
    $upJurusan = UpJurusan::factory()->create(['name' => 'UP RPL']);
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($this->category)
        ->approved()
        ->create(['name' => 'Keripik Titipan']);
    $consignment = UpJurusanConsignment::factory()->create([
        'seller_id' => $seller->id,
        'product_id' => $product->id,
        'up_jurusan_id' => $upJurusan->id,
    ]);
    $movementId = DB::table('up_jurusan_stock_movements')->insertGetId([
        'up_jurusan_consignment_id' => $consignment->id,
        'product_id' => null,
        'user_id' => $picket->id,
        'type' => 'out',
        'quantity' => 2,
        'unit_price' => 10000,
        'gross_amount' => 20000,
        'commission_amount' => 2000,
        'seller_amount' => 18000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($seller)
        ->get(route('seller.orders.offline.show', $movementId))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('seller/orders/show')
            ->where('orderItem.source', 'offline')
            ->where('orderItem.product_name', 'Keripik Titipan')
            ->where('orderItem.gross_amount', 20000)
            ->where('orderItem.commission_amount', 2000)
            ->where('orderItem.seller_amount', 18000)
            ->where('orderItem.picket.name', 'Picket RPL')
            ->where('orderItem.up_jurusan.name', 'UP RPL'),
        );

    $this->actingAs($otherSeller)
        ->get(route('seller.orders.offline.show', $movementId))
        ->assertForbidden();
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

test('seller cannot update status from sent to completed', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();

    $product = Product::factory()->for($seller, 'seller')->for($this->category)->approved()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'status' => OrderItemStatus::Sent,
    ]);

    $this->actingAs($seller);

    $response = $this->from(route('seller.orders.index'))
        ->put(route('seller.orders.update-status', $orderItem), [
            'status' => OrderItemStatus::Completed->value,
        ]);

    $response->assertRedirect(route('seller.orders.index'));
    $response->assertSessionHasErrors('status');

    $this->assertDatabaseHas('order_items', [
        'id' => $orderItem->id,
        'status' => OrderItemStatus::Sent->value,
    ]);
});

test('seller cannot skip status from sent to completed', function () {
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

test('seller can progress pre-order status through production flow', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($this->category)
        ->approved()
        ->preOrder()
        ->create();
    $order = Order::factory()->create(['user_id' => $buyer->id]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'status' => OrderItemStatus::Pending,
        'is_pre_order' => true,
        'pre_order_estimate_days' => 7,
    ]);

    $this->actingAs($seller);

    $this->from(route('seller.orders.index'))
        ->put(route('seller.orders.update-status', $orderItem), [
            'status' => OrderItemStatus::InProduction->value,
        ])
        ->assertRedirect(route('seller.orders.index'))
        ->assertSessionHas('success');
    expect($orderItem->fresh()->status)->toBe(OrderItemStatus::InProduction);

    $this->from(route('seller.orders.index'))
        ->put(route('seller.orders.update-status', $orderItem), [
            'status' => OrderItemStatus::Ready->value,
        ])
        ->assertRedirect(route('seller.orders.index'))
        ->assertSessionHas('success');
    expect($orderItem->fresh()->status)->toBe(OrderItemStatus::Ready);

    $this->from(route('seller.orders.index'))
        ->put(route('seller.orders.update-status', $orderItem), [
            'status' => OrderItemStatus::Sent->value,
        ])
        ->assertRedirect(route('seller.orders.index'))
        ->assertSessionHas('success');
    expect($orderItem->fresh()->status)->toBe(OrderItemStatus::Sent);
});

test('seller cannot skip pre-order status to ready', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($this->category)
        ->approved()
        ->preOrder()
        ->create();
    $orderItem = OrderItem::factory()->create([
        'product_id' => $product->id,
        'status' => OrderItemStatus::Pending,
        'is_pre_order' => true,
    ]);

    $this->actingAs($seller)
        ->from(route('seller.orders.index'))
        ->put(route('seller.orders.update-status', $orderItem), [
            'status' => OrderItemStatus::Ready->value,
        ])
        ->assertRedirect(route('seller.orders.index'))
        ->assertSessionHasErrors('status');

    expect($orderItem->fresh()->status)->toBe(OrderItemStatus::Pending);
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
