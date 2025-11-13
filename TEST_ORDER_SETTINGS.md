# 🧪 TEST ORDER SETTINGS ACCESS

## ✅ **STATUS CHECKLIST:**

### **1. Files Created:**
- ✅ Controller: `app/Http/Controllers/Web/OrderSettingController.php`
- ✅ View: `resources/views/order-settings/index.blade.php`
- ✅ Route: Added to `routes/web.php`
- ✅ Migration: Run successfully
- ✅ Settings: Seeded to database

### **2. Route Registered:**
```
GET    /order-settings              → order-settings.index
PUT    /order-settings/update       → order-settings.update
```

### **3. Cache Cleared:**
```bash
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

---

## 🚀 **HOW TO ACCESS:**

### **Method 1: Direct URL**
```
http://your-domain/order-settings
http://localhost:8000/order-settings
http://192.168.1.4:8000/order-settings
```

### **Method 2: From Laravel Server**
```bash
cd /home/biru/Downloads/gabungan/laravel
php artisan serve --host=0.0.0.0 --port=8000
```

Then open browser:
```
http://localhost:8000/order-settings
```

### **Method 3: Add Link to Welcome Page**
Add this to `resources/views/welcome.blade.php` or create navigation:

```html
<a href="{{ route('order-settings.index') }}" class="btn">
    ⚙️ Order Settings
</a>
```

---

## 🔍 **TROUBLESHOOTING:**

### **If you get 404 Not Found:**

#### **Step 1: Check Route**
```bash
cd /home/biru/Downloads/gabungan/laravel
php artisan route:list | grep order-settings
```

**Expected Output:**
```
GET|HEAD   order-settings
PUT        order-settings/update
```

#### **Step 2: Clear Cache Again**
```bash
php artisan optimize:clear
```

#### **Step 3: Check Controller Exists**
```bash
ls -la app/Http/Controllers/Web/OrderSettingController.php
```

#### **Step 4: Check View Exists**
```bash
ls -la resources/views/order-settings/index.blade.php
```

#### **Step 5: Test Route Directly**
```bash
php artisan route:list | grep OrderSetting
```

### **If you get Error:**

#### **Check Logs:**
```bash
tail -50 storage/logs/laravel.log
```

#### **Permission Issue:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 🎯 **QUICK ACCESS URLS:**

```
Order Settings Page:
→ http://localhost:8000/order-settings

Test Order (Table 1):
→ http://localhost:8000/order/1

Admin Panel (Filament):
→ http://localhost:8000/admin

Table Management:
→ http://localhost:8000/table-management
```

---

## 📋 **VERIFICATION STEPS:**

### **1. Start Server:**
```bash
cd /home/biru/Downloads/gabungan/laravel
php artisan serve
```

### **2. Open Browser:**
```
http://localhost:8000/order-settings
```

### **3. Expected Result:**
- ✅ Page loads successfully
- ✅ Show 3 toggle switches (Discount, Tax, Service)
- ✅ Each toggle works
- ✅ Save button functional

### **4. Test Functionality:**
1. Toggle Discount → ON
2. Click "Save Settings"
3. See success message: "✅ Order settings updated successfully!"
4. Refresh page → Toggle should stay ON
5. Go to `/order/1` → Add items → Checkout
6. Should see discount dropdown

---

## 🛠️ **IF STILL NOT WORKING:**

### **Option A: Recreate Route (Manual Check)**
Open `routes/web.php` and verify:
```php
// Order Settings Management (Discount, Tax, Service Charge)
Route::prefix('order-settings')->name('order-settings.')->group(function () {
    Route::get('/', [App\Http\Controllers\Web\OrderSettingController::class, 'index'])->name('index');
    Route::put('/update', [App\Http\Controllers\Web\OrderSettingController::class, 'update'])->name('update');
});
```

### **Option B: Test Controller Directly**
```bash
php artisan tinker
> app(App\Http\Controllers\Web\OrderSettingController::class)->index();
```

### **Option C: Debug Route**
Add this to your `routes/web.php` temporarily:
```php
Route::get('/test-order-settings', function() {
    return "Order Settings Route is Working!";
});
```

Then access: `http://localhost:8000/test-order-settings`

### **Option D: Check Web Server**
If using Apache/Nginx, make sure `.htaccess` or nginx config is correct.

---

## ✅ **CONFIRMATION:**

Once you can access `/order-settings`, you should see:

```
┌────────────────────────────────────┐
│ ⚙️ Order Settings                  │
├────────────────────────────────────┤
│                                    │
│ 🎁 Discount System      [Toggle]  │
│                                    │
│ 🧾 Tax (PPN)          [Toggle]  │
│                                    │
│ 💼 Service Charge      [Toggle]  │
│                                    │
│             [💾 Save Settings]     │
└────────────────────────────────────┘
```

---

## 📞 **STILL HAVING ISSUES?**

**Run this diagnostic:**
```bash
# Full diagnostic
cd /home/biru/Downloads/gabungan/laravel

echo "=== Route Check ==="
php artisan route:list | grep order-settings

echo "=== File Check ==="
ls -la app/Http/Controllers/Web/OrderSettingController.php
ls -la resources/views/order-settings/index.blade.php

echo "=== Settings Check ==="
php artisan tinker --execute="App\Models\Setting::where('key', 'like', 'enable_%')->get();"

echo "=== Clear Everything ==="
php artisan optimize:clear

echo "=== Test Server ==="
php artisan serve --host=0.0.0.0 --port=8000
```

**Then test:** `http://localhost:8000/order-settings`

---

**Ready to test!** 🚀
