<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('seller can delete their own product without order history', function () {
    Storage::fake('public');

    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create([
            'image' => 'products/test-file.txt',
        ]);

    Storage::disk('public')->put('products/test-file.txt', 'fake content');

    $this->actingAs($seller);

    $response = $this->delete(route('seller.products.destroy', $product));

    $response->assertRedirect(route('seller.products.index'));
    $response->assertSessionHas('success', 'Produk berhasil dihapus.');

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
    Storage::disk('public')->assertMissing('products/test-file.txt');
});

test('seller cannot delete another sellers product', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $otherSeller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $product = Product::factory()
        ->for($otherSeller, 'seller')
        ->for($category)
        ->create();

    $this->actingAs($seller);

    $this->delete(route('seller.products.destroy', $product))->assertForbidden();

    $this->assertDatabaseHas('products', ['id' => $product->id]);
});

test('seller cannot delete a product that has order items', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();
    $category = Category::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create(['stock' => 10]);

    $order = Order::factory()->create(['user_id' => $buyer->id]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'price' => $product->price,
        'quantity' => 1,
        'subtotal' => $product->price,
    ]);

    $this->actingAs($seller);

    $response = $this->delete(route('seller.products.destroy', $product));

    $response->assertRedirect(route('seller.products.index'));
    $response->assertSessionHasErrors([
        'product' => 'Produk tidak dapat dihapus karena sudah memiliki riwayat pesanan.',
    ]);

    $this->assertDatabaseHas('products', ['id' => $product->id]);
});

test('non seller users cannot delete products', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $category = Category::factory()->create();
    $product = Product::factory()
        ->for($seller, 'seller')
        ->for($category)
        ->create();

    $this->actingAs($user);

    $this->delete(route('seller.products.destroy', $product))->assertForbidden();
})->with([
    UserRole::Admin,
    UserRole::Buyer,
    UserRole::PicketOfficer,
]);
