# ⚡ QUICK START: Discount, Tax & Service Charge

## 🎯 **3-MINUTE SETUP**

### **Step 1: Access Settings (30 seconds)**
```
http://your-domain/order-settings
```

### **Step 2: Configure Features (1 minute)**
```
🎁 Discount:        ❌ → ✅ (Enable jika ingin gunakan)
🧾 Tax (PPN):       ✅ (Already ON - 11%)
💼 Service Charge:  ✅ (Already ON)

Click: 💾 Save Settings
```

### **Step 3: Create Test Discount (1 minute)**
```
Admin Panel → Discounts → Create New

Name: Weekend Promo 🎉
Type: Percentage
Value: 20
Expired Date: (1 month dari now)
Status: Active

Save ✅
```

### **Step 4: Test Order (30 seconds)**
```
1. Go to: /order/1 (or any table number)
2. Add some items
3. Click: "View Cart"
4. Click: "Checkout"
5. Select discount: "Weekend Promo"
6. See calculation update real-time ✨
7. Complete payment
8. Success! 🎉
```

---

## 📊 **CALCULATION EXAMPLE**

### **Scenario: Order Rp 100,000 dengan Weekend Promo 20%**

```
┌─────────────────────────────────────────┐
│ CHECKOUT SUMMARY                        │
├─────────────────────────────────────────┤
│ Subtotal (3 items)     Rp    100,000   │
│ Discount (20%)         Rp    -20,000   │ ← Hijau
│ Subtotal After         Rp     80,000   │
│ Tax (11%)              Rp     +8,800   │
│ Service (5%)           Rp     +4,000   │
├─────────────────────────────────────────┤
│ TOTAL PAYMENT          Rp     92,800   │ ← Bold
└─────────────────────────────────────────┘
```

---

## 🎨 **CUSTOMER EXPERIENCE**

### **Checkout Page Features:**
```
✅ Discount dropdown (jika enabled)
✅ Real-time calculation
✅ Clear price breakdown
✅ All charges transparent
✅ Final total highlighted
```

### **Discount Selection:**
```html
<select name="discount_id">
  <option value="">No Discount</option>
  <option value="1">🎁 Weekend Promo (20% OFF)</option>
  <option value="2">🎁 Member Discount (Rp 50,000 OFF)</option>
  <option value="3">🎁 First Order (10% OFF)</option>
</select>
```

**Note:** Dropdown hanya muncul jika:
1. Discount feature di-enable di settings
2. Ada discount yang status = active
3. Discount belum expired

---

## ⚙️ **SETTINGS CONTROL**

### **URL:**
```
/order-settings
```

### **Toggle Options:**

#### **🎁 Discount System**
```
Enabled:  Customer bisa pilih discount di checkout
Disabled: No discount dropdown, calculation skip discount
```

#### **🧾 Tax (PPN)**
```
Enabled:  Auto-calculate 11% dari subtotal
Disabled: No tax applied
```

#### **💼 Service Charge**
```
Enabled:  Auto-calculate X% dari subtotal (dari database)
Disabled: No service charge
```

---

## 💾 **DATABASE RECORD**

### **Order dengan Discount:**
```json
{
  "id": 123,
  "code": "JG-251112-0001",
  "table_id": 5,
  "customer_name": "John Doe",
  
  "discount_id": 1,
  "discount_amount": 20000.00,
  
  "subtotal": 80000.00,
  
  "tax_percentage": 11.00,
  "tax_amount": 8800.00,
  
  "service_charge_percentage": 5.00,
  "service_charge_amount": 4000.00,
  
  "total_amount": 92800.00,
  "status": "pending"
}
```

**Benefit:** Complete audit trail untuk semua calculation!

---

## 🧪 **TESTING CHECKLIST**

### ✅ **Test 1: Enable All (2 min)**
```
1. /order-settings
2. Enable: Discount, Tax, Service
3. Save
4. /order/1
5. Add items → Checkout
6. Discount dropdown visible? ✅
7. Tax calculated? ✅
8. Service calculated? ✅
```

### ✅ **Test 2: Disable Discount (1 min)**
```
1. /order-settings
2. Disable: Discount
3. Save
4. /order/1 → Checkout
5. No discount dropdown? ✅
6. Only tax & service? ✅
```

### ✅ **Test 3: Apply Discount (2 min)**
```
1. Cart: Rp 100,000
2. Select: Weekend Promo 20%
3. Expected:
   - Discount: -Rp 20,000 ✅
   - Subtotal: Rp 80,000 ✅
   - Tax: +Rp 8,800 ✅
   - Service: +Rp 4,000 ✅
   - Total: Rp 92,800 ✅
4. Pay & check database ✅
```

### ✅ **Test 4: Complete Flow (3 min)**
```
1. Create discount: "Test 50%" (percentage, 50%)
2. Cart items: Rp 200,000
3. Apply discount → Rp 100,000 saved
4. Complete payment
5. Check order record in database:
   ✅ discount_id saved
   ✅ discount_amount = 100,000
   ✅ subtotal = 100,000
   ✅ tax_amount calculated
   ✅ service_charge_amount calculated
   ✅ total_amount correct
```

---

## 🚨 **TROUBLESHOOTING**

### **Issue 1: Discount tidak muncul**
```
Check:
1. Settings enabled? /order-settings
2. Discount status = active?
3. Discount belum expired?
4. Cache cleared? php artisan optimize:clear
```

### **Issue 2: Calculation salah**
```
Check:
1. Tax percentage di Settings (default: 11%)
2. Service charge di Taxes table (type: layanan)
3. Discount type & value correct?
4. Check logs: storage/logs/laravel.log
```

### **Issue 3: Settings tidak save**
```
Fix:
1. php artisan optimize:clear
2. Check database: SELECT * FROM settings;
3. Manual insert jika perlu
```

---

## 📂 **FILE LOCATIONS**

### **Backend:**
```
Controllers:
- app/Http/Controllers/Web/OrderSettingController.php
- app/Http/Controllers/OrderController.php (updated)

Models:
- app/Models/Order.php (updated)
- app/Models/Setting.php
- app/Models/Discount.php
- app/Models/Tax.php

Helpers:
- app/helpers.php (updated)
```

### **Frontend:**
```
Views:
- resources/views/order-settings/index.blade.php
- resources/views/order/checkout.blade.php (updated)

Routes:
- routes/web.php (updated)
```

### **Database:**
```
Migrations:
- 2025_11_12_030421_add_discount_tax_to_orders_table.php

Tables Modified:
- orders (added 7 columns)
- settings (added 3 records)
```

---

## 💡 **PRO TIPS**

### **1. Create Multiple Discounts:**
```
- Weekday Promo (20% OFF) Mon-Fri
- Weekend Special (10% OFF) Sat-Sun
- Member Discount (Rp 50,000 OFF) for members
- First Order (15% OFF) for new customers
```

### **2. Set Expiry Dates:**
```
Flash sale: Expired = 24 hours
Monthly promo: Expired = end of month
Seasonal: Expired = end of season
```

### **3. Monitor Usage:**
```sql
-- Most popular discount
SELECT discount_id, COUNT(*) as usage
FROM orders
WHERE discount_id IS NOT NULL
GROUP BY discount_id
ORDER BY usage DESC;

-- Total savings given
SELECT SUM(discount_amount) as total_savings
FROM orders
WHERE created_at >= '2025-11-01';
```

### **4. A/B Testing:**
```
- Create 2 similar discounts
- Track which one gets more usage
- Keep the best performing one
```

---

## 🎉 **READY TO USE!**

**All Features:**
- ✅ Settings page functional
- ✅ Discount dropdown working
- ✅ Real-time calculation active
- ✅ Database recording complete
- ✅ Midtrans integration done
- ✅ Cache optimized

**Access Points:**
- Settings: `/order-settings`
- Test Order: `/order/1`
- Admin Discounts: `/admin/discounts`

**Start using now!** 🚀

---

## 📞 **NEED HELP?**

**Check:**
1. `ORDER_DISCOUNT_TAX_GUIDE.md` - Full documentation
2. `storage/logs/laravel.log` - Error logs
3. Database records - Verify data saved

**Common Commands:**
```bash
# Clear cache
php artisan optimize:clear

# Check settings
php artisan tinker
> Setting::all();

# Test discount
php artisan tinker
> Discount::active()->get();
```

**Happy selling! 💰**
