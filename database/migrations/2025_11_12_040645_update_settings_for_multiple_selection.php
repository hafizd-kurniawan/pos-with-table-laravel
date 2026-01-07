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
            // Drop old foreign keys
            $table->dropForeign(['selected_discount_id']);
            $table->dropForeign(['selected_tax_id']);
            $table->dropForeign(['selected_service_id']);
            
            // Drop old columns
            $table->dropColumn(['selected_discount_id', 'selected_tax_id', 'selected_service_id']);
            
            // Add new JSON columns for multiple selection
            $table->json('selected_discount_ids')->nullable()->after('value');
            $table->json('selected_tax_ids')->nullable()->after('selected_discount_ids');
            $table->json('selected_service_ids')->nullable()->after('selected_tax_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Drop JSON columns
            $table->dropColumn(['selected_discount_ids', 'selected_tax_ids', 'selected_service_ids']);
            
            // Restore old columns
            $table->unsignedBigInteger('selected_discount_id')->nullable()->after('value');
            $table->unsignedBigInteger('selected_tax_id')->nullable()->after('selected_discount_id');
            $table->unsignedBigInteger('selected_service_id')->nullable()->after('selected_tax_id');
            
            // Restore foreign keys
            $table->foreign('selected_discount_id')->references('id')->on('discounts')->onDelete('set null');
            $table->foreign('selected_tax_id')->references('id')->on('taxes')->onDelete('set null');
            $table->foreign('selected_service_id')->references('id')->on('taxes')->onDelete('set null');
        });
    }
};
