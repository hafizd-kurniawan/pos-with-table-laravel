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
// PUBLIC CART ENDPOINTS (for Self-Order)
// ========================================
Route::post('/cart/validate', [\App\Http\Controllers\Api\CartController::class, 'validateCart']);
Route::post('/cart/stock', [\App\Http\Controllers\Api\CartController::class, 'getCartStock']);
Route::post('/cart/pre-checkout', [\App\Http\Controllers\Api\CartController::class, 'preCheckout']);

// ========================================
// TENANT ENDPOINTS (with auth + tenant middleware)
// ========================================
Route::middleware(['auth:sanctum', 'tenant'])->prefix('tenant')->group(function () {
    // Tenant Info
    Route::get('/info', function (Request $request) {
        $tenant = app('tenant');
        return response()->json([
            'success' => true,
            'tenant' => [
                'id' => $tenant->id,
                'subdomain' => $tenant->subdomain,
                'business_name' => $tenant->business_name,
                'email' => $tenant->email,
                'status' => $tenant->status,
                'status_label' => $tenant->status_label,
                'days_until_expiry' => $tenant->getDaysUntilExpiry(),
                'trial_ends_at' => $tenant->trial_ends_at?->format('d M Y H:i'),
                'subscription_ends_at' => $tenant->subscription_ends_at?->format('d M Y H:i'),
            ],
        ]);
    });
    
    // Tenant Settings Management
    Route::get('/settings', [\App\Http\Controllers\Api\TenantSettingsController::class, 'index']);
    
    // Midtrans Configuration
    Route::post('/settings/midtrans', [\App\Http\Controllers\Api\TenantSettingsController::class, 'updateMidtrans']);
    Route::delete('/settings/midtrans', [\App\Http\Controllers\Api\TenantSettingsController::class, 'deleteMidtrans']);
    Route::post('/settings/midtrans/test', [\App\Http\Controllers\Api\TenantSettingsController::class, 'testMidtrans']);
    
    // N8N Webhook Configuration
    Route::post('/settings/n8n', [\App\Http\Controllers\Api\TenantSettingsController::class, 'updateN8N']);
    Route::delete('/settings/n8n', [\App\Http\Controllers\Api\TenantSettingsController::class, 'deleteN8N']);
    
    // Firebase Configuration
    Route::post('/settings/firebase', [\App\Http\Controllers\Api\TenantSettingsController::class, 'updateFirebase']);
    Route::delete('/settings/firebase', [\App\Http\Controllers\Api\TenantSettingsController::class, 'deleteFirebase']);
});

// ========================================
// AUTHENTICATION ENDPOINTS
// ========================================
// Login: NO tenant middleware (user belum auth, tenant detected from user)
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

// Logout & User Info: Require auth only
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user()->load('tenant', 'role');
    });
});

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
// AUTHENTICATED ENDPOINTS (with tenant middleware)
// ========================================
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    
    // CATEGORIES
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
    
    // CATEGORIES - Flutter Compatible Routes
    Route::get('/api-categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);

    // PRODUCTS  
    Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::get('/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'show']);
    Route::post('/products/check-stock', [\App\Http\Controllers\Api\ProductController::class, 'checkStock']);

    // DISCOUNTS (Migrated from laravel-old)
    Route::apiResource('/discounts', \App\Http\Controllers\Api\DiscountController::class);
    Route::get('/discounts-active', [\App\Http\Controllers\Api\DiscountController::class, 'active']);
    
    // DISCOUNTS - Flutter Compatible Routes
    Route::get('/api-discounts', [\App\Http\Controllers\Api\DiscountController::class, 'index']);
    Route::post('/api-discounts', [\App\Http\Controllers\Api\DiscountController::class, 'store']);

    // TAXES & SERVICE CHARGES
    Route::apiResource('/taxes', \App\Http\Controllers\Api\TaxController::class);
    
    // POS SETTINGS (Unified endpoint for Discount, Tax, Service)
    Route::get('/pos-settings', [\App\Http\Controllers\Api\PosSettingsController::class, 'index']);

    // ========================================
    // INVENTORY MANAGEMENT
    // ========================================
    
    // Suppliers
    Route::apiResource('/suppliers', \App\Http\Controllers\Api\SupplierController::class);
    
    // Ingredients
    Route::get('/ingredients', [\App\Http\Controllers\Api\IngredientController::class, 'index']);
    Route::post('/ingredients', [\App\Http\Controllers\Api\IngredientController::class, 'store']);
    Route::get('/ingredients/{id}', [\App\Http\Controllers\Api\IngredientController::class, 'show']);
    Route::put('/ingredients/{id}', [\App\Http\Controllers\Api\IngredientController::class, 'update']);
    Route::delete('/ingredients/{id}', [\App\Http\Controllers\Api\IngredientController::class, 'destroy']);
    Route::post('/ingredients/{id}/adjust-stock', [\App\Http\Controllers\Api\IngredientController::class, 'adjustStock']);
    Route::get('/ingredients/{id}/stock-history', [\App\Http\Controllers\Api\IngredientController::class, 'stockHistory']);
    Route::get('/ingredients-low-stock', [\App\Http\Controllers\Api\IngredientController::class, 'lowStock']);
    Route::get('/ingredients-categories', [\App\Http\Controllers\Api\IngredientController::class, 'categories']);
    
    // Purchase Orders
    Route::get('/purchase-orders', [\App\Http\Controllers\Api\PurchaseOrderController::class, 'index']);
    Route::post('/purchase-orders', [\App\Http\Controllers\Api\PurchaseOrderController::class, 'store']);
    Route::get('/purchase-orders/{id}', [\App\Http\Controllers\Api\PurchaseOrderController::class, 'show']);
    Route::put('/purchase-orders/{id}', [\App\Http\Controllers\Api\PurchaseOrderController::class, 'update']);
    Route::delete('/purchase-orders/{id}', [\App\Http\Controllers\Api\PurchaseOrderController::class, 'destroy']);
    Route::post('/purchase-orders/{id}/send', [\App\Http\Controllers\Api\PurchaseOrderController::class, 'markAsSent']);
    Route::post('/purchase-orders/{id}/receive', [\App\Http\Controllers\Api\PurchaseOrderController::class, 'receive']);
    Route::post('/purchase-orders/{id}/cancel', [\App\Http\Controllers\Api\PurchaseOrderController::class, 'cancel']);
    
    // Recipes (Product-Ingredient Mapping)
    Route::get('/products/{productId}/recipes', [\App\Http\Controllers\Api\RecipeController::class, 'index']);
    Route::post('/products/{productId}/recipes', [\App\Http\Controllers\Api\RecipeController::class, 'store']);
    Route::put('/products/{productId}/recipes/{recipeId}', [\App\Http\Controllers\Api\RecipeController::class, 'update']);
    Route::delete('/products/{productId}/recipes/{recipeId}', [\App\Http\Controllers\Api\RecipeController::class, 'destroy']);
    Route::get('/products/{productId}/check-availability', [\App\Http\Controllers\Api\RecipeController::class, 'checkAvailability']);
    Route::get('/taxes-active', [\App\Http\Controllers\Api\TaxController::class, 'active']);
    Route::get('/taxes-by-type', [\App\Http\Controllers\Api\TaxController::class, 'byType']);
    Route::post('/taxes-calculate', [\App\Http\Controllers\Api\TaxController::class, 'calculate']);
    
    // TAXES - Flutter Compatible Routes
    Route::get('/api-taxes', [\App\Http\Controllers\Api\TaxController::class, 'index']);
    Route::post('/api-taxes', [\App\Http\Controllers\Api\TaxController::class, 'store']);

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

    // ORDERS
    Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::post('/orders', [\App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::get('/orders/completed', [\App\Http\Controllers\Api\OrderController::class, 'completedOrders']);
    Route::get('/orders/paid', [\App\Http\Controllers\Api\OrderController::class, 'paidOrders']);
    Route::get('/orders/cooking', [\App\Http\Controllers\Api\OrderController::class, 'cookingOrders']);
    Route::put('/orders/{order}/status', [\App\Http\Controllers\Api\OrderController::class, 'updateStatus']);
    
    // ORDERS - Flutter Compatible Routes  
    Route::post('/save-order', [\App\Http\Controllers\Api\OrderController::class, 'saveOrder']);
    Route::get('/order-item', [\App\Http\Controllers\Api\OrderController::class, 'orderItems']);
    Route::get('/order-sales', [\App\Http\Controllers\Api\OrderController::class, 'orderSales']);
    Route::get('/summary', [\App\Http\Controllers\Api\OrderController::class, 'summary']);

    // REPORTS (Enhanced)
    Route::get('/reports/summary', [\App\Http\Controllers\Api\ReportController::class, 'summary']);
    Route::get('/reports/product-sales', [\App\Http\Controllers\Api\ReportController::class, 'productSales']);
    
    // NEW REPORTING ENDPOINTS
    Route::get('/reports/daily-summary', [\App\Http\Controllers\Api\ReportController::class, 'dailySummary']);
    Route::get('/reports/period-summary', [\App\Http\Controllers\Api\ReportController::class, 'periodSummary']);
    Route::get('/reports/top-products', [\App\Http\Controllers\Api\ReportController::class, 'topProducts']);
    Route::post('/reports/generate-daily-summary', [\App\Http\Controllers\Api\ReportController::class, 'generateDailySummary']);
    
    // VISUALIZATION ENDPOINTS (Phase 2)
    Route::get('/reports/sales-trend', [\App\Http\Controllers\Api\ReportController::class, 'salesTrend']);
    Route::get('/reports/category-performance', [\App\Http\Controllers\Api\ReportController::class, 'categoryPerformance']);
    Route::get('/reports/hourly-breakdown', [\App\Http\Controllers\Api\ReportController::class, 'hourlyBreakdown']);
    Route::get('/reports/payment-trends', [\App\Http\Controllers\Api\ReportController::class, 'paymentTrends']);
    
    // EXPORT ENDPOINTS (Phase 3)
    Route::get('/reports/export/daily-pdf', [\App\Http\Controllers\Api\ReportController::class, 'exportDailyPDF']);
    Route::get('/reports/export/daily-excel', [\App\Http\Controllers\Api\ReportController::class, 'exportDailyExcel']);
    Route::get('/reports/export/period-pdf', [\App\Http\Controllers\Api\ReportController::class, 'exportPeriodPDF']);
    Route::get('/reports/export/period-excel', [\App\Http\Controllers\Api\ReportController::class, 'exportPeriodExcel']);

    // FCM TOKEN UPDATE
    Route::post('/fcm-token', [\App\Http\Controllers\Api\AuthController::class, 'updateFcmToken']);
    
    // SETTINGS API (For Flutter App)
    Route::get('/settings', [\App\Http\Controllers\API\SettingsController::class, 'index']);
    Route::get('/settings/{key}', [\App\Http\Controllers\API\SettingsController::class, 'show']);
});