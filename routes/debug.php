<?php

use Illuminate\Support\Facades\Route;

// DEBUG route untuk testing tenant isolation
Route::get('/debug/tenant-test', function () {
    if (!auth()->check()) {
        return response()->json(['error' => 'Not authenticated']);
    }
    
    // Get user info
    $userId = auth()->id();
    $dbUser = \DB::table('users')->where('id', $userId)->first();
    
    // Test Eloquent query
    $eloquentCategories = \App\Models\Category::all();
    
    // Test raw DB query
    $rawCategories = \DB::table('categories')->where('tenant_id', $dbUser->tenant_id)->get();
    
    // All categories (no filter)
    $allCategories = \DB::table('categories')->get();
    
    return response()->json([
        'auth_check' => auth()->check(),
        'auth_id' => auth()->id(),
        'auth_email' => $dbUser->email ?? null,
        'auth_tenant_id' => $dbUser->tenant_id ?? null,
        
        'eloquent_query_sql' => \App\Models\Category::query()->toSql(),
        'eloquent_count' => $eloquentCategories->count(),
        'eloquent_items' => $eloquentCategories->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'tenant_id' => $c->tenant_id,
        ]),
        
        'raw_filtered_count' => $rawCategories->count(),
        'raw_filtered_items' => $rawCategories->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'tenant_id' => $c->tenant_id,
        ]),
        
        'all_categories_count' => $allCategories->count(),
        'all_categories' => $allCategories->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'tenant_id' => $c->tenant_id,
        ]),
    ]);
})->middleware(['web', 'auth']);
