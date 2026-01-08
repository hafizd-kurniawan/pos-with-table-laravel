<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change the status column from ENUM to STRING to prevent truncation errors
        // and allow flexible statuses like 'completed', 'cooking', 'processing'.
        DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(255) NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to ENUM if needed (optional, but good practice to define)
        // Note: We include all current known statuses to be safe
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'completed', 'cooking', 'paid', 'cancelled', 'processing', 'ready', 'served') NOT NULL DEFAULT 'pending'");
    }
};
