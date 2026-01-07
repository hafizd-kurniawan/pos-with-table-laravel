<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Database\Seeders\DefaultTenantSettingsSeeder;

class RegisterTenantController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            DB::beginTransaction();

            // 1. Generate Subdomain
            $subdomain = Str::slug($request->business_name);
            // Ensure unique subdomain
            $count = 0;
            $originalSubdomain = $subdomain;
            while (Tenant::where('subdomain', $subdomain)->exists()) {
                $count++;
                $subdomain = $originalSubdomain . '-' . $count;
            }

            // 2. Create Tenant
            $tenant = Tenant::create([
                'business_name' => $request->business_name,
                'subdomain' => $subdomain,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'status' => 'trial',
                'trial_starts_at' => now(),
                'trial_ends_at' => now()->addDays(7), // 7 Days Trial
            ]);

            // 3. Create User (Tenant Admin)
            $user = User::create([
                'name' => 'Admin ' . $request->business_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'tenant_id' => $tenant->id,
                'role_id' => 1, // Will be assigned properly after seeding
            ]);

            // 4. Run Seeders for this Tenant
            $this->seedRoles($tenant->id);
            
            // Assign Role ID 1 (Admin) to user now that roles exist
            $adminRole = DB::table('roles')->where('tenant_id', $tenant->id)->where('name', 'admin')->first();
            if ($adminRole) {
                $user->update(['role_id' => $adminRole->id]);
            }

            // Seed Settings
            $this->createDefaultSettings($tenant->id);

            // Seed Order Settings
            $this->createOrderSettings($tenant->id);

            // Seed Takeaway Table
            $this->createTakeawayTable($tenant);

            DB::commit();

            // Create Token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'token' => $token,
                'user' => $user,
                'tenant' => $tenant,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function seedRoles($tenantId)
    {
        // Get all permissions
        $allPermissions = \App\Models\Permission::all();
        
        // 1. Admin Role (Full Access)
        $adminRole = \App\Models\Role::create([
            'tenant_id' => $tenantId,
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Administrator - Full Access',
            'is_default' => true,
            'is_system' => true,
        ]);
        $adminRole->permissions()->sync($allPermissions->pluck('id'));

        // 2. Cashier Role
        $cashierPermissions = [
            'view_dashboard',
            'view_orders', 'create_orders', 'manage_order_status',
            'view_products',
            'view_tables', 'manage_table_status',
            'process_payments', 'view_payment_history',
            'self_attendance', 'view_my_leaves', 'request_leaves',
        ];
        $cashierRole = \App\Models\Role::create([
            'tenant_id' => $tenantId,
            'name' => 'Cashier',
            'slug' => 'cashier',
            'description' => 'Cashier - Process payments',
            'is_default' => false,
            'is_system' => true,
        ]);
        $cashierRole->permissions()->sync($allPermissions->whereIn('slug', $cashierPermissions)->pluck('id'));

        // 3. Kitchen Staff (Chef) Role
        $chefPermissions = [
            'view_dashboard',
            'view_orders', 'manage_order_status',
            'view_products',
            'view_kds', 'manage_kds_status',
            'self_attendance', 'view_my_leaves', 'request_leaves',
        ];
        $kitchenRole = \App\Models\Role::create([
            'tenant_id' => $tenantId,
            'name' => 'Kitchen Staff',
            'slug' => 'kitchen', // Kept 'kitchen' slug to match existing logic if any
            'description' => 'Kitchen Staff - Manage KDS',
            'is_default' => false,
            'is_system' => true,
        ]);
        $kitchenRole->permissions()->sync($allPermissions->whereIn('slug', $chefPermissions)->pluck('id'));

        // 4. Waiter Role
        $waiterPermissions = [
            'view_dashboard',
            'view_orders', 'create_orders', 'manage_order_status',
            'view_products',
            'view_tables', 'manage_table_status',
            'self_attendance', 'view_my_leaves', 'request_leaves',
        ];
        $waiterRole = \App\Models\Role::create([
            'tenant_id' => $tenantId,
            'name' => 'Waiter',
            'slug' => 'waiter',
            'description' => 'Waiter - Take orders',
            'is_default' => false,
            'is_system' => true,
        ]);
        $waiterRole->permissions()->sync($allPermissions->whereIn('slug', $waiterPermissions)->pluck('id'));
    }

    private function createDefaultSettings($tenantId)
    {
        $settings = DefaultTenantSettingsSeeder::getDefaultSettings();
        \App\Models\Setting::unguarded(function () use ($settings, $tenantId) {
            foreach ($settings as $setting) {
                \App\Models\Setting::create(array_merge($setting, ['tenant_id' => $tenantId]));
            }
        });
    }

    private function createOrderSettings($tenantId)
    {
        $settings = [
            ['key' => 'tax_rate', 'value' => '11', 'type' => 'number', 'group' => 'order', 'label' => 'Pajak (%)', 'description' => 'Persentase pajak (PPN)'],
            ['key' => 'service_charge_rate', 'value' => '5', 'type' => 'number', 'group' => 'order', 'label' => 'Service Charge (%)', 'description' => 'Persentase service charge'],
            ['key' => 'enable_tax', 'value' => '1', 'type' => 'boolean', 'group' => 'order', 'label' => 'Aktifkan Pajak', 'description' => 'Hitung pajak pada order'],
            ['key' => 'enable_service_charge', 'value' => '0', 'type' => 'boolean', 'group' => 'order', 'label' => 'Aktifkan Service Charge', 'description' => 'Hitung service charge pada order'],
        ];

        \App\Models\Setting::unguarded(function () use ($settings, $tenantId) {
            foreach ($settings as $setting) {
                \App\Models\Setting::create(array_merge($setting, ['tenant_id' => $tenantId]));
            }
        });
    }

    private function createTakeawayTable($tenant)
    {
        \App\Models\Table::unguarded(function () use ($tenant) {
            \App\Models\Table::create([
                'tenant_id' => $tenant->id,
                'name' => 'Takeaway - ' . $tenant->subdomain,
                'capacity' => 0,
                'status' => 'available',
                'location' => 'counter',
                'x_position' => 0,
                'y_position' => 0,
            ]);
        });
    }
}
