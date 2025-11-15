<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates default roles for each tenant with appropriate permissions
     */
    public function run(): void
    {
        // Get all permissions
        $permissions = Permission::all()->keyBy('slug');

        // Get all tenants
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('⚠️  No tenants found. Skipping role seeding.');
            return;
        }

        $totalRolesCreated = 0;

        foreach ($tenants as $tenant) {
            $this->command->info("📋 Creating roles for tenant: {$tenant->name} (ID: {$tenant->id})");

            // 1. Admin Role - Full Access
            $adminRole = Role::updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => 'admin'],
                [
                    'name' => 'Admin',
                    'description' => 'Full access to all features',
                    'is_default' => true, // Default role for new users
                    'is_system' => true, // Cannot be deleted
                ]
            );
            // Assign ALL permissions to admin
            $adminRole->permissions()->sync($permissions->pluck('id')->toArray());
            $totalRolesCreated++;

            // 2. Manager Role
            $managerRole = Role::updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => 'manager'],
                [
                    'name' => 'Manager',
                    'description' => 'Manage products, orders, and view reports',
                    'is_default' => false,
                    'is_system' => true,
                ]
            );
            // Manager permissions (most permissions except role management)
            $managerPermissions = [
                'view_dashboard', 'view_analytics',
                'view_orders', 'create_orders', 'edit_orders', 'manage_order_status', 'void_orders',
                'view_products', 'create_products', 'edit_products', 'manage_categories', 'manage_addons',
                'view_tables', 'create_tables', 'edit_tables', 'manage_table_status',
                'view_reports', 'view_financial_reports', 'view_product_reports', 'export_reports',
                'view_users', 'create_users', 'edit_users', 'assign_roles',
                'view_roles', // Can view but not modify roles
                'view_settings', 'edit_settings',
                'process_payments', 'view_payment_history',
                'view_inventory', 'manage_inventory', 'view_stock_reports',
                'manage_discounts', 'manage_taxes', 'manage_service_charges',
            ];
            $managerRole->permissions()->sync($permissions->whereIn('slug', $managerPermissions)->pluck('id')->toArray());
            $totalRolesCreated++;

            // 3. Cashier Role
            $cashierRole = Role::updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => 'cashier'],
                [
                    'name' => 'Cashier',
                    'description' => 'Process orders and payments',
                    'is_default' => false,
                    'is_system' => true,
                ]
            );
            // Cashier permissions
            $cashierPermissions = [
                'view_dashboard',
                'view_orders', 'create_orders', 'manage_order_status',
                'view_products', // Can view products to take orders
                'view_tables', 'manage_table_status',
                'process_payments', 'view_payment_history',
            ];
            $cashierRole->permissions()->sync($permissions->whereIn('slug', $cashierPermissions)->pluck('id')->toArray());
            $totalRolesCreated++;

            // 4. Chef/Kitchen Role
            $chefRole = Role::updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => 'chef'],
                [
                    'name' => 'Chef',
                    'description' => 'View and update order cooking status',
                    'is_default' => false,
                    'is_system' => true,
                ]
            );
            // Chef permissions
            $chefPermissions = [
                'view_dashboard',
                'view_orders', 'manage_order_status', // Can see orders and mark as cooking/ready
                'view_products', // Can view menu items
            ];
            $chefRole->permissions()->sync($permissions->whereIn('slug', $chefPermissions)->pluck('id')->toArray());
            $totalRolesCreated++;

            // 5. Waiter Role
            $waiterRole = Role::updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => 'waiter'],
                [
                    'name' => 'Waiter',
                    'description' => 'Take orders and manage tables',
                    'is_default' => false,
                    'is_system' => true,
                ]
            );
            // Waiter permissions
            $waiterPermissions = [
                'view_dashboard',
                'view_orders', 'create_orders', 'manage_order_status', // Can create orders and mark as served
                'view_products',
                'view_tables', 'manage_table_status', // Can manage table status
            ];
            $waiterRole->permissions()->sync($permissions->whereIn('slug', $waiterPermissions)->pluck('id')->toArray());
            $totalRolesCreated++;

            // 6. Viewer Role (Read-only)
            $viewerRole = Role::updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => 'viewer'],
                [
                    'name' => 'Viewer',
                    'description' => 'View-only access to reports',
                    'is_default' => false,
                    'is_system' => false, // Can be deleted
                ]
            );
            // Viewer permissions
            $viewerPermissions = [
                'view_dashboard',
                'view_orders',
                'view_products',
                'view_tables',
                'view_reports', 'view_product_reports',
            ];
            $viewerRole->permissions()->sync($permissions->whereIn('slug', $viewerPermissions)->pluck('id')->toArray());
            $totalRolesCreated++;

            // Assign admin role to existing users without roles
            $usersWithoutRole = User::where('tenant_id', $tenant->id)
                ->whereNull('role_id')
                ->get();

            if ($usersWithoutRole->isNotEmpty()) {
                foreach ($usersWithoutRole as $user) {
                    $user->update(['role_id' => $adminRole->id]);
                    $this->command->info("   ✅ Assigned Admin role to user: {$user->email}");
                }
            }
        }

        $this->command->info("✅ Total roles created: {$totalRolesCreated}");
        $this->command->info("✅ Role seeding completed successfully!");
    }
}
