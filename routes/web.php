<?php

use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminJurusanConsignmentController;
use App\Http\Controllers\AdminJurusanDashboardController;
use App\Http\Controllers\AdminJurusanReportController;
use App\Http\Controllers\AdminJurusanUpJurusanController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AdminProductModerationController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\BuyerCatalogController;
use App\Http\Controllers\BuyerOrderController;
use App\Http\Controllers\BuyerProductDetailController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PicketUpJurusanConsignmentController;
use App\Http\Controllers\SellerConsignmentController;
use App\Http\Controllers\SellerDashboardController;
use App\Http\Controllers\SellerInventoryController;
use App\Http\Controllers\SellerOrderController;
use App\Http\Controllers\SellerProductController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsAdminJurusan;
use App\Http\Middleware\EnsureUserIsBuyer;
use App\Http\Middleware\EnsureUserIsPicketOfficer;
use App\Http\Middleware\EnsureUserIsSeller;
use Illuminate\Support\Facades\Route;

Route::get('/', BuyerCatalogController::class)->name('home');

Route::get('catalog', BuyerCatalogController::class)->name('catalog.index');
Route::get('catalog/{product:slug}', BuyerProductDetailController::class)->name('catalog.show');

Route::middleware(['auth', EnsureUserIsBuyer::class])->group(function () {
    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart/items/{product:slug}', [CartController::class, 'store'])->name('cart.items.store');
    Route::put('cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
    Route::delete('cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');

    Route::get('checkout/confirm', [CheckoutController::class, 'confirm'])->name('checkout.confirm');
    Route::post('checkout', CheckoutController::class)->name('checkout');

    Route::get('orders', [BuyerOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [BuyerOrderController::class, 'show'])->name('orders.show');
});

Route::middleware(['auth', EnsureUserIsAdmin::class])->group(function () {
    Route::get('dashboard', AdminDashboardController::class)->name('dashboard');
    Route::get('admin/products', [AdminProductController::class, 'index'])->name('admin.products.index');
    Route::get('admin/products/moderation', [AdminProductModerationController::class, 'index'])->name('admin.products.moderation.index');
    Route::post('admin/products/{product}/approve', [AdminProductModerationController::class, 'approve'])->name('admin.products.moderation.approve');
    Route::post('admin/products/{product}/reject', [AdminProductModerationController::class, 'reject'])->name('admin.products.moderation.reject');
    Route::get('admin/categories', [AdminCategoryController::class, 'index'])->name('admin.categories.index');
    Route::post('admin/categories', [AdminCategoryController::class, 'store'])->name('admin.categories.store');
    Route::put('admin/categories/{category}', [AdminCategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('admin/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('admin.categories.destroy');
    Route::get('admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('admin/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
});

Route::middleware(['auth', EnsureUserIsSeller::class])
    ->prefix('seller')
    ->name('seller.')
    ->group(function () {
        Route::get('dashboard', SellerDashboardController::class)->name('dashboard');
        Route::get('products', [SellerProductController::class, 'index'])->name('products.index');
        Route::get('products/create', [SellerProductController::class, 'create'])->name('products.create');
        Route::post('products', [SellerProductController::class, 'store'])->name('products.store');
        Route::get('products/{product}/edit', [SellerProductController::class, 'edit'])->name('products.edit');
        Route::put('products/{product}', [SellerProductController::class, 'update'])->name('products.update');
        Route::delete('products/{product}', [SellerProductController::class, 'destroy'])->name('products.destroy');

        Route::get('inventory', [SellerInventoryController::class, 'index'])->name('inventory.index');
        Route::patch('inventory/{product}', [SellerInventoryController::class, 'update'])->name('inventory.update');

        Route::get('orders', [SellerOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{orderItem}', [SellerOrderController::class, 'show'])->name('orders.show');
        Route::put('orders/{orderItem}/status', [SellerOrderController::class, 'updateStatus'])->name('orders.update-status');

        Route::get('consignments', [SellerConsignmentController::class, 'index'])->name('consignments.index');
    });

Route::middleware(['auth', EnsureUserIsAdminJurusan::class])
    ->prefix('admin-jurusan')
    ->name('admin-jurusan.')
    ->group(function () {
        Route::get('dashboard', AdminJurusanDashboardController::class)->name('dashboard');
        Route::get('up-jurusan', [AdminJurusanUpJurusanController::class, 'index'])->name('up-jurusan.index');
        Route::post('up-jurusan', [AdminJurusanUpJurusanController::class, 'store'])->name('up-jurusan.store');
        Route::post('up-jurusan/{upJurusan}/assign-picket', [AdminJurusanUpJurusanController::class, 'assignPicket'])->name('up-jurusan.assign-picket');
        Route::post('products', [AdminJurusanUpJurusanController::class, 'storeProduct'])->name('products.store');
        Route::get('consignments', [AdminJurusanConsignmentController::class, 'index'])->name('consignments.index');
        Route::get('consignments/{consignment}', [AdminJurusanConsignmentController::class, 'show'])->name('consignments.show');
        Route::get('reports', [AdminJurusanReportController::class, 'index'])->name('reports.index');
        Route::post('consignments/{consignment}/approve', [AdminJurusanConsignmentController::class, 'approve'])->name('consignments.approve');
        Route::post('consignments/{consignment}/reject', [AdminJurusanConsignmentController::class, 'reject'])->name('consignments.reject');
        Route::post('consignments/{consignment}/receive', [AdminJurusanConsignmentController::class, 'receive'])->name('consignments.receive');
        Route::post('consignments/{consignment}/payout', [AdminJurusanConsignmentController::class, 'payout'])->name('consignments.payout');
    });

Route::middleware(['auth', EnsureUserIsPicketOfficer::class])
    ->prefix('picket')
    ->name('picket.')
    ->group(function () {
        Route::get('dashboard', [PicketUpJurusanConsignmentController::class, 'dashboard'])->name('dashboard');
        Route::get('pos', [PicketUpJurusanConsignmentController::class, 'pos'])->name('pos');
        Route::get('orders', [PicketUpJurusanConsignmentController::class, 'orders'])->name('orders');
        Route::get('reports', [PicketUpJurusanConsignmentController::class, 'reports'])->name('reports');
        Route::put('orders/{orderItem}/status', [PicketUpJurusanConsignmentController::class, 'updateOrderStatus'])->name('orders.update-status');
        Route::get('up-jurusan/consignments', [PicketUpJurusanConsignmentController::class, 'index'])->name('up-jurusan.consignments.index');
        Route::post('up-jurusan/consignments/{consignment}/receive', [PicketUpJurusanConsignmentController::class, 'receive'])->name('up-jurusan.consignments.receive');
        Route::post('up-jurusan/consignments/{consignment}/release', [PicketUpJurusanConsignmentController::class, 'release'])->name('up-jurusan.consignments.release');
        Route::post('up-jurusan/sales', [PicketUpJurusanConsignmentController::class, 'storeSale'])->name('up-jurusan.sales.store');
        Route::post('up-jurusan/report', [PicketUpJurusanConsignmentController::class, 'storeReport'])->name('up-jurusan.report.store');
    });

require __DIR__.'/settings.php';
