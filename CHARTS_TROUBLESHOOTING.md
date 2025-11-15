# 🔧 CHARTS TROUBLESHOOTING GUIDE

## 🐛 **ISSUE: Blank White Charts**

**Symptoms:**
- Chart containers visible
- But charts are empty/blank white
- No visible error in UI

---

## ✅ **FIXES APPLIED**

### **Fix 1: Parameter Order Error** ✅
**Problem:** `getSalesTrend()` called with wrong parameter order
```php
// WRONG ❌
$trend = $service->getSalesTrend($startDate, $endDate, 'daily');

// CORRECT ✅
$trend = $service->getSalesTrend($tenantId, $startDate, $endDate, 'daily');
```

**Solution:** Added `$tenantId` as first parameter and format dates as strings

### **Fix 2: Filter Zero Amount Payments** ✅
**Problem:** Payment breakdown includes methods with 0 amount
```php
// Payment breakdown might have:
// [
//   {method: 'cash', amount: 0},    ← Causes issues
//   {method: 'qris', amount: 1000}
// ]
```

**Solution:** Filter out zero amounts
```php
$breakdown = collect($breakdown)
    ->filter(fn($item) => $item['amount'] > 0)
    ->values()
    ->toArray();
```

### **Fix 3: Added Console Debugging** ✅
**Added logs to track:**
- ✅ Chart initialization start
- ✅ Sales trend data structure
- ✅ Payment chart data structure
- ✅ Chart render success/failure
- ✅ Data availability warnings

---

## 🔍 **HOW TO DEBUG**

### **Step 1: Check Browser Console**
1. Open Reports page: `/admin/reports`
2. Press **F12** (open DevTools)
3. Go to **Console** tab
4. Look for messages:

**Expected Output:**
```
🔄 Initializing charts...
📊 Sales Trend Data: {labels: [...], data: [...]}
💳 Payment Chart Data: {labels: [...], data: [...]}
✅ Sales chart rendered
✅ Payment chart rendered
✅ Chart initialization complete
```

**If you see:**
```
⚠️ No sales trend data available
⚠️ No payment chart data available
```
**Then:** Data is missing or empty

### **Step 2: Check Data in Tinker**
```bash
php artisan tinker

# Test chart data availability
$user = \App\Models\User::find(YOUR_USER_ID);
$tenantId = $user->tenant_id;
$service = app(\App\Services\ReportService::class);

# Test sales trend
$trend = $service->getSalesTrend($tenantId, '2025-11-07', '2025-11-13', 'daily');
print_r($trend);

# Test daily summary
$summary = $service->getDailySummary($tenantId, '2025-11-13');
print_r($summary['payment_breakdown']);
```

### **Step 3: Check for JavaScript Errors**
In browser console, look for:
- ❌ `Uncaught ReferenceError: ApexCharts is not defined`
  - **Fix:** ApexCharts CDN not loaded
  - Check `<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>`

- ❌ `TypeError: Cannot read property 'destroy' of undefined`
  - **Fix:** Chart already destroyed or never created
  - Should be handled by our code now

---

## 📊 **DATA REQUIREMENTS**

### **For Sales Trend Chart:**
```php
$salesTrendData = [
    'labels' => ['07 Nov', '08 Nov', '09 Nov', ...],  // Must have data
    'data' => [1000000, 1500000, 1200000, ...]        // Must have numbers > 0
];
```

**Minimum:** 1 data point required

### **For Payment Chart:**
```php
$paymentChartData = [
    'labels' => ['QRIS', 'CASH'],                // At least 1 method
    'data' => [1000000, 500000],                 // At least 1 amount > 0
    'percentages' => [66.7, 33.3]                // Calculated percentages
];
```

**Minimum:** 1 payment method with amount > 0

---

## 🛠️ **COMMON ISSUES & SOLUTIONS**

### **Issue 1: No Data for Selected Date**
**Symptom:** Charts don't show
**Console:** `⚠️ No sales trend data available`

**Solution:**
1. Check if orders exist for that date
2. Run: `php artisan reports:generate-daily --date=2025-11-13`
3. Refresh page

### **Issue 2: All Payment Methods Show 0**
**Symptom:** Payment chart not rendering
**Console:** `⚠️ No payment chart data available`

**Solution:**
- Payment breakdown filtered to show only methods with amount > 0
- If all payments are 0, chart won't render (by design)
- Create orders with actual payments

### **Issue 3: ApexCharts Not Loading**
**Symptom:** Console error about ApexCharts undefined
**Console:** `Uncaught ReferenceError: ApexCharts is not defined`

**Solution:**
```blade
<!-- Make sure this is at TOP of reports.blade.php -->
<x-filament-panels::page>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    ...
```

### **Issue 4: Charts Don't Update on Date Change**
**Symptom:** Charts stuck with old data

**Solution:**
- Livewire hook already added: `Livewire.hook('message.processed')`
- Chart destruction already implemented
- Should work automatically
- Check console for re-initialization messages

---

## 🧪 **TEST CHECKLIST**

**Daily Report:**
- [ ] Open `/admin/reports`
- [ ] Select today's date
- [ ] Check console for initialization messages
- [ ] Verify sales trend chart shows
- [ ] Verify payment donut chart shows
- [ ] Change date → Charts should update
- [ ] Check console for re-init messages

**Period Report:**
- [ ] Switch to "Period" mode
- [ ] Select date range (last 7 days)
- [ ] Charts should show period data
- [ ] Sales trend should show multiple days

**No Data Scenario:**
- [ ] Select date with no orders
- [ ] Should show "Tidak ada data" message
- [ ] Charts should not render
- [ ] No JavaScript errors

---

## 📝 **DEBUGGING COMMANDS**

### **Check if daily summary exists:**
```bash
php artisan tinker
\App\Models\DailySummary::where('tenant_id', 3)
    ->where('date', '2025-11-13')
    ->first();
```

### **Generate daily summary manually:**
```bash
php artisan reports:generate-daily --date=2025-11-13
```

### **Check orders for date:**
```bash
php artisan tinker
\App\Models\Order::where('tenant_id', 3)
    ->whereDate('created_at', '2025-11-13')
    ->whereIn('status', ['paid', 'complete'])
    ->count();
```

### **Test ReportService directly:**
```bash
php artisan tinker
$service = app(\App\Services\ReportService::class);
$data = $service->getDailySummary(3, '2025-11-13');
print_r($data);
```

---

## ✅ **SUCCESS CRITERIA**

**Charts Working When:**
- ✅ Console shows: "Chart initialization complete"
- ✅ Sales trend chart visible with blue area
- ✅ Payment donut chart visible with colors
- ✅ Hover shows tooltips
- ✅ Charts update when date changes
- ✅ No JavaScript errors in console

**If Still Not Working:**
1. Check console logs
2. Verify data exists (tinker)
3. Check network tab for CDN load
4. Hard refresh (Ctrl+F5)
5. Clear browser cache

---

## 📞 **QUICK FIXES**

**Charts blank but data exists:**
```bash
# 1. Clear all caches
php artisan optimize:clear
php artisan view:clear

# 2. Hard refresh browser (Ctrl+F5)

# 3. Check console for errors
```

**Payment chart not showing:**
```bash
# Check if payment breakdown has non-zero values
php artisan tinker
$summary = \App\Models\DailySummary::where('date', '2025-11-13')->first();
dd($summary->payment_breakdown);
```

**Sales trend empty:**
```bash
# Regenerate daily summaries
php artisan reports:generate-daily --date=2025-11-13 --force
```

---

**Status:** ✅ All fixes applied  
**Console logs:** ✅ Added for debugging  
**Data filtering:** ✅ Zero amounts filtered  
**Error handling:** ✅ Complete
