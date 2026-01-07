<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Created: 2025-11-13 04:07:58 WIB
     * Purpose: Subscription plans for multi-tenant SAAS (Bronze/Silver/Gold/Platinum)
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            
            // Plan Info
            $table->string('name', 100)->comment('Plan name (e.g., Bronze, Silver, Gold, Platinum)');
            $table->string('slug', 100)->unique()->comment('URL-friendly slug (bronze, silver, gold, platinum)');
            $table->text('description')->nullable()->comment('Plan description');
            
            // Pricing
            $table->integer('duration_days')->comment('Plan duration in days (30, 90, 180, 365)');
            $table->decimal('price', 10, 2)->comment('Plan price in IDR');
            $table->decimal('discount_percentage', 5, 2)->default(0)->comment('Discount if any');
            
            // Feature Limits (-1 = unlimited)
            $table->integer('max_products')->default(-1)->comment('Maximum products allowed (-1 = unlimited)');
            $table->integer('max_orders_per_day')->default(-1)->comment('Maximum orders per day (-1 = unlimited)');
            $table->integer('max_users')->default(-1)->comment('Maximum staff users (-1 = unlimited)');
            $table->integer('max_tables')->default(-1)->comment('Maximum tables (-1 = unlimited)');
            $table->integer('max_reservations_per_day')->default(-1)->comment('Maximum reservations per day');
            
            // Features (JSON)
            $table->json('features')->nullable()->comment('Additional features as JSON');
            
            // Display & Status
            $table->boolean('is_active')->default(true)->comment('Whether plan is available for purchase');
            $table->boolean('is_popular')->default(false)->comment('Highlight as popular choice');
            $table->integer('display_order')->default(0)->comment('Sort order in UI');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('slug');
            $table->index('is_active');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
