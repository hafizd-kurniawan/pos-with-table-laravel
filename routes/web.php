<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\QRCodeController;

// Include debug routes
require __DIR__.'/debug.php';

// ========================================
// SUPER ADMIN ROUTES
// ========================================
Route::prefix('superadmin')->name('superadmin.')->group(function () {
    // Login routes (no auth required)
    Route::get('/login', [\App\Http\Controllers\SuperAdmin\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\SuperAdmin\AuthController::class, 'login']);
    
    // Protected routes (require superadmin middleware)
    Route::middleware('superadmin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\SuperAdmin\DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [\App\Http\Controllers\SuperAdmin\AuthController::class, 'logout'])->name('logout');
        
        // Tenant Management
        Route::get('/tenants', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'index'])->name('tenants.index');
        Route::post('/tenants', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'store'])->name('tenants.store');
        Route::get('/tenants/{tenant}', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'show'])->name('tenants.show');
        Route::put('/tenants/{tenant}', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'update'])->name('tenants.update');
        Route::delete('/tenants/{tenant}', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'destroy'])->name('tenants.destroy');
        
        // Tenant Actions
        Route::post('/tenants/{tenant}/extend-trial', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'extendTrial'])->name('tenants.extend-trial');
        Route::post('/tenants/{tenant}/activate-subscription', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'activateSubscription'])->name('tenants.activate-subscription');
        Route::post('/tenants/{tenant}/suspend', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('/tenants/{tenant}/reactivate', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'reactivate'])->name('tenants.reactivate');
        Route::post('/tenants/{tenant}/reset-password', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'resetPassword'])->name('tenants.reset-password');
        
        // Subscription Plans Management
        Route::get('/plans', [\App\Http\Controllers\SuperAdmin\SubscriptionPlanController::class, 'index'])->name('plans.index');
        Route::post('/plans', [\App\Http\Controllers\SuperAdmin\SubscriptionPlanController::class, 'store'])->name('plans.store');
        Route::put('/plans/{plan}', [\App\Http\Controllers\SuperAdmin\SubscriptionPlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [\App\Http\Controllers\SuperAdmin\SubscriptionPlanController::class, 'destroy'])->name('plans.destroy');
    });
});

// ========================================
// TENANT ADMIN ROUTES
// ========================================
Route::prefix('tenant/admin')->name('tenantadmin.')->group(function () {
    // Login routes (no auth required)
    Route::get('/login', [\App\Http\Controllers\TenantAdmin\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\TenantAdmin\AuthController::class, 'login']);
    
    // Protected routes (require tenantadmin middleware)
    Route::middleware('tenantadmin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\TenantAdmin\DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [\App\Http\Controllers\TenantAdmin\AuthController::class, 'logout'])->name('logout');
        
        // Settings
        Route::get('/settings', [\App\Http\Controllers\TenantAdmin\SettingsController::class, 'index'])->name('settings');
        Route::post('/settings/midtrans', [\App\Http\Controllers\TenantAdmin\SettingsController::class, 'updateMidtrans'])->name('settings.midtrans');
        Route::post('/settings/n8n', [\App\Http\Controllers\TenantAdmin\SettingsController::class, 'updateN8n'])->name('settings.n8n');
        Route::post('/settings/firebase', [\App\Http\Controllers\TenantAdmin\SettingsController::class, 'updateFirebase'])->name('settings.firebase');
        Route::get('/settings/delete/{type}', [\App\Http\Controllers\TenantAdmin\SettingsController::class, 'deleteConfig'])->name('settings.delete');
        
        // Expired Page
        Route::get('/expired', [\App\Http\Controllers\TenantAdmin\DashboardController::class, 'expired'])->name('expired');
    });
});

Route::get('/', function () {
    // Show navigation page
    return view('navigation');
})->name('home');

// Self-Order Routes (Public - with UUID for security)
Route::get('/order/{tenantIdentifier}/{tablenumber}', [OrderController::class, 'index'])->name('order.menu');
Route::post('/order/{tenantIdentifier}/{tablenumber}/add', [OrderController::class, 'addToCart'])->name('order.addToCart');
Route::get('/order/{tenantIdentifier}/{tablenumber}/cart', [OrderController::class, 'cart'])->name('order.cart');
Route::delete('order/{tenantIdentifier}/{tablenumber}/cart/remove/{productId}', [OrderController::class, 'removeCart'])->name('order.removeCart');
Route::get('/order/{tenantIdentifier}/{tablenumber}/checkout', [OrderController::class, 'checkoutForm'])->name('order.checkoutForm');
Route::middleware('throttle:10,1')->post('/order/{tenantIdentifier}/{tablenumber}/checkout', [OrderController::class, 'checkout'])->name('order.checkout'); // 10 requests per minute
Route::get('/order/{tenantIdentifier}/{tablenumber}/qris/{code}', [OrderController::class, 'qris'])->name('order.qris');
Route::post('/order/{tenantIdentifier}/{tablenumber}/qris/{code}/confirm', [OrderController::class, 'qrisConfirm'])->name('order.qris.confirm');
Route::get('/order/{tenantIdentifier}/{tablenumber}/qris/{code}/check-status', [OrderController::class, 'checkPaymentStatus'])->name('order.qris.check-status');

// DEBUG route untuk testing (development only)
Route::post('/debug/order/{tenantIdentifier}/{tablenumber}/qris/{code}/force-success', [OrderController::class, 'forcePaymentSuccess'])->name('debug.order.force-success');

// Manual release expired orders (untuk testing)
Route::get('/admin/orders/release-expired', [\App\Http\Controllers\OrderManagementController::class, 'releaseExpiredOrders'])->name('admin.orders.release-expired');

// API untuk check order status
Route::get('/api/order/{code}/status', [\App\Http\Controllers\OrderManagementController::class, 'checkOrderStatus'])->name('api.order.status');

Route::get('/order/{tenantIdentifier}/{tablenumber}/success/{code}', [OrderController::class, 'success'])->name('order.success');
Route::post('/midtrans/callback', [OrderController::class, 'midtransCallback']);
Route::get('/table/{table}/product/{product}', [OrderController::class, 'detail'])
    ->name('order.detail');
Route::post('/table/{table}/product/{productId}/add', [OrderController::class, 'addToCartWithNote'])->name('order.addToCartWithNote');

// AJAX Add to Cart Route (with session support)
Route::post('/ajax/order/{tablenumber}/add-cart', [OrderController::class, 'addToCartAjax'])->name('order.addToCartAjax');

// QR Code routes
Route::get('/table/{table}/print-qr', [QRCodeController::class, 'printTableQR'])->name('table.print-qr');
Route::get('/table/{table}/download-qr', [QRCodeController::class, 'downloadTableQR'])->name('table.download-qr');

// Table Management Web Routes (Simple)
Route::prefix('table-management')->name('table-management.')->group(function () {
    Route::get('/', [App\Http\Controllers\Web\TableController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Web\TableController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Web\TableController::class, 'store'])->name('store');
    Route::get('/{table}', [App\Http\Controllers\Web\TableController::class, 'show'])->name('show');
    Route::get('/{table}/edit', [App\Http\Controllers\Web\TableController::class, 'edit'])->name('edit');
    Route::put('/{table}', [App\Http\Controllers\Web\TableController::class, 'update'])->name('update');
    Route::delete('/{table}', [App\Http\Controllers\Web\TableController::class, 'destroy'])->name('destroy');
});

// Table Category Management Web Routes
Route::prefix('table-categories')->name('table-categories.')->group(function () {
    Route::get('/', [App\Http\Controllers\Web\TableCategoryController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Web\TableCategoryController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Web\TableCategoryController::class, 'store'])->name('store');
    Route::get('/{tableCategory}', [App\Http\Controllers\Web\TableCategoryController::class, 'show'])->name('show');
    Route::get('/{tableCategory}/edit', [App\Http\Controllers\Web\TableCategoryController::class, 'edit'])->name('edit');
    Route::put('/{tableCategory}', [App\Http\Controllers\Web\TableCategoryController::class, 'update'])->name('update');
    Route::delete('/{tableCategory}', [App\Http\Controllers\Web\TableCategoryController::class, 'destroy'])->name('destroy');
});

// Order Settings Management (Discount, Tax, Service Charge)
Route::prefix('order-settings')->name('order-settings.')->group(function () {
    Route::get('/', [App\Http\Controllers\Web\OrderSettingController::class, 'index'])->name('index');
    Route::put('/update', [App\Http\Controllers\Web\OrderSettingController::class, 'update'])->name('update');
});
