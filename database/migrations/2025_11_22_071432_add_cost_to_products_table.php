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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost', 15, 2)->default(0)->after('price')->comment('Cost of Goods Sold (COGS)');
            $table->decimal('profit_margin_target', 5, 2)->default(35)->after('cost')->comment('Target profit margin percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['cost', 'profit_margin_target']);
        });
    }
};
