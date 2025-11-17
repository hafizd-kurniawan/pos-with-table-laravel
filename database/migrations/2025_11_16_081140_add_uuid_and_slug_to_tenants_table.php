<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add UUID and slug for secure tenant identification in public URLs
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Full UUID (36 chars) for maximum security - nullable first
            $table->uuid('uuid')->nullable()->after('id');
            
            // Short UUID (8 chars) for balanced URL length - nullable first
            $table->string('short_uuid', 8)->nullable()->after('uuid');
            
            // Slug for SEO and user-friendly URLs - nullable first
            $table->string('slug')->nullable()->after('short_uuid');
        });
        
        // Generate UUIDs and slugs for existing tenants
        $tenants = \DB::table('tenants')->get();
        foreach ($tenants as $tenant) {
            $uuid = \Illuminate\Support\Str::uuid()->toString();
            
            \DB::table('tenants')
                ->where('id', $tenant->id)
                ->update([
                    'uuid' => $uuid,
                    'short_uuid' => substr($uuid, 0, 8),
                    'slug' => \Illuminate\Support\Str::slug($tenant->business_name),
                ]);
        }
        
        // Now add unique constraints and index
        Schema::table('tenants', function (Blueprint $table) {
            $table->unique('uuid');
            $table->unique('short_uuid');
            $table->unique('slug');
            $table->index(['slug', 'short_uuid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['slug', 'short_uuid']);
            $table->dropColumn(['uuid', 'short_uuid', 'slug']);
        });
    }
};
