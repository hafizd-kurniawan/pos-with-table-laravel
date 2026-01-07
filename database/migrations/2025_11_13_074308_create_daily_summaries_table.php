<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->date('date');
            
            // Order statistics
            $table->integer('total_orders')->default(0);
            $table->integer('total_items')->default(0);
            $table->integer('total_customers')->default(0);
            
            // Revenue breakdown
            $table->decimal('gross_sales', 15, 2)->default(0);
            $table->decimal('total_discount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total_tax', 15, 2)->default(0);
            $table->decimal('total_service', 15, 2)->default(0);
            $table->decimal('net_sales', 15, 2)->default(0);
            
            // Payment methods breakdown
            $table->decimal('cash_amount', 15, 2)->default(0);
            $table->integer('cash_count')->default(0);
            $table->decimal('qris_amount', 15, 2)->default(0);
            $table->integer('qris_count')->default(0);
            $table->decimal('gopay_amount', 15, 2)->default(0);
            $table->integer('gopay_count')->default(0);
            
            // Meta information
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->unique(['tenant_id', 'date']);
            $table->index('date');
            $table->index('is_closed');
            $table->index(['tenant_id', 'date', 'is_closed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_summaries');
    }
};
