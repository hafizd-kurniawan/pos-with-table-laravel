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
        // 1. Temporarily change to VARCHAR to allow data manipulation without truncation
        DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");

        // 2. Normalize 'completed' to 'complete' (since code uses 'complete')
        DB::table('orders')->where('status', 'completed')->update(['status' => 'complete']);

        // 3. Convert back to ENUM with 'complete', 'refunded', AND 'expired'
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'complete', 'cooking', 'paid', 'cancelled', 'refunded', 'expired') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reverting to avoid data loss if refunded orders exist
    }
};
