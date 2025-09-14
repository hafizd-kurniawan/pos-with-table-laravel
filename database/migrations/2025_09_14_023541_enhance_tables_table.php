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
            // Hanya tambah field yang benar-benar missing
            $table->integer('party_size')->nullable()->after('capacity');
            $table->datetime('reservation_time')->nullable()->after('party_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn(['party_size', 'reservation_time']);
        });
    }
};
