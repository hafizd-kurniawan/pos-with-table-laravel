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
        Schema::table('product_addons', function (Blueprint $table) {
            $table->foreignId('ingredient_id')->nullable()->constrained('ingredients')->nullOnDelete();
            $table->decimal('quantity_needed', 10, 2)->default(0)->after('ingredient_id');
            $table->decimal('cost', 15, 2)->default(0)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_addons', function (Blueprint $table) {
            $table->dropForeign(['ingredient_id']);
            $table->dropColumn(['ingredient_id', 'quantity_needed', 'cost']);
        });
    }
};
