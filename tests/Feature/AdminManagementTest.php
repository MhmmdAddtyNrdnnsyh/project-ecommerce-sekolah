<?php

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can list and filter products', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $seller = User::factory()->create(['role' => UserRole::Seller, 'name' => 'Seller ATK']);
    $category = Category::factory()->create(['name' => 'Alat Tulis']);

    Product::factory()->for($seller, 'seller')->for($category)->approved()->create([
        'name' => 'Pulpen Biru',
        'slug' => 'pulpen-biru',
        'stock' => 8,
    ]);
    Product::factory()->for($seller, 'seller')->for($category)->create([
        'name' => 'Buku Pending',
        'status' => ProductStatus::Pending,
        'stock' => 5,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.products.index', [
            'q' => 'pulpen',
            'status' => ProductStatus::Approved->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/products/index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Pulpen Biru')
            ->where('products.data.0.seller.name', 'Seller ATK')
            ->where('products.data.0.category.name', 'Alat Tulis')
            ->where('filters.q', 'pulpen')
            ->where('filters.status', ProductStatus::Approved->value)
            ->has('statuses', 4),
        );
});

test('admin can manage categories', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = Category::factory()->create(['name' => 'Lama', 'slug' => 'lama']);

    $this->actingAs($admin);

    $this->get(route('admin.categories.index', ['q' => 'lama']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/categories/index')
            ->has('categories.data', 1)
            ->where('categories.data.0.name', 'Lama')
            ->where('filters.q', 'lama'),
        );

    $this->from(route('admin.categories.index'))
        ->post(route('admin.categories.store'), ['name' => 'Seragam Sekolah'])
        ->assertRedirect(route('admin.categories.index'));

    $this->assertDatabaseHas('categories', [
        'name' => 'Seragam Sekolah',
        'slug' => 'seragam-sekolah',
    ]);

    $this->from(route('admin.categories.index'))
        ->put(route('admin.categories.update', $category), ['name' => 'Alat Kelas'])
        ->assertRedirect(route('admin.categories.index'));

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Alat Kelas',
        'slug' => 'alat-kelas',
    ]);

    $this->from(route('admin.categories.index'))
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.categories.index'));

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

test('admin cannot delete category that has products', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = Category::factory()->create();
    Product::factory()->for($category)->create();

    $this->actingAs($admin)
        ->from(route('admin.categories.index'))
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.categories.index'))
        ->assertSessionHasErrors('category');

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

test('admin can list and filter users', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    User::factory()->create(['name' => 'Budi Seller', 'role' => UserRole::Seller]);
    User::factory()->create(['name' => 'Ani Buyer', 'role' => UserRole::Buyer]);

    $this->actingAs($admin)
        ->get(route('admin.users.index', [
            'q' => 'budi',
            'role' => UserRole::Seller->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/index')
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Budi Seller')
            ->where('users.data.0.role.code', UserRole::Seller->value)
            ->where('filters.q', 'budi')
            ->where('filters.role', UserRole::Seller->value)
            ->has('roles', 5),
        );
});

test('admin can create admin jurusan account', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.users.create-admin-jurusan'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/create-admin-jurusan'),
        );

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Admin RPL',
            'email' => 'admin-rpl@example.com',
            'role' => UserRole::AdminJurusan->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('admin.users.create-admin-jurusan'));

    $this->assertDatabaseHas('users', [
        'name' => 'Admin RPL',
        'email' => 'admin-rpl@example.com',
        'role' => UserRole::AdminJurusan->value,
    ]);
});

test('admin cannot create privileged account other than admin jurusan from users page', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->from(route('admin.users.create-admin-jurusan'))
        ->post(route('admin.users.store'), [
            'name' => 'Super Admin Baru',
            'email' => 'super-admin@example.com',
            'role' => UserRole::Admin->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('admin.users.create-admin-jurusan'))
        ->assertSessionHasErrors('role');

    $this->assertDatabaseMissing('users', [
        'email' => 'super-admin@example.com',
    ]);
});

test('admin can list and filter orders', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $buyer = User::factory()->create(['name' => 'Pembeli Utama']);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $product = Product::factory()->for($seller, 'seller')->approved()->create(['name' => 'Pulpen Biru']);
    $order = Order::factory()->for($buyer)->create([
        'status' => OrderStatus::Pending,
        'total_price' => 20_000,
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'product_name' => 'Pulpen Biru',
        'quantity' => 2,
        'subtotal' => 20_000,
        'status' => OrderItemStatus::Packed,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['q' => (string) $order->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/index')
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $order->id)
            ->where('orders.data.0.buyer.name', 'Pembeli Utama')
            ->where('orders.data.0.total_price', 20_000)
            ->where('orders.data.0.items.0.product_name', 'Pulpen Biru')
            ->where('filters.q', (string) $order->id),
        );
});

test('admin can manually approve cash payment', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Unpaid,
        'payment_confirmed_at' => null,
        'payment_confirmed_by' => null,
    ]);
    OrderItem::factory()->for($order)->create();

    $this->actingAs($admin)
        ->from(route('admin.orders.index'))
        ->post(route('admin.orders.payment.approve', $order))
        ->assertRedirect(route('admin.orders.index'))
        ->assertSessionHas('success', "Pembayaran order {$order->code} berhasil di-override lunas.");

    $order->refresh();

    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->payment_confirmed_by)->toBe($admin->id)
        ->and($order->payment_confirmed_at)->not->toBeNull()
        ->and($order->payment_rejection_reason)->toBeNull();
    expect($order->items()->first()?->payment_status)->toBe(PaymentStatus::Paid);
});

test('admin can reject unpaid cash payment with a reason', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    OrderItem::factory()->for($order)->create();

    $this->actingAs($admin)
        ->from(route('admin.orders.index'))
        ->post(route('admin.orders.payment.reject', $order), [
            'payment_rejection_reason' => 'Uang tunai belum diterima admin.',
        ])
        ->assertRedirect(route('admin.orders.index'))
        ->assertSessionHas('success', "Pembayaran order {$order->code} ditolak.");

    $order->refresh();

    expect($order->payment_status)->toBe(PaymentStatus::Rejected)
        ->and($order->payment_rejection_reason)->toBe('Uang tunai belum diterima admin.')
        ->and($order->payment_confirmed_at)->toBeNull()
        ->and($order->payment_confirmed_by)->toBeNull();
    expect($order->items()->first()?->payment_status)->toBe(PaymentStatus::Rejected);
});

test('admin payment rejection requires a reason', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.orders.index'))
        ->post(route('admin.orders.payment.reject', $order), [
            'payment_rejection_reason' => '',
        ])
        ->assertRedirect(route('admin.orders.index'))
        ->assertSessionHasErrors('payment_rejection_reason');

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('non admin users cannot access admin management endpoints', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $category = Category::factory()->create();

    $this->actingAs($user);

    $this->get(route('admin.products.index'))->assertForbidden();
    $this->get(route('admin.categories.index'))->assertForbidden();
    $this->post(route('admin.categories.store'), ['name' => 'Baru'])->assertForbidden();
    $this->put(route('admin.categories.update', $category), ['name' => 'Edit'])->assertForbidden();
    $this->delete(route('admin.categories.destroy', $category))->assertForbidden();
    $this->get(route('admin.users.index'))->assertForbidden();
    $this->get(route('admin.users.create-admin-jurusan'))->assertForbidden();
    $this->post(route('admin.users.store'), [
        'name' => 'Admin RPL',
        'email' => 'admin-rpl@example.com',
        'role' => UserRole::AdminJurusan->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertForbidden();
    $this->get(route('admin.orders.index'))->assertForbidden();
})->with([
    UserRole::Buyer,
    UserRole::Seller,
    UserRole::AdminJurusan,
    UserRole::PicketOfficer,
]);
