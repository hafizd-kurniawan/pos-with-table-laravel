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
        Schema::table('settings', function (Blueprint $table) {
            // Add columns for selected discount, tax, and service IDs
            $table->unsignedBigInteger('selected_discount_id')->nullable()->after('value');
            $table->unsignedBigInteger('selected_tax_id')->nullable()->after('selected_discount_id');
            $table->unsignedBigInteger('selected_service_id')->nullable()->after('selected_tax_id');
            
            // Add foreign keys
            $table->foreign('selected_discount_id')->references('id')->on('discounts')->onDelete('set null');
            $table->foreign('selected_tax_id')->references('id')->on('taxes')->onDelete('set null');
            $table->foreign('selected_service_id')->references('id')->on('taxes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign(['selected_discount_id']);
            $table->dropForeign(['selected_tax_id']);
            $table->dropForeign(['selected_service_id']);
            
            $table->dropColumn('selected_discount_id');
            $table->dropColumn('selected_tax_id');
            $table->dropColumn('selected_service_id');
        });
    }
};
