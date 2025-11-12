<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\QRCodeController;

Route::get('/', function () {
    // Show navigation page
    return view('navigation');
})->name('home');

Route::get('/order/{tablenumber}', [OrderController::class, 'index'])->name('order.menu');
Route::post('/order/{tablenumber}/add', [OrderController::class, 'addToCart'])->name('order.addToCart');
Route::get('/order/{tablenumber}/cart', [OrderController::class, 'cart'])->name('order.cart');
// Route::post('/order/{tablenumber}/remove/{index}', [OrderController::class, 'removeCart'])->name('order.removeCart');
Route::delete('order/{tablenumber}/cart/remove/{productId}', [OrderController::class, 'removeCart'])->name('order.removeCart');
Route::get('/order/{tablenumber}/checkout', [OrderController::class, 'checkoutForm'])->name('order.checkoutForm');
Route::post('/order/{tablenumber}/checkout', [OrderController::class, 'checkout'])->name('order.checkout');
Route::get('/order/{tablenumber}/qris/{code}', [OrderController::class, 'qris'])->name('order.qris');
Route::post('/order/{tablenumber}/qris/{code}/confirm', [OrderController::class, 'qrisConfirm'])->name('order.qris.confirm');
Route::get('/order/{tablenumber}/qris/{code}/check-status', [OrderController::class, 'checkPaymentStatus'])->name('order.qris.check-status');

// DEBUG route untuk testing (development only)
Route::post('/debug/order/{tablenumber}/qris/{code}/force-success', [OrderController::class, 'forcePaymentSuccess'])->name('debug.order.force-success');

// Manual release expired orders (untuk testing)
Route::get('/admin/orders/release-expired', [\App\Http\Controllers\OrderManagementController::class, 'releaseExpiredOrders'])->name('admin.orders.release-expired');

// API untuk check order status
Route::get('/api/order/{code}/status', [\App\Http\Controllers\OrderManagementController::class, 'checkOrderStatus'])->name('api.order.status');

Route::get('/order/{tablenumber}/success/{code}', [OrderController::class, 'success'])->name('order.success');
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
