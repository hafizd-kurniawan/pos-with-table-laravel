<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'group',
        'description',
    ];

    /**
     * Permissions are global - NOT tenant specific
     * This ensures consistency across all tenants
     */

    /**
     * Roles that have this permission
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * Group permissions by their group
     */
    public static function grouped()
    {
        return static::all()->groupBy('group')->map(function ($permissions, $group) {
            return [
                'group' => $group,
                'permissions' => $permissions,
            ];
        })->values();
    }

    /**
     * Get permissions by group
     */
    public static function getByGroup(string $group)
    {
        return static::where('group', $group)->get();
    }
}
