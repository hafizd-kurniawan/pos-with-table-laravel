<?php

namespace App\Filament\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for Filament Resources to scope queries to current tenant
 * 
 * This ensures:
 * 1. Index page shows only current tenant's records
 * 2. Edit page can only access current tenant's records (no 404)
 * 3. Delete action only affects current tenant's records
 * 4. Complete isolation at Filament query level
 * 
 * Usage:
 * ```php
 * class ProductResource extends Resource
 * {
 *     use BelongsToTenantResource;
 * }
 * ```
 */
trait BelongsToTenantResource
{
    /**
     * Scope all Filament queries to current tenant
     * 
     * CRITICAL: Uses direct DB query to avoid infinite loop
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Get authenticated user's tenant_id WITHOUT triggering User query
        if (auth()->check()) {
            $userId = auth()->id();
            $user = \DB::table('users')->where('id', $userId)->first();
            
            if ($user && $user->tenant_id) {
                $query->where($query->getModel()->getTable() . '.tenant_id', $user->tenant_id);
            }
        }
        
        return $query;
    }
}
