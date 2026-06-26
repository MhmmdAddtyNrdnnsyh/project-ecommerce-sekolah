<?php

use App\Enums\OrderItemStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
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
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    Product::factory()->for($seller, 'seller')->for($category)->create([
        'status' => ProductStatus::Pending,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('dashboard.stats', 4)
            ->has('dashboard.userGrowthData', 8)
            ->has('dashboard.roleDistributionData', 5)
            ->has('dashboard.adminQueue', 1)
            ->where('dashboard.adminQueue.0.owner', $seller->name)
            ->has('dashboard.platformHealth', 4)
            ->has('dashboard.activities'),
        );
});

test('admin dashboard uses real product and order data', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create(['role' => UserRole::Buyer]);
    $category = Category::factory()->create();
    $approvedProduct = Product::factory()->for($seller, 'seller')->for($category)->approved()->create();
    Product::factory()->for($seller, 'seller')->for($category)->create([
        'status' => ProductStatus::Pending,
    ]);
    $order = Order::factory()->for($buyer)->create(['total_price' => 25_000]);
    OrderItem::factory()->for($order)->for($approvedProduct)->create([
        'subtotal' => 25_000,
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.stats.2.label', 'Produk Live')
            ->where('dashboard.stats.2.value', '1')
            ->where('dashboard.stats.2.context', '2 total produk')
            ->where('dashboard.stats.3.label', 'Transaksi')
            ->where('dashboard.stats.3.value', '1')
            ->where('dashboard.stats.3.context', 'Rp 25.000 omzet tercatat')
            ->where('dashboard.adminQueue.0.area', 'Moderasi produk')
            ->where('dashboard.adminQueue.0.owner', $seller->name),
        );
});

test('admin header notifications contain admin action items', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $pendingProduct = Product::factory()->for($seller, 'seller')->for($category)->create([
        'name' => 'Produk Pending Admin',
        'status' => ProductStatus::Pending,
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('adminHeader.notifications.0.type', 'product')
            ->where('adminHeader.notifications.0.title', $pendingProduct->name)
            ->where('adminHeader.notifications.0.href', route('admin.products.moderation.index', absolute: false))
            ->has('adminHeader.notifications', 1)
            ->where('adminHeader.supportEmail', config('mail.from.address')),
        );
});

test('header notifications show more than three items for admin and seller', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();

    Product::factory()->count(6)->for($seller, 'seller')->for($category)->create([
        'status' => ProductStatus::Pending,
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('adminHeader.notifications', 6),
        );

    Product::query()->delete();

    Product::factory()->count(6)->for($seller, 'seller')->for($category)->approved()->create([
        'stock' => 1,
    ]);

    $this->actingAs($seller)
        ->get(route('seller.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('sellerHeader.notifications', 6),
        );
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
    UserRole::AdminJurusan,
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
            ->has('dashboard.orderMixData', 3)
            ->where('dashboard.orders', [])
            ->where('dashboard.topProducts', [])
            ->where('dashboard.stockAlerts', [])
            ->has('dashboard.tasks', 3),
        );
});

test('seller dashboard uses real data scoped to the current seller', function () {
    $this->travelTo('2026-06-21 12:00:00');

    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $otherSeller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create(['name' => 'Pembeli Utama']);
    $category = Category::factory()->create(['name' => 'Alat Tulis']);

    $topProduct = Product::factory()->for($seller, 'seller')->for($category)->approved()->create([
        'name' => 'Pulpen Biru',
        'stock' => 3,
    ]);
    $secondProduct = Product::factory()->for($seller, 'seller')->for($category)->approved()->create([
        'name' => 'Buku Tulis',
        'stock' => 0,
    ]);
    Product::factory()->for($seller, 'seller')->for($category)->create([
        'name' => 'Penggaris',
        'status' => ProductStatus::Pending,
        'stock' => 1,
    ]);
    Product::factory()->for($seller, 'seller')->for($category)->create([
        'name' => 'Pensil',
        'status' => ProductStatus::Pending,
        'stock' => 2,
    ]);
    Product::factory()->for($seller, 'seller')->for($category)->create([
        'name' => 'Penghapus',
        'status' => ProductStatus::Pending,
        'stock' => 4,
    ]);
    Product::factory()->for($seller, 'seller')->for($category)->create([
        'name' => 'Spidol',
        'status' => ProductStatus::Pending,
        'stock' => 5,
    ]);
    $normalProduct = Product::factory()->for($seller, 'seller')->for($category)->approved()->create(['stock' => 10]);

    $todayOrder = Order::factory()->for($buyer)->create(['created_at' => '2026-06-21 09:00:00']);
    OrderItem::factory()->for($todayOrder)->for($topProduct)->create([
        'product_name' => $topProduct->name,
        'quantity' => 2,
        'subtotal' => 10_000,
        'status' => OrderItemStatus::Pending,
        'created_at' => '2026-06-21 09:00:00',
    ]);
    OrderItem::factory()->for($todayOrder)->for($secondProduct)->create([
        'product_name' => $secondProduct->name,
        'quantity' => 3,
        'subtotal' => 15_000,
        'status' => OrderItemStatus::Packed,
        'created_at' => '2026-06-21 09:01:00',
    ]);

    $yesterdayOrder = Order::factory()->for($buyer)->create(['created_at' => '2026-06-20 10:00:00']);
    OrderItem::factory()->count(3)->for($yesterdayOrder)->for($secondProduct)->sequence(
        fn ($sequence) => [
            'product_name' => $secondProduct->name,
            'quantity' => 1,
            'subtotal' => 1_000,
            'status' => OrderItemStatus::Pending,
            'created_at' => '2026-06-20 10:0'.$sequence->index.':00',
        ],
    )->create();

    $sixDaysAgoOrder = Order::factory()->for($buyer)->create(['created_at' => '2026-06-15 08:00:00']);
    OrderItem::factory()->for($sixDaysAgoOrder)->for($topProduct)->create([
        'product_name' => $topProduct->name,
        'quantity' => 5,
        'subtotal' => 20_000,
        'status' => OrderItemStatus::Sent,
        'created_at' => '2026-06-15 08:00:00',
    ]);

    $oldOrder = Order::factory()->for($buyer)->create(['created_at' => '2026-05-31 08:00:00']);
    OrderItem::factory()->for($oldOrder)->for($normalProduct)->create([
        'product_name' => $normalProduct->name,
        'quantity' => 1,
        'subtotal' => 999_000,
        'created_at' => '2026-05-31 08:00:00',
    ]);

    $otherProduct = Product::factory()->for($otherSeller, 'seller')->for($category)->approved()->create(['stock' => 1]);
    $otherOrder = Order::factory()->for($buyer)->create();
    OrderItem::factory()->for($otherOrder)->for($otherProduct)->create([
        'quantity' => 100,
        'subtotal' => 9_999_000,
        'created_at' => '2026-06-21 11:00:00',
    ]);

    $this->actingAs($seller)
        ->get(route('seller.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.stats.0.label', 'Omzet Bulan Ini')
            ->where('dashboard.stats.0.value', 'Rp 48.000')
            ->where('dashboard.stats.1.label', 'Pesanan Masuk')
            ->where('dashboard.stats.1.value', '3')
            ->where('dashboard.stats.2.value', '3')
            ->where('dashboard.stats.3.label', 'Stok Rendah')
            ->where('dashboard.stats.3.value', '5')
            ->where('dashboard.salesData.0.sales', 20_000)
            ->where('dashboard.salesData.0.orders', 1)
            ->where('dashboard.salesData.5.sales', 3_000)
            ->where('dashboard.salesData.5.orders', 1)
            ->where('dashboard.salesData.6.sales', 25_000)
            ->where('dashboard.salesData.6.orders', 1)
            ->where('dashboard.salesData', fn ($days) => collect($days)->pluck('sales')->all() === [20_000, 0, 0, 0, 0, 3_000, 25_000]
                && collect($days)->pluck('orders')->all() === [1, 0, 0, 0, 0, 1, 1])
            ->where('dashboard.orderMixData.0.label', OrderItemStatus::Pending->label())
            ->where('dashboard.orderMixData.0.value', 5)
            ->where('dashboard.orderMixData.1.value', 1)
            ->where('dashboard.orderMixData.2.value', 1)
            ->has('dashboard.orders', 5)
            ->where('dashboard.orders.0.product', 'Buku Tulis')
            ->where('dashboard.topProducts.0.name', 'Pulpen Biru')
            ->where('dashboard.topProducts.0.sold', '7 terjual')
            ->has('dashboard.stockAlerts', 5)
            ->where('dashboard.stockAlerts.0.product', 'Buku Tulis')
            ->where('dashboard.stockAlerts.4.product', 'Penghapus'),
        );
});

test('seller header notifications contain only current seller action items', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $otherSeller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();
    $category = Category::factory()->create();

    $lowStockProduct = Product::factory()->for($seller, 'seller')->for($category)->approved()->create([
        'name' => 'Pulpen Biru',
        'stock' => 2,
    ]);
    $normalProduct = Product::factory()->for($seller, 'seller')->for($category)->approved()->create([
        'stock' => 20,
    ]);
    $otherProduct = Product::factory()->for($otherSeller, 'seller')->for($category)->approved()->create([
        'stock' => 0,
    ]);

    $order = Order::factory()->for($buyer)->create();
    $pendingItem = OrderItem::factory()->for($order)->for($lowStockProduct)->create([
        'product_name' => $lowStockProduct->name,
        'status' => OrderItemStatus::Pending,
    ]);
    OrderItem::factory()->for($order)->for($normalProduct)->create([
        'status' => OrderItemStatus::Sent,
    ]);
    OrderItem::factory()->for($order)->for($otherProduct)->create([
        'status' => OrderItemStatus::Pending,
    ]);

    $this->actingAs($seller)
        ->get(route('seller.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('sellerHeader.notifications', 2)
            ->where('sellerHeader.notifications.0.type', 'order')
            ->where('sellerHeader.notifications.0.href', route('seller.orders.show', $pendingItem, absolute: false))
            ->where('sellerHeader.notifications.1.type', 'stock')
            ->where('sellerHeader.notifications.1.title', 'Pulpen Biru')
            ->where('sellerHeader.notifications.1.href', route('seller.inventory.index', ['q' => 'Pulpen Biru'], absolute: false))
            ->where('sellerHeader.supportEmail', config('mail.from.address')),
        );
});

test('seller header notifications are empty when no action is needed', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);

    $this->actingAs($seller)
        ->get(route('seller.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sellerHeader.notifications', [])
            ->where('sellerHeader.supportEmail', config('mail.from.address')),
        );
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
