<?php

use App\Enums\UserRole;
use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminJurusanConsignmentController;
use App\Http\Controllers\AdminJurusanDashboardController;
use App\Http\Controllers\AdminJurusanReportController;
use App\Http\Controllers\AdminJurusanUpJurusanController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AdminProductModerationController;
use App\Http\Controllers\AdminSellerApplicationController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\BuyerCatalogController;
use App\Http\Controllers\BuyerOrderController;
use App\Http\Controllers\BuyerProductDetailController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\NotificationDismissalController;
use App\Http\Controllers\PicketUpJurusanConsignmentController;
use App\Http\Controllers\SellerApplicationController;
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
use Inertia\Inertia;

Route::get('/', BuyerCatalogController::class)->name('home');

Route::get('catalog', BuyerCatalogController::class)->name('catalog.index');
Route::get('catalog/{product:slug}', BuyerProductDetailController::class)->name('catalog.show');

Route::middleware('auth')->delete('notifications', [NotificationDismissalController::class, 'destroy'])->name('notifications.destroy');

Route::middleware(['auth', EnsureUserIsBuyer::class])->group(function () {
    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart/items/{product:slug}', [CartController::class, 'store'])->name('cart.items.store');
    Route::put('cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
    Route::delete('cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');

    Route::get('checkout/confirm', [CheckoutController::class, 'confirm'])->name('checkout.confirm');
    Route::post('checkout', CheckoutController::class)->name('checkout');

    Route::get('orders', [BuyerOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [BuyerOrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/complete', [BuyerOrderController::class, 'complete'])->name('orders.complete');
    Route::post('orders/{order}/cancel', [BuyerOrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('seller-application', [SellerApplicationController::class, 'index'])->name('seller-application.index');
    Route::post('seller-application', [SellerApplicationController::class, 'store'])->name('seller-application.store');
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
    Route::get('admin/users/create-admin-jurusan', [AdminUserController::class, 'createAdminJurusan'])->name('admin.users.create-admin-jurusan');
    Route::post('admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::get('admin/seller-applications', [AdminSellerApplicationController::class, 'index'])->name('admin.seller-applications.index');
    Route::post('admin/seller-applications/{application}/approve', [SellerApplicationController::class, 'approve'])->name('admin.seller-applications.approve');
    Route::post('admin/seller-applications/{application}/reject', [SellerApplicationController::class, 'reject'])->name('admin.seller-applications.reject');
    Route::get('admin/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::post('admin/orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('admin.orders.cancel');
    Route::post('admin/orders/{order}/force-complete', [AdminOrderController::class, 'forceComplete'])->name('admin.orders.force-complete');
    Route::post('admin/orders/{order}/mark-review', [AdminOrderController::class, 'markReview'])->name('admin.orders.mark-review');
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
        Route::get('orders/offline/{movement}', [SellerOrderController::class, 'showOffline'])->name('orders.offline.show');
        Route::get('orders/{orderItem}', [SellerOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{orderItem}/payment/approve', [SellerOrderController::class, 'approvePayment'])->name('orders.payment.approve');
        Route::post('orders/{orderItem}/payment/reject', [SellerOrderController::class, 'rejectPayment'])->name('orders.payment.reject');
        Route::put('orders/{orderItem}/status', [SellerOrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::post('orders/{orderItem}/cancel', [SellerOrderController::class, 'cancel'])->name('orders.cancel');

        Route::get('consignments', [SellerConsignmentController::class, 'index'])->name('consignments.index');
    });

Route::middleware(['auth', EnsureUserIsAdminJurusan::class])
    ->prefix('admin-jurusan')
    ->name('admin-jurusan.')
    ->group(function () {
        Route::get('dashboard', AdminJurusanDashboardController::class)->name('dashboard');
        Route::get('up-jurusan', [AdminJurusanUpJurusanController::class, 'index'])->name('up-jurusan.index');
        Route::post('up-jurusan', [AdminJurusanUpJurusanController::class, 'store'])->name('up-jurusan.store');
        Route::delete('up-jurusan/{upJurusan}', [AdminJurusanUpJurusanController::class, 'destroy'])->name('up-jurusan.destroy');
        Route::get('picket-officer/create', [AdminJurusanUpJurusanController::class, 'createPicket'])->name('picket-officer.create');
        Route::post('up-jurusan/{upJurusan}/assign-picket', [AdminJurusanUpJurusanController::class, 'assignPicket'])->name('up-jurusan.assign-picket');
        Route::post('up-jurusan/{upJurusan}/unassign-picket', [AdminJurusanUpJurusanController::class, 'unassignPicket'])->name('up-jurusan.unassign-picket');
        Route::post('up-jurusan/{upJurusan}/pickets', [AdminJurusanUpJurusanController::class, 'storePicket'])->name('up-jurusan.pickets.store');
        Route::post('products', [AdminJurusanUpJurusanController::class, 'storeProduct'])->name('products.store');
        Route::get('consignments', [AdminJurusanConsignmentController::class, 'index'])->name('consignments.index');
        Route::get('consignments/{consignment}', [AdminJurusanConsignmentController::class, 'show'])->name('consignments.show');
        Route::get('reports', [AdminJurusanReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [AdminJurusanReportController::class, 'show'])->name('reports.show');
        Route::post('consignments/{consignment}/approve', [AdminJurusanConsignmentController::class, 'approve'])->name('consignments.approve');
        Route::post('consignments/{consignment}/reject', [AdminJurusanConsignmentController::class, 'reject'])->name('consignments.reject');
        Route::post('consignments/{consignment}/cancel', [AdminJurusanConsignmentController::class, 'cancel'])->name('consignments.cancel');
        Route::post('consignments/{consignment}/payout', [AdminJurusanConsignmentController::class, 'payout'])->name('consignments.payout');
    });

Route::middleware('auth')
    ->get('picket/unassigned', function () {
        $user = request()->user();

        abort_unless($user?->role === UserRole::PicketOfficer, 403);

        if ($user->up_jurusan_id !== null) {
            return to_route('picket.dashboard');
        }

        return Inertia::render('picket/unassigned');
    })
    ->name('picket.unassigned');

Route::middleware(['auth', EnsureUserIsPicketOfficer::class])
    ->prefix('picket')
    ->name('picket.')
    ->group(function () {
        Route::get('dashboard', [PicketUpJurusanConsignmentController::class, 'dashboard'])->name('dashboard');
        Route::get('receiving', [PicketUpJurusanConsignmentController::class, 'receiving'])->name('receiving');
        Route::get('pos', [PicketUpJurusanConsignmentController::class, 'pos'])->name('pos');
        Route::get('pos/sales/{sale}/receipt', [PicketUpJurusanConsignmentController::class, 'receipt'])->name('pos.receipt');
        Route::get('orders', [PicketUpJurusanConsignmentController::class, 'orders'])->name('orders');
        Route::get('reports', [PicketUpJurusanConsignmentController::class, 'reports'])->name('reports');
        Route::post('orders/{orderItem}/payment/approve', [PicketUpJurusanConsignmentController::class, 'approveOrderPayment'])->name('orders.payment.approve');
        Route::post('orders/{orderItem}/payment/reject', [PicketUpJurusanConsignmentController::class, 'rejectOrderPayment'])->name('orders.payment.reject');
        Route::put('orders/{orderItem}/status', [PicketUpJurusanConsignmentController::class, 'updateOrderStatus'])->name('orders.update-status');
        Route::post('orders/{orderItem}/cancel', [PicketUpJurusanConsignmentController::class, 'cancelOrderItem'])->name('orders.cancel');
        Route::get('up-jurusan/consignments', [PicketUpJurusanConsignmentController::class, 'index'])->name('up-jurusan.consignments.index');
        Route::post('up-jurusan/consignments/{consignment}/receive', [PicketUpJurusanConsignmentController::class, 'receive'])->name('up-jurusan.consignments.receive');
        Route::post('up-jurusan/consignments/{consignment}/release', [PicketUpJurusanConsignmentController::class, 'release'])->name('up-jurusan.consignments.release');
        Route::post('up-jurusan/sales', [PicketUpJurusanConsignmentController::class, 'storeSale'])->name('up-jurusan.sales.store');
        Route::post('up-jurusan/report', [PicketUpJurusanConsignmentController::class, 'storeReport'])->name('up-jurusan.report.store');
    });

require __DIR__.'/settings.php';
