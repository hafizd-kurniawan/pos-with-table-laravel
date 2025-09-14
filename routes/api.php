<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;

// ========================================
// HEALTH CHECK ENDPOINTS (Public)
// ========================================
Route::get('/health', [\App\Http\Controllers\Api\HealthController::class, 'check']);
Route::get('/ping', [\App\Http\Controllers\Api\HealthController::class, 'ping']);

// ========================================
// AUTHENTICATION ENDPOINTS
// ========================================
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ========================================
// PUBLIC ENDPOINTS
// ========================================
Route::post('/midtrans/prod/callback', [OrderController::class, 'midtransCallback']);
Route::get('images/{filename}', [App\Http\Controllers\ImageController::class, 'show']);
Route::get('/settings', [\App\Http\Controllers\Api\SettingController::class, 'getValue']);

// QRIS Order endpoints for Flutter (Public)
Route::post('order/create-qris', [OrderController::class, 'createQrisOrder']);
Route::get('order/{orderCode}/status', [OrderController::class, 'checkOrderStatus']);

// ========================================
// AUTHENTICATED ENDPOINTS
// ========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // CATEGORIES
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);

    // PRODUCTS  
    Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::get('/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'show']);
    Route::post('/products/check-stock', [\App\Http\Controllers\Api\ProductController::class, 'checkStock']);

    // DISCOUNTS (Migrated from laravel-old)
    Route::apiResource('/discounts', \App\Http\Controllers\Api\DiscountController::class);
    Route::get('/discounts-active', [\App\Http\Controllers\Api\DiscountController::class, 'active']);

    // STOCK MANAGEMENT
    Route::post('/stock/check', [\App\Http\Controllers\Api\StockController::class, 'checkStock']);
    Route::post('/stock/validate-order', [\App\Http\Controllers\Api\StockController::class, 'validateOrder']);
    Route::get('/stock/product/{productId}', [\App\Http\Controllers\Api\StockController::class, 'getProductStock']);
    Route::post('/stock/update', [\App\Http\Controllers\Api\StockController::class, 'updateStock']);

    // STOCK TRANSACTIONS (Flutter Sync)
    Route::get('/stock-transactions', [\App\Http\Controllers\Api\StockTransactionController::class, 'index']);
    Route::post('/stock-transactions', [\App\Http\Controllers\Api\StockTransactionController::class, 'store']);
    Route::post('/stock-transactions/batch', [\App\Http\Controllers\Api\StockTransactionController::class, 'batch']);
    Route::get('/stock-levels', [\App\Http\Controllers\Api\StockTransactionController::class, 'stockLevels']);

    // TABLE MANAGEMENT (Enhanced)
    Route::apiResource('/tables', \App\Http\Controllers\Api\TableController::class)->names([
        'index' => 'api.tables.index',
        'store' => 'api.tables.store', 
        'show' => 'api.tables.show',
        'update' => 'api.tables.update',
        'destroy' => 'api.tables.destroy'
    ]);
    Route::put('/tables/{table}/position', [\App\Http\Controllers\Api\TableController::class, 'updatePosition']);
    Route::put('/tables/{table}/status', [\App\Http\Controllers\Api\TableController::class, 'updateStatus']);
    Route::get('/tables-available', [\App\Http\Controllers\Api\TableController::class, 'available']);
    Route::get('/tables-by-category', [\App\Http\Controllers\Api\TableController::class, 'byCategory']);
    Route::get('/table-categories', [\App\Http\Controllers\Api\TableController::class, 'categories']);

    // RESERVATIONS (New Feature)
    Route::apiResource('/reservations', \App\Http\Controllers\Api\ReservationController::class);
    Route::put('/reservations/{reservation}/confirm', [\App\Http\Controllers\Api\ReservationController::class, 'confirm']);
    Route::put('/reservations/{reservation}/cancel', [\App\Http\Controllers\Api\ReservationController::class, 'cancel']);
    Route::get('/reservations-today', [\App\Http\Controllers\Api\ReservationController::class, 'today']);
    Route::post('/reservations/check-availability', [\App\Http\Controllers\Api\ReservationController::class, 'checkAvailability']);

    // CART MANAGEMENT
    Route::post('/cart/validate', [\App\Http\Controllers\Api\CartController::class, 'validateCart']);
    Route::post('/cart/stock', [\App\Http\Controllers\Api\CartController::class, 'getCartStock']);
    Route::post('/cart/pre-checkout', [\App\Http\Controllers\Api\CartController::class, 'preCheckout']);

    // ORDERS
    Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::post('/orders', [\App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::get('/orders/completed', [\App\Http\Controllers\Api\OrderController::class, 'completedOrders']);
    Route::get('/orders/paid', [\App\Http\Controllers\Api\OrderController::class, 'paidOrders']);
    Route::get('/orders/cooking', [\App\Http\Controllers\Api\OrderController::class, 'cookingOrders']);
    Route::put('/orders/{order}/status', [\App\Http\Controllers\Api\OrderController::class, 'updateStatus']);

    // REPORTS
    Route::get('/reports/summary', [\App\Http\Controllers\Api\ReportController::class, 'summary']);
    Route::get('/reports/product-sales', [\App\Http\Controllers\Api\ReportController::class, 'productSales']);

    // FCM TOKEN UPDATE
    Route::post('/fcm-token', [\App\Http\Controllers\Api\AuthController::class, 'updateFcmToken']);
});