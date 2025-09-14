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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('tables')->onDelete('cascade');
            //total amount of the order
            $table->decimal('total_amount', 10, 2);
            //status of the order
            $table->enum('status', ['pending', 'completed', 'cooking', 'paid', 'cancelled'])->default('pending');
            //time when the order was placed
            $table->timestamp('placed_at')->useCurrent();
            //time when the order was completed
            $table->timestamp('completed_at')->nullable();
            //payment method qris
            $table->string('payment_method')->default('qris');
            //additional notes for the order
            $table->text('notes')->nullable();
            //customer name
            $table->string('customer_name')->nullable();
            //customer phone number
            $table->string('customer_phone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
