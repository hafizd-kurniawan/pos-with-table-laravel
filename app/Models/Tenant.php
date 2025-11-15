<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

/**
 * Tenant Model
 * 
 * Created: 2025-11-13 04:23:03 WIB
 * Purpose: Multi-tenant SAAS - Represents each restaurant/cafe tenant
 * 
 * Features:
 * - Trial system (3 days default, extendable)
 * - Subscription management (Bronze/Silver/Gold/Platinum)
 * - Payment gateway config per tenant (Midtrans) - encrypted
 * - N8N webhook config per tenant
 * - Firebase FCM config per tenant - encrypted
 */
class Tenant extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'subdomain',
        'business_name',
        'email',
        'phone',
        'address',
        'logo',
        'status',
        'trial_starts_at',
        'trial_ends_at',
        'trial_extension_days',
        'subscription_plan',
        'subscription_starts_at',
        'subscription_ends_at',
        'amount_paid',
        'payment_method',
        'payment_proof',
        'midtrans_merchant_id',
        'midtrans_server_key',
        'midtrans_client_key',
        'midtrans_is_production',
        'n8n_webhook_url',
        'notification_phone',
        'notification_settings',
        'firebase_project_id',
        'firebase_credentials',
        'firebase_database_url',
    ];
    
    protected $casts = [
        'trial_starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'subscription_starts_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'notification_settings' => 'array',
        'midtrans_is_production' => 'boolean',
        'amount_paid' => 'decimal:2',
    ];
    
    protected $hidden = [
        'midtrans_server_key',
        'midtrans_client_key',
        'firebase_credentials',
    ];
    
    // === ENCRYPTED ATTRIBUTES === //
    
    /**
     * Get Midtrans Server Key (decrypt)
     */
    public function getMidtransServerKeyAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }
    
    /**
     * Set Midtrans Server Key (encrypt)
     */
    public function setMidtransServerKeyAttribute($value)
    {
        $this->attributes['midtrans_server_key'] = $value ? Crypt::encryptString($value) : null;
    }
    
    /**
     * Get Midtrans Client Key (decrypt)
     */
    public function getMidtransClientKeyAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }
    
    /**
     * Set Midtrans Client Key (encrypt)
     */
    public function setMidtransClientKeyAttribute($value)
    {
        $this->attributes['midtrans_client_key'] = $value ? Crypt::encryptString($value) : null;
    }
    
    /**
     * Get Firebase Credentials (decrypt)
     */
    public function getFirebaseCredentialsAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }
    
    /**
     * Set Firebase Credentials (encrypt)
     */
    public function setFirebaseCredentialsAttribute($value)
    {
        $this->attributes['firebase_credentials'] = $value ? Crypt::encryptString($value) : null;
    }
    
    // === RELATIONSHIPS === //
    
    /**
     * Get all users belonging to this tenant
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    /**
     * Get all products belonging to this tenant
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    
    /**
     * Get all orders belonging to this tenant
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    
    /**
     * Get all categories belonging to this tenant
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }
    
    /**
     * Get all tables belonging to this tenant
     */
    public function tables()
    {
        return $this->hasMany(Table::class);
    }
    
    /**
     * Get all reservations belonging to this tenant
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
    
    // === HELPER METHODS === //
    
    /**
     * Check if trial is currently active
     */
    public function isTrialActive(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at > now();
    }
    
    /**
     * Check if subscription is currently active
     */
    public function isSubscriptionActive(): bool
    {
        return $this->status === 'active' && $this->subscription_ends_at > now();
    }
    
    /**
     * Check if tenant is currently active (trial or subscription)
     */
    public function isActive(): bool
    {
        return $this->isTrialActive() || $this->isSubscriptionActive();
    }
    
    /**
     * Check if Midtrans is configured
     */
    public function hasMidtransConfigured(): bool
    {
        return !empty($this->midtrans_server_key) 
            && !empty($this->midtrans_client_key)
            && !empty($this->midtrans_merchant_id);
    }
    
    /**
     * Check if N8N webhook is configured
     */
    public function hasN8nConfigured(): bool
    {
        return !empty($this->n8n_webhook_url);
    }
    
    /**
     * Check if Firebase is configured
     */
    public function hasFirebaseConfigured(): bool
    {
        return !empty($this->firebase_credentials) 
            && !empty($this->firebase_project_id);
    }
    
    /**
     * Get days until expiry (trial or subscription)
     */
    public function getDaysUntilExpiry(): int
    {
        $expiryDate = $this->status === 'trial' 
            ? $this->trial_ends_at 
            : $this->subscription_ends_at;
            
        if (!$expiryDate) {
            return 0;
        }
        
        $days = now()->diffInDays($expiryDate, false);
        return $days > 0 ? (int) $days : 0;
    }
    
    /**
     * Get human-readable status
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'trial' => '🆓 Trial',
            'active' => '✅ Active',
            'expired' => '⏰ Expired',
            'suspended' => '🚫 Suspended',
            default => '❓ Unknown',
        };
    }
    
    /**
     * Start trial for this tenant
     */
    public function startTrial(int $days = 3): void
    {
        $this->update([
            'status' => 'trial',
            'trial_starts_at' => now(),
            'trial_ends_at' => now()->addDays($days),
        ]);
    }
    
    /**
     * Extend trial by X days (positive = extend, negative = reduce)
     */
    public function extendTrial(int $days): void
    {
        if ($days > 0) {
            $this->increment('trial_extension_days', $days);
        } else {
            $this->decrement('trial_extension_days', abs($days));
        }
        
        if ($this->trial_ends_at) {
            $newTrialEnd = $this->trial_ends_at->addDays($days);
            
            // Prevent setting trial end before trial start
            if ($newTrialEnd < $this->trial_starts_at) {
                $newTrialEnd = $this->trial_starts_at;
            }
            
            $this->update([
                'trial_ends_at' => $newTrialEnd,
            ]);
            
            // Auto-expire if new date is in the past
            if ($newTrialEnd < now() && $this->status === 'trial') {
                $this->update(['status' => 'expired']);
            }
        }
    }
    
    /**
     * Activate subscription
     */
    public function activateSubscription(string $plan, $subscriptionEndsAt, float $amount = 0): void
    {
        $this->update([
            'status' => 'active',
            'subscription_plan' => $plan,
            'subscription_starts_at' => now(),
            'subscription_ends_at' => $subscriptionEndsAt,
            'amount_paid' => $amount,
        ]);
    }
    
    /**
     * Suspend tenant
     */
    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }
    
    /**
     * Reactivate suspended tenant
     */
    public function reactivate(): void
    {
        $status = $this->subscription_ends_at && $this->subscription_ends_at > now() 
            ? 'active' 
            : 'trial';
            
        $this->update(['status' => $status]);
    }
}
