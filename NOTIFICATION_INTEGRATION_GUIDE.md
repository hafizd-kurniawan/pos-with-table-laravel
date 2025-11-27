# 🔔 NOTIFICATION INTEGRATION - Laravel Backend

## ✅ CURRENT STATUS (What You Already Have)

```
✅ fcm_token column in users table (migration: 2025_08_10_224438_add_fcm_at_users.php)
✅ Firebase package installed (kreait/laravel-firebase v6.1)
✅ FCM token endpoint: POST /api/fcm-token (AuthController::updateFcmToken)
✅ Firebase config in .env (FIREBASE_CREDENTIALS, FIREBASE_DATABASE_URL)
✅ Low stock listener skeleton (SendLowStockNotification.php)
✅ Observer pattern exists (ReservationObserver, PurchaseOrderObserver)

STATUS: 50% Complete! Just need to complete implementation.
```

---

## 🎯 WHAT NEEDS TO BE DONE (50% Remaining)

```
Phase 1 (30 min):
├─ Update User model (add fcm_token to fillable)
├─ Download Firebase credentials file
├─ Publish Firebase config
└─ Create NotificationService class

Phase 2 (1 hour):
├─ Create Notification model
├─ Create NotificationPreference model
├─ Create migrations for tables
└─ Create API endpoints

Phase 3 (30 min):
├─ Create OrderObserver
├─ Update SendLowStockNotification
└─ Register observers

Phase 4 (30 min):
├─ Testing
└─ Documentation

TOTAL: 2.5 hours
```

---

## 🚀 STEP-BY-STEP IMPLEMENTATION

### **PHASE 1: SETUP & CONFIG (30 minutes)**

#### **Step 1.1: Update User Model**

**File:** `app/Models/User.php`

**Add to $fillable array:**
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'tenant_id',
    'role_id',
    'fcm_token', // ← ADD THIS
];
```

**Add methods at end of class:**
```php
// FCM Token Management
public function hasFcmToken(): bool
{
    return !empty($this->fcm_token);
}

public function updateFcmToken(string $token): void
{
    $this->update([
        'fcm_token' => $token,
    ]);
}

public function clearFcmToken(): void
{
    $this->update([
        'fcm_token' => null,
    ]);
}

// Notification Preferences Relationship
public function notificationPreferences()
{
    return $this->hasOne(NotificationPreference::class);
}

// Notifications History Relationship
public function notifications()
{
    return $this->hasMany(Notification::class);
}
```

---

#### **Step 1.2: Download Firebase Credentials**

1. Go to: https://console.firebase.google.com
2. Select project: **self-order-7f581**
3. Click: ⚙️ Project Settings
4. Go to: Service accounts tab
5. Click: "Generate new private key"
6. Download JSON file
7. Save as: `storage/app/firebase-auth.json`

```bash
cd /home/biru/Downloads/gabungan/laravel

# Verify file exists
ls -la storage/app/firebase-auth.json

# Set proper permissions
chmod 600 storage/app/firebase-auth.json
```

---

#### **Step 1.3: Publish Firebase Config**

```bash
cd /home/biru/Downloads/gabungan/laravel

# Publish Firebase config
php artisan vendor:publish --provider="Kreait\Laravel\Firebase\ServiceProvider"
```

**This creates:** `config/firebase.php`

**Update `.env` (if needed):**
```env
FIREBASE_CREDENTIALS=storage/app/firebase-auth.json
FIREBASE_DATABASE_URL=https://self-order-7f581-default-rtdb.firebaseio.com
```

---

#### **Step 1.4: Create NotificationService**

```bash
php artisan make:class Services/NotificationService
```

**File:** `app/Services/NotificationService.php`

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $messaging;

    public function __construct()
    {
        try {
            $factory = (new Factory)
                ->withServiceAccount(storage_path('app/firebase-auth.json'));
            
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to user
     */
    public function sendToUser(User $user, array $data): bool
    {
        if (!$user->hasFcmToken()) {
            Log::warning("User {$user->id} has no FCM token");
            return false;
        }

        if (!$this->messaging) {
            Log::error('Firebase messaging not initialized');
            return false;
        }

        // Save to database
        $notification = Notification::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'type' => $data['type'],
            'title' => $data['title'],
            'body' => $data['body'],
            'data' => $data['data'] ?? null,
        ]);

        try {
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(
                    FirebaseNotification::create($data['title'], $data['body'])
                )
                ->withData([
                    'type' => $data['type'],
                    'id' => (string) ($data['data']['id'] ?? ''),
                    'notification_id' => (string) $notification->id,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]);

            $this->messaging->send($message);
            $notification->update(['is_sent' => true]);

            Log::info("Notification sent to user {$user->id}", [
                'type' => $data['type'],
                'notification_id' => $notification->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send notification: " . $e->getMessage());
            $notification->update([
                'is_sent' => false,
                'error_message' => $e->getMessage(),
            ]);

            // Clear invalid token
            if (str_contains($e->getMessage(), 'not-found') ||
                str_contains($e->getMessage(), 'invalid-registration-token')) {
                $user->clearFcmToken();
            }

            return false;
        }
    }

    /**
     * Send new order notification
     */
    public function sendNewOrderNotification(User $user, $order): bool
    {
        return $this->sendToUser($user, [
            'type' => 'new_order',
            'title' => '🛒 New Order!',
            'body' => "Order #{$order->id}" . 
                      ($order->table_number ? " - Table {$order->table_number}" : '') .
                      " - Rp " . number_format($order->total, 0, ',', '.'),
            'data' => [
                'id' => (string) $order->id,
                'order_type' => $order->order_type ?? 'dine-in',
                'table_number' => $order->table_number ?? '',
                'total_amount' => $order->total,
            ],
        ]);
    }

    /**
     * Send low stock notification
     */
    public function sendLowStockNotification(User $user, $ingredient): bool
    {
        return $this->sendToUser($user, [
            'type' => 'low_stock',
            'title' => '📦 Low Stock Alert',
            'body' => "{$ingredient->name} - Stock: {$ingredient->current_stock} (Min: {$ingredient->min_stock})",
            'data' => [
                'id' => (string) $ingredient->id,
                'name' => $ingredient->name,
                'current_stock' => $ingredient->current_stock,
                'min_stock' => $ingredient->min_stock,
            ],
        ]);
    }

    /**
     * Send to multiple users
     */
    public function sendToTenantUsers(int $tenantId, array $roles, array $data): array
    {
        $users = User::where('tenant_id', $tenantId)
            ->whereIn('role_id', $roles)
            ->whereNotNull('fcm_token')
            ->get();

        $results = ['success' => 0, 'failed' => 0];
        
        foreach ($users as $user) {
            if ($this->sendToUser($user, $data)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }
}
```

---

### **PHASE 2: DATABASE & MODELS (1 hour)**

#### **Step 2.1: Create Notifications Migration**

```bash
php artisan make:migration create_notifications_table
```

**File:** `database/migrations/xxxx_create_notifications_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type'); // new_order, low_stock, payment, system
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
            $table->index(['tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
```

---

#### **Step 2.2: Create Notification Preferences Migration**

```bash
php artisan make:migration create_notification_preferences_table
```

**File:** `database/migrations/xxxx_create_notification_preferences_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('fcm_enabled')->default(true);
            $table->boolean('sound_enabled')->default(true);
            $table->boolean('new_order_alerts')->default(true);
            $table->boolean('low_stock_alerts')->default(true);
            $table->boolean('payment_alerts')->default(true);
            $table->boolean('system_alerts')->default(true);
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
```

**Run migrations:**
```bash
php artisan migrate
```

---

#### **Step 2.3: Create Notification Model**

```bash
php artisan make:model Notification
```

**File:** `app/Models/Notification.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_id',
        'type',
        'title',
        'body',
        'data',
        'is_read',
        'read_at',
        'is_sent',
        'error_message',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'is_sent' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
```

---

#### **Step 2.4: Create NotificationPreference Model**

```bash
php artisan make:model NotificationPreference
```

**File:** `app/Models/NotificationPreference.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_id',
        'fcm_enabled',
        'sound_enabled',
        'new_order_alerts',
        'low_stock_alerts',
        'payment_alerts',
        'system_alerts',
    ];

    protected $casts = [
        'fcm_enabled' => 'boolean',
        'sound_enabled' => 'boolean',
        'new_order_alerts' => 'boolean',
        'low_stock_alerts' => 'boolean',
        'payment_alerts' => 'boolean',
        'system_alerts' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function createDefault(User $user): self
    {
        return self::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
        ]);
    }

    public function isTypeEnabled(string $type): bool
    {
        if (!$this->fcm_enabled) {
            return false;
        }

        return match($type) {
            'new_order' => $this->new_order_alerts,
            'low_stock' => $this->low_stock_alerts,
            'payment' => $this->payment_alerts,
            'system' => $this->system_alerts,
            default => false,
        };
    }
}
```

---

#### **Step 2.5: Create API Controller**

```bash
php artisan make:controller Api/NotificationController
```

**File:** `app/Http/Controllers/Api/NotificationController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function getPreferences(Request $request)
    {
        $user = $request->user();
        $preferences = $user->notificationPreferences 
            ?? NotificationPreference::createDefault($user);

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $request->validate([
            'fcm_enabled' => 'sometimes|boolean',
            'sound_enabled' => 'sometimes|boolean',
            'new_order_alerts' => 'sometimes|boolean',
            'low_stock_alerts' => 'sometimes|boolean',
            'payment_alerts' => 'sometimes|boolean',
            'system_alerts' => 'sometimes|boolean',
        ]);

        $user = $request->user();
        $preferences = $user->notificationPreferences 
            ?? NotificationPreference::createDefault($user);

        $preferences->update($request->only([
            'fcm_enabled',
            'sound_enabled',
            'new_order_alerts',
            'low_stock_alerts',
            'payment_alerts',
            'system_alerts',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated',
            'data' => $preferences,
        ]);
    }

    public function getNotifications(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Marked as read',
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $count = $request->user()
            ->notifications()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "Marked {$count} as read",
        ]);
    }

    public function getUnreadCount(Request $request)
    {
        $count = $request->user()
            ->notifications()
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count],
        ]);
    }
}
```

---

#### **Step 2.6: Add API Routes**

**File:** `routes/api.php`

**Add these routes inside the `auth:sanctum` middleware group:**

```php
// Notification routes
Route::get('notifications/preferences', [\App\Http\Controllers\Api\NotificationController::class, 'getPreferences']);
Route::put('notifications/preferences', [\App\Http\Controllers\Api\NotificationController::class, 'updatePreferences']);
Route::get('notifications', [\App\Http\Controllers\Api\NotificationController::class, 'getNotifications']);
Route::get('notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'getUnreadCount']);
Route::put('notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
Route::put('notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
```

---

### **PHASE 3: OBSERVERS (30 minutes)**

#### **Step 3.1: Create OrderObserver**

```bash
php artisan make:observer OrderObserver --model=Order
```

**File:** `app/Observers/OrderObserver.php`

```php
<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function created(Order $order): void
    {
        try {
            // Get all kasir and admin users in this tenant
            $users = User::where('tenant_id', $order->tenant_id)
                ->whereNotNull('fcm_token')
                ->get();

            Log::info("Sending new order notification", [
                'order_id' => $order->id,
                'users_count' => $users->count(),
            ]);

            foreach ($users as $user) {
                $this->notificationService->sendNewOrderNotification($user, $order);
            }

        } catch (\Exception $e) {
            Log::error("Failed to send order notifications: " . $e->getMessage());
        }
    }
}
```

---

#### **Step 3.2: Update SendLowStockNotification**

**File:** `app/Listeners/SendLowStockNotification.php`

**Replace the entire handle method with:**

```php
public function handle(LowStockDetected $event): void
{
    $ingredient = $event->ingredient;
    
    Log::warning("Low stock alert: {$ingredient->name}", [
        'ingredient_id' => $ingredient->id,
        'current_stock' => $ingredient->current_stock,
        'min_stock' => $ingredient->min_stock,
    ]);
    
    // Send FCM notifications to admin users
    try {
        $notificationService = app(\App\Services\NotificationService::class);
        
        // Get admin users in this tenant
        $adminUsers = \App\Models\User::where('tenant_id', $ingredient->tenant_id)
            ->whereHas('role', function($query) {
                $query->where('slug', 'admin');
            })
            ->whereNotNull('fcm_token')
            ->get();
        
        foreach ($adminUsers as $user) {
            $notificationService->sendLowStockNotification($user, $ingredient);
        }
        
        Log::info("Low stock notifications sent to {$adminUsers->count()} admins");
        
    } catch (\Exception $e) {
        Log::error("Failed to send low stock notifications: " . $e->getMessage());
    }
}
```

---

#### **Step 3.3: Register OrderObserver**

**File:** `app/Providers/AppServiceProvider.php`

**Add to the `boot()` method:**

```php
use App\Models\Order;
use App\Observers\OrderObserver;

public function boot(): void
{
    // Register observers
    Order::observe(OrderObserver::class);
}
```

---

### **PHASE 4: TESTING (30 minutes)**

#### **Test 1: Save FCM Token**

```bash
# Using curl or Postman
curl -X POST http://localhost:8000/api/fcm-token \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"fcm_token":"fK9xm3Qr7sT4..."}'
```

---

#### **Test 2: Send Manual Notification**

```bash
php artisan tinker
```

```php
$user = \App\Models\User::first();
$service = new \App\Services\NotificationService();

// Create test order
$order = new stdClass();
$order->id = 999;
$order->table_number = '5';
$order->total = 150000;
$order->order_type = 'dine-in';

$service->sendNewOrderNotification($user, $order);
```

---

#### **Test 3: Check Logs**

```bash
tail -f storage/logs/laravel.log
```

---

## ✅ COMPLETION CHECKLIST

- [ ] User model updated (fcm_token in fillable)
- [ ] Firebase credentials downloaded
- [ ] Firebase config published
- [ ] NotificationService created
- [ ] Migrations run
- [ ] Models created (Notification, NotificationPreference)
- [ ] API Controller created
- [ ] Routes added
- [ ] OrderObserver created & registered
- [ ] SendLowStockNotification updated
- [ ] Tested FCM token save
- [ ] Tested manual notification
- [ ] Tested auto-send (create order)

---

## 🎉 SUCCESS CRITERIA

**Backend is ready when:**
- ✅ FCM token saves to database
- ✅ Manual notification sends successfully
- ✅ New order triggers notification
- ✅ Low stock triggers notification
- ✅ User preferences save correctly
- ✅ No errors in logs

---

## 📊 SUMMARY

**What You Have:**
- 50% complete (database, endpoint, package)

**What To Do:**
- 2.5 hours implementation
- Copy-paste code from this guide
- Test each phase

**Result:**
- Complete notification system
- Auto-send on events
- User preferences
- Full integration with Flutter!

---

**Next Step:** Start with Phase 1 (30 minutes)

Good luck! 🚀
