<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\BelongsToTenant;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id', // Nullable for super admin
        'role_id', // User's role
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    /**
     * Check if user is super admin (no tenant_id)
     */
    public function isSuperAdmin(): bool
    {
        return $this->tenant_id === null;
    }
    
    /**
     * Check if user belongs to specific tenant
     */
    public function belongsToTenant(int $tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }

    /**
     * User's role relationship
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * User's tenant relationship
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        // User without role has no permissions (fallback: could be true for backward compatibility)
        if (!$this->role_id) {
            // For backward compatibility: users without role get admin access
            return true;
        }

        // Check if user's role has the permission
        return $this->role->hasPermission($permissionSlug);
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $roleSlug): bool
    {
        if (!$this->role) {
            return false;
        }

        return $this->role->slug === $roleSlug;
    }

    /**
     * Check if user can perform action (alias for hasPermission)
     */
    public function can($ability, $arguments = []): bool
    {
        // If checking permission by slug
        if (is_string($ability) && strpos($ability, '_') !== false) {
            return $this->hasPermission($ability);
        }

        // Otherwise use default Laravel authorization
        return parent::can($ability, $arguments);
    }

    /**
     * Get all user permissions (through role)
     */
    public function permissions()
    {
        if (!$this->role) {
            return collect();
        }

        return $this->role->permissions;
    }

    /**
     * Check if user is admin (either super admin or has admin role)
     */
    public function isAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->hasRole('admin');
    }
}
