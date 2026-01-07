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
            // Drop old unique constraint on 'key' alone
            $table->dropUnique('settings_key_unique');
            
            // Add composite unique constraint on 'tenant_id' + 'key'
            $table->unique(['tenant_id', 'key'], 'settings_tenant_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Restore old unique constraint
            $table->dropUnique('settings_tenant_key_unique');
            $table->unique('key', 'settings_key_unique');
        });
    }
};
