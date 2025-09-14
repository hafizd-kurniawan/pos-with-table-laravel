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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
           //qr
            $table->string('qr_code')->nullable();
            //status
            $table->enum('status', ['available', 'occupied', 'reserved'])->default('available');
            //capacity
            $table->unsignedInteger('capacity')->default(1);
            //time for calculate how long the table is occupied
            $table->timestamp('occupied_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
