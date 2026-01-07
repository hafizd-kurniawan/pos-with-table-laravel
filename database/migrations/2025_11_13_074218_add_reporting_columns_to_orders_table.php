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
        Schema::table('orders', function (Blueprint $table) {
            // Add payment_status for better tracking
            $table->string('payment_status', 20)->default('pending')->after('payment_method');
            // Values: pending, paid, failed, refunded
            
            // Add cashier_id for tracking who processed the order
            $table->unsignedBigInteger('cashier_id')->nullable()->after('customer_email');
            
            // Add closed_at for daily closing tracking
            $table->timestamp('closed_at')->nullable()->after('completed_at');
            
            // Add indexes for faster reporting queries
            $table->index('payment_status');
            $table->index('payment_method');
            $table->index('closed_at');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'payment_method']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropIndex(['tenant_id', 'payment_method']);
            $table->dropIndex(['tenant_id', 'created_at']);
            $table->dropIndex(['closed_at']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['payment_status']);
            
            // Drop columns
            $table->dropColumn(['payment_status', 'cashier_id', 'closed_at']);
        });
    }
};
