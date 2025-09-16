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
        Schema::table('tables', function (Blueprint $table) {
            // Add position columns for table layout management
            $table->decimal('x_position', 8, 2)->default(50)->after('capacity');
            $table->decimal('y_position', 8, 2)->default(50)->after('x_position');
            
            // Add additional table management fields
            $table->string('customer_name')->nullable()->after('y_position');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->string('table_name')->nullable()->after('customer_phone');
            $table->string('table_status')->nullable()->after('table_name');
            $table->enum('service_type', ['dine_in', 'takeaway', 'delivery'])->default('dine_in')->after('table_status');
            $table->timestamp('start_time')->nullable()->after('service_type');
            $table->unsignedBigInteger('order_id')->nullable()->after('start_time');
            $table->decimal('payment_amount', 10, 2)->default(0)->after('order_id');
            $table->integer('pax_capacity')->nullable()->after('payment_amount');
            $table->string('waiter_assigned')->nullable()->after('pax_capacity');
            $table->timestamp('last_activity')->nullable()->after('waiter_assigned');
            $table->text('special_notes')->nullable()->after('last_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn([
                'x_position',
                'y_position', 
                'customer_name',
                'customer_phone',
                'table_name',
                'table_status',
                'service_type',
                'start_time',
                'order_id',
                'payment_amount',
                'pax_capacity',
                'waiter_assigned',
                'last_activity',
                'special_notes'
            ]);
        });
    }
};
