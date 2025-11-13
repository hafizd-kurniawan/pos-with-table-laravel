<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Created: 2025-11-13 04:21:28 WIB
     * Purpose: Add tenant_id to all existing tables for multi-tenancy
     * 
     * SAFE MIGRATION:
     * - Uses nullable() initially to avoid errors on existing data
     * - Will set tenant_id = 1 (default tenant) for existing records via seeder
     * - Then remove nullable constraint in future migration after data migration
     */
    public function up(): void
    {
        // Products table
        if (!Schema::hasColumn('products', 'tenant_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }
        
        // Categories table
        if (!Schema::hasColumn('categories', 'tenant_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }
        
        // Orders table
        if (!Schema::hasColumn('orders', 'tenant_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }
        
        // Order Items table
        if (!Schema::hasColumn('order_items', 'tenant_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }
        
        // Tables table
        if (!Schema::hasColumn('tables', 'tenant_id')) {
            Schema::table('tables', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }
        
        // Table Categories table
        if (!Schema::hasColumn('table_categories', 'tenant_id')) {
            Schema::table('table_categories', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }
        
        // Reservations table
        if (!Schema::hasColumn('reservations', 'tenant_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }
        
        // Discounts table
        if (!Schema::hasColumn('discounts', 'tenant_id')) {
            Schema::table('discounts', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }
        
        // Taxes table
        if (!Schema::hasColumn('taxes', 'tenant_id')) {
            Schema::table('taxes', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }
        
        // Settings table
        if (!Schema::hasColumn('settings', 'tenant_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }
        
        // Users table
        if (!Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tenant_id columns in reverse order
        $tables = [
            'users',
            'settings',
            'taxes',
            'discounts',
            'reservations',
            'table_categories',
            'tables',
            'order_items',
            'orders',
            'categories',
            'products',
        ];
        
        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropIndex(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }
};
