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
        Schema::table('reservations', function (Blueprint $table) {
            // Drop the old enum column and recreate with new values
            $table->dropColumn('status');
        });

        Schema::table('reservations', function (Blueprint $table) {
            // Add the column back with updated enum values
            $table->enum('status', [
                'pending', 
                'confirmed', 
                'checked_in',
                'completed', 
                'cancelled',
                'no_show'
            ])->default('pending')->after('reservation_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('reservations', function (Blueprint $table) {
            // Restore original enum values
            $table->enum('status', [
                'pending', 
                'confirmed', 
                'cancelled', 
                'completed'
            ])->default('pending')->after('reservation_time');
        });
    }
};
