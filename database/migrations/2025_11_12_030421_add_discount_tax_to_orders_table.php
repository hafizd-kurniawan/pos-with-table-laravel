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
            // Discount
            $table->foreignId('discount_id')->nullable()->after('total_amount')->constrained('discounts')->nullOnDelete();
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_id');
            
            // Subtotal (before tax & service)
            $table->decimal('subtotal', 10, 2)->default(0)->after('discount_amount');
            
            // Tax & Service Charge
            $table->decimal('tax_amount', 10, 2)->default(0)->after('subtotal');
            $table->decimal('tax_percentage', 5, 2)->default(0)->after('tax_amount');
            $table->decimal('service_charge_amount', 10, 2)->default(0)->after('tax_percentage');
            $table->decimal('service_charge_percentage', 5, 2)->default(0)->after('service_charge_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['discount_id']);
            $table->dropColumn([
                'discount_id',
                'discount_amount',
                'subtotal',
                'tax_amount',
                'tax_percentage',
                'service_charge_amount',
                'service_charge_percentage',
            ]);
        });
    }
};
