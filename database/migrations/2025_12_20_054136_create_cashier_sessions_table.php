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
        Schema::create('cashier_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->decimal('starting_cash', 15, 2);
            $table->decimal('ending_cash', 15, 2)->nullable();
            
            // Calculated fields (for snapshotting history)
            $table->decimal('cash_sales', 15, 2)->default(0);
            $table->decimal('cash_refunds', 15, 2)->default(0);
            $table->decimal('total_pay_in', 15, 2)->default(0);
            $table->decimal('total_pay_out', 15, 2)->default(0);
            $table->decimal('expected_ending_cash', 15, 2)->nullable();
            $table->decimal('variance', 15, 2)->nullable(); // Difference between expected and actual
            
            $table->string('status')->default('open'); // open, closed
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_sessions');
    }
};
