<?php

namespace App\Filament\Traits;

trait HasTenantScope
{
    /**
     * Mutate form data before create to add tenant_id from authenticated user
     * 
     * This ensures all records created via Filament automatically get
     * the correct tenant_id from the logged-in user.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // CRITICAL: Get user WITHOUT triggering User query scope
        if (!auth()->check()) {
            \Log::error('HasTenantScope: No authenticated user!', [
                'data' => $data,
                'resource' => static::class,
            ]);
            throw new \Exception('Authentication required. Please login as tenant admin.');
        }
        
        $userId = auth()->id();
        $user = \DB::table('users')->where('id', $userId)->first();
        
        if (!$user) {
            \Log::error('HasTenantScope: User not found in database!', [
                'user_id' => $userId,
                'data' => $data,
                'resource' => static::class,
            ]);
            throw new \Exception('User not found. Please login again.');
        }
        
        if (!$user->tenant_id) {
            \Log::error('HasTenantScope: User has no tenant_id!', [
                'user_id' => $user->id,
                'email' => $user->email,
                'tenant_id' => $user->tenant_id,
                'data' => $data,
                'resource' => static::class,
            ]);
            throw new \Exception('Access denied. Super admins cannot create tenant data. Login as tenant admin instead.');
        }
        
        // Inject tenant_id
        $data['tenant_id'] = $user->tenant_id;
        
        \Log::info('HasTenantScope: Injected tenant_id', [
            'user_email' => $user->email,
            'tenant_id' => $user->tenant_id,
            'model' => $data['name'] ?? $data['title'] ?? 'Unknown',
        ]);
        
        return $data;
    }
}
