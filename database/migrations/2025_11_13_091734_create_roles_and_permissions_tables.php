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
        // 1. Permissions Table (Global - not tenant specific)
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "View Reports"
            $table->string('slug')->unique(); // e.g., "view_reports"
            $table->string('group'); // e.g., "reports", "products"
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('slug');
            $table->index('group');
        });

        // 2. Roles Table (Tenant specific)
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Cashier", "Manager"
            $table->string('slug'); // e.g., "cashier", "manager"
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false); // Default role for new users
            $table->boolean('is_system')->default(false); // System roles (cannot delete)
            $table->timestamps();
            
            $table->unique(['tenant_id', 'slug'], 'unique_slug_per_tenant');
            $table->index('tenant_id');
            $table->index('slug');
        });

        // 3. Role-Permission Pivot Table
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['role_id', 'permission_id'], 'unique_role_permission');
            $table->index('role_id');
            $table->index('permission_id');
        });

        // 4. Update Users Table - Add role_id
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('tenant_id')->constrained()->onDelete('set null');
            $table->index('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse order
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
        
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
