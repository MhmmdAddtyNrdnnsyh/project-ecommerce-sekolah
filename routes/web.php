<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminProductModerationController;
use App\Http\Controllers\BuyerCatalogController;
use App\Http\Controllers\BuyerProductDetailController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\SellerDashboardController;
use App\Http\Controllers\SellerProductController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsSeller;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('catalog', BuyerCatalogController::class)->name('catalog.index');
Route::get('catalog/{product:slug}', BuyerProductDetailController::class)->name('catalog.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart/items/{product:slug}', [CartController::class, 'store'])->name('cart.items.store');
    Route::put('cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
    Route::delete('cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');

    Route::post('checkout', CheckoutController::class)->name('checkout');
});

Route::middleware(['auth', 'verified', EnsureUserIsAdmin::class])->group(function () {
    Route::get('dashboard', AdminDashboardController::class)->name('dashboard');
    Route::get('admin/products/moderation', [AdminProductModerationController::class, 'index'])->name('admin.products.moderation.index');
    Route::post('admin/products/{product}/approve', [AdminProductModerationController::class, 'approve'])->name('admin.products.moderation.approve');
    Route::post('admin/products/{product}/reject', [AdminProductModerationController::class, 'reject'])->name('admin.products.moderation.reject');
});

Route::middleware(['auth', 'verified', EnsureUserIsSeller::class])
    ->prefix('seller')
    ->name('seller.')
    ->group(function () {
        Route::get('dashboard', SellerDashboardController::class)->name('dashboard');
        Route::get('products', [SellerProductController::class, 'index'])->name('products.index');
        Route::get('products/create', [SellerProductController::class, 'create'])->name('products.create');
        Route::post('products', [SellerProductController::class, 'store'])->name('products.store');
        Route::get('products/{product}/edit', [SellerProductController::class, 'edit'])->name('products.edit');
        Route::put('products/{product}', [SellerProductController::class, 'update'])->name('products.update');
    });

require __DIR__.'/settings.php';
