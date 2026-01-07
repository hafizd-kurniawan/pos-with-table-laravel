<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant;

/**
 * BelongsToTenant Trait
 * 
 * Created: 2025-11-13 04:34:00 WIB
 * Purpose: Auto-scope all queries to current tenant and auto-assign tenant_id on create
 * 
 * Usage:
 * ```php
 * use App\Traits\BelongsToTenant;
 * 
 * class Product extends Model {
 *     use BelongsToTenant;
 * }
 * 
 * // Now all queries automatically filtered:
 * Product::all(); // WHERE tenant_id = current_tenant
 * Product::find(1); // WHERE id = 1 AND tenant_id = current_tenant
 * ```
 */
trait BelongsToTenant
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToTenant(): void
    {
        // Auto-add tenant_id when creating
        static::creating(function (Model $model) {
            if (!$model->tenant_id) {
                $model->tenant_id = static::getCurrentTenantId();
            }
        });
        
        // Auto-scope all queries to current tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = static::getCurrentTenantId();
            
            if ($tenantId) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });
    }
    
    /**
     * Get current tenant ID
     * 
     * IMPORTANT: Prevents infinite loops by checking if we're already resolving
     */
    protected static function getCurrentTenantId(): ?int
    {
        // Prevent infinite loop: if we're already resolving, return null
        static $resolving = false;
        
        if ($resolving) {
            return null;
        }
        
        $resolving = true;
        
        try {
            // PRIORITY 1: Get from authenticated user (MOST RELIABLE)
            if (auth()->check()) {
                // CRITICAL: Get user ID first, then query WITHOUT scope
                $userId = auth()->id();
                if ($userId) {
                    // Query user table directly without scope to avoid loop
                    $user = \DB::table('users')->where('id', $userId)->first();
                    if ($user && isset($user->tenant_id) && $user->tenant_id) {
                        $resolving = false;
                        return (int) $user->tenant_id;
                    }
                }
            }
            
            // PRIORITY 2: Get from app instance
            if (app()->has('tenant')) {
                $tenant = app('tenant');
                if ($tenant && isset($tenant->id)) {
                    $resolving = false;
                    return (int) $tenant->id;
                }
            }
            
            // PRIORITY 3: Get from session
            if (session()->has('tenant_id')) {
                $resolving = false;
                return (int) session('tenant_id');
            }
            
            // PRIORITY 4: Get from request
            if (request()->has('tenant_id')) {
                $resolving = false;
                return (int) request('tenant_id');
            }
            
            $resolving = false;
            return null;
        } catch (\Exception $e) {
            $resolving = false;
            \Log::error('BelongsToTenant::getCurrentTenantId() error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Relationship: Belongs to tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    
    /**
     * Scope: Without tenant filter (use with caution!)
     * Usage: Product::withoutTenantScope()->get();
     */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
    
    /**
     * Scope: For specific tenant (admin only)
     * Usage: Product::forTenant($tenantId)->get();
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->withoutGlobalScope('tenant')
            ->where($query->getModel()->getTable() . '.tenant_id', $tenantId);
    }
    
    /**
     * Scope: All tenants (super admin only)
     * Usage: Product::allTenants()->get();
     */
    public function scopeAllTenants(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}
