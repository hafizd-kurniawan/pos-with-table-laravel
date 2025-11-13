<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds all available permissions globally
     * These permissions are NOT tenant-specific
     */
    public function run(): void
    {
        $permissions = [
            // Dashboard Group
            ['name' => 'View Dashboard', 'slug' => 'view_dashboard', 'group' => 'dashboard', 'description' => 'Access main dashboard'],
            ['name' => 'View Analytics', 'slug' => 'view_analytics', 'group' => 'dashboard', 'description' => 'View analytics widgets and charts'],

            // Orders Group
            ['name' => 'View Orders', 'slug' => 'view_orders', 'group' => 'orders', 'description' => 'View all orders'],
            ['name' => 'Create Orders', 'slug' => 'create_orders', 'group' => 'orders', 'description' => 'Create new orders'],
            ['name' => 'Edit Orders', 'slug' => 'edit_orders', 'group' => 'orders', 'description' => 'Edit existing orders'],
            ['name' => 'Delete Orders', 'slug' => 'delete_orders', 'group' => 'orders', 'description' => 'Delete orders'],
            ['name' => 'Manage Order Status', 'slug' => 'manage_order_status', 'group' => 'orders', 'description' => 'Update order status (pending, cooking, ready, etc)'],
            ['name' => 'Void Orders', 'slug' => 'void_orders', 'group' => 'orders', 'description' => 'Void/cancel orders'],

            // Products Group
            ['name' => 'View Products', 'slug' => 'view_products', 'group' => 'products', 'description' => 'View products list'],
            ['name' => 'Create Products', 'slug' => 'create_products', 'group' => 'products', 'description' => 'Add new products'],
            ['name' => 'Edit Products', 'slug' => 'edit_products', 'group' => 'products', 'description' => 'Edit products'],
            ['name' => 'Delete Products', 'slug' => 'delete_products', 'group' => 'products', 'description' => 'Delete products'],
            ['name' => 'Manage Categories', 'slug' => 'manage_categories', 'group' => 'products', 'description' => 'Manage product categories'],
            ['name' => 'Manage Addons', 'slug' => 'manage_addons', 'group' => 'products', 'description' => 'Manage product addons'],

            // Tables Group
            ['name' => 'View Tables', 'slug' => 'view_tables', 'group' => 'tables', 'description' => 'View tables list'],
            ['name' => 'Create Tables', 'slug' => 'create_tables', 'group' => 'tables', 'description' => 'Create new tables'],
            ['name' => 'Edit Tables', 'slug' => 'edit_tables', 'group' => 'tables', 'description' => 'Edit tables'],
            ['name' => 'Delete Tables', 'slug' => 'delete_tables', 'group' => 'tables', 'description' => 'Delete tables'],
            ['name' => 'Manage Table Status', 'slug' => 'manage_table_status', 'group' => 'tables', 'description' => 'Change table status (available, occupied, reserved)'],

            // Reports Group
            ['name' => 'View Reports', 'slug' => 'view_reports', 'group' => 'reports', 'description' => 'View basic reports'],
            ['name' => 'View Financial Reports', 'slug' => 'view_financial_reports', 'group' => 'reports', 'description' => 'View financial reports (revenue, expenses)'],
            ['name' => 'View Product Reports', 'slug' => 'view_product_reports', 'group' => 'reports', 'description' => 'View product performance reports'],
            ['name' => 'Export Reports', 'slug' => 'export_reports', 'group' => 'reports', 'description' => 'Export reports to PDF/Excel'],
            ['name' => 'View All Reports', 'slug' => 'view_all_reports', 'group' => 'reports', 'description' => 'View all tenant reports (admin only)'],

            // Users Group
            ['name' => 'View Users', 'slug' => 'view_users', 'group' => 'users', 'description' => 'View users list'],
            ['name' => 'Create Users', 'slug' => 'create_users', 'group' => 'users', 'description' => 'Create new users'],
            ['name' => 'Edit Users', 'slug' => 'edit_users', 'group' => 'users', 'description' => 'Edit users'],
            ['name' => 'Delete Users', 'slug' => 'delete_users', 'group' => 'users', 'description' => 'Delete users'],
            ['name' => 'Assign Roles', 'slug' => 'assign_roles', 'group' => 'users', 'description' => 'Assign roles to users'],

            // Roles & Permissions Group
            ['name' => 'View Roles', 'slug' => 'view_roles', 'group' => 'roles', 'description' => 'View roles list'],
            ['name' => 'Create Roles', 'slug' => 'create_roles', 'group' => 'roles', 'description' => 'Create new roles'],
            ['name' => 'Edit Roles', 'slug' => 'edit_roles', 'group' => 'roles', 'description' => 'Edit roles'],
            ['name' => 'Delete Roles', 'slug' => 'delete_roles', 'group' => 'roles', 'description' => 'Delete roles'],
            ['name' => 'Assign Permissions', 'slug' => 'assign_permissions', 'group' => 'roles', 'description' => 'Assign permissions to roles'],

            // Settings Group
            ['name' => 'View Settings', 'slug' => 'view_settings', 'group' => 'settings', 'description' => 'View settings'],
            ['name' => 'Edit Settings', 'slug' => 'edit_settings', 'group' => 'settings', 'description' => 'Edit general settings'],
            ['name' => 'Edit Payment Settings', 'slug' => 'edit_payment_settings', 'group' => 'settings', 'description' => 'Edit payment gateway settings'],
            ['name' => 'Edit Appearance', 'slug' => 'edit_appearance', 'group' => 'settings', 'description' => 'Edit appearance/theme settings'],

            // Payments Group
            ['name' => 'Process Payments', 'slug' => 'process_payments', 'group' => 'payments', 'description' => 'Process customer payments'],
            ['name' => 'View Payment History', 'slug' => 'view_payment_history', 'group' => 'payments', 'description' => 'View payment transaction history'],
            ['name' => 'Refund Payments', 'slug' => 'refund_payments', 'group' => 'payments', 'description' => 'Process payment refunds'],

            // Inventory Group (Optional - for future)
            ['name' => 'View Inventory', 'slug' => 'view_inventory', 'group' => 'inventory', 'description' => 'View inventory/stock'],
            ['name' => 'Manage Inventory', 'slug' => 'manage_inventory', 'group' => 'inventory', 'description' => 'Manage inventory/stock levels'],
            ['name' => 'View Stock Reports', 'slug' => 'view_stock_reports', 'group' => 'inventory', 'description' => 'View stock/inventory reports'],

            // Discounts & Taxes Group
            ['name' => 'Manage Discounts', 'slug' => 'manage_discounts', 'group' => 'pricing', 'description' => 'Manage discount rules'],
            ['name' => 'Manage Taxes', 'slug' => 'manage_taxes', 'group' => 'pricing', 'description' => 'Manage tax rules'],
            ['name' => 'Manage Service Charges', 'slug' => 'manage_service_charges', 'group' => 'pricing', 'description' => 'Manage service charge rules'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        $this->command->info('✅ ' . count($permissions) . ' permissions seeded successfully!');
    }
}
