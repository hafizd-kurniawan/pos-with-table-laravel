# 🎁 DISCOUNT, TAX & SERVICE CHARGE SYSTEM

## ✅ **FITUR BARU YANG SUDAH DIIMPLEMENTASIKAN**

Sistem lengkap untuk manage **Discount**, **Tax (PPN)**, dan **Service Charge** pada order customer di self-order web.

---

## 🎯 **OVERVIEW**

### **Flow Calculation:**
```
Cart Items (Rp 100,000)
    ↓
Apply Discount (-Rp 10,000) → Subtotal: Rp 90,000
    ↓
Apply Tax 11% (+Rp 9,900) → Rp 99,900
    ↓
Apply Service 5% (+Rp 4,500) → Total: Rp 104,400
```

---

## ⚙️ **1. SETTINGS PAGE (Admin)**

### **URL:**
```
http://your-domain/order-settings
```

### **Features:**
- ✅ **Enable/Disable Discount** (Toggle switch)
- ✅ **Enable/Disable Tax** (Toggle switch)
- ✅ **Enable/Disable Service Charge** (Toggle switch)

### **Default State:**
```
Discount: ❌ Disabled (OFF)
Tax: ✅ Enabled (ON) - 11%
Service Charge: ✅ Enabled (ON) - Ambil dari database
```

### **How to Access:**
1. Go to `/order-settings`
2. Toggle switches untuk enable/disable
3. Click **"💾 Save Settings"**
4. Settings applied immediately!

### **Database Storage:**
```sql
settings table:
- key: 'enable_discount', value: '0' or '1'
- key: 'enable_tax', value: '0' or '1'
- key: 'enable_service_charge', value: '0' or '1'
```

---

## 🛒 **2. CHECKOUT PAGE (Customer)**

### **URL:**
```
http://your-domain/order/{table-number}/checkout
```

### **New Elements:**

#### **A. Discount Dropdown (if enabled)**
```html
<select name="discount_id">
  <option value="">No Discount</option>
  <option value="1">🎁 Weekend Promo (20% OFF)</option>
  <option value="2">🎁 Member Discount (Rp 50,000 OFF)</option>
</select>
```

**Source:** Active discounts from `discounts` table
**Filter:** Only `status = 'active'` and `expired_date >= today`

#### **B. Order Summary Breakdown**
```
Subtotal (3 items)          Rp 100,000
Discount                    - Rp 20,000  ← Hijau (if selected)
Subtotal After Discount     Rp 80,000
Tax (11%)                   + Rp 8,800
Service Charge (5%)         + Rp 4,000
────────────────────────────────────────
Total Payment               Rp 92,800
```

### **Dynamic Calculation:**
- ✅ **Real-time update** saat pilih discount
- ✅ **Auto-calculate** tax & service charge
- ✅ **JavaScript frontend calculation** (instant feedback)
- ✅ **Backend validation** (actual calculation)

---

## 💾 **3. DATABASE STRUCTURE**

### **New Columns in `orders` table:**
```sql
discount_id                  BIGINT NULL (FK to discounts.id)
discount_amount              DECIMAL(10,2) DEFAULT 0
subtotal                     DECIMAL(10,2) DEFAULT 0
tax_percentage               DECIMAL(5,2) DEFAULT 0
tax_amount                   DECIMAL(10,2) DEFAULT 0
service_charge_percentage    DECIMAL(5,2) DEFAULT 0
service_charge_amount        DECIMAL(10,2) DEFAULT 0
total_amount                 DECIMAL(10,2) (final total)
```

### **Example Order Record:**
```json
{
  "code": "JG-251112-0001",
  "discount_id": 5,
  "discount_amount": 20000.00,
  "subtotal": 80000.00,
  "tax_percentage": 11.00,
  "tax_amount": 8800.00,
  "service_charge_percentage": 5.00,
  "service_charge_amount": 4000.00,
  "total_amount": 92800.00
}
```

---

## 🔧 **4. HELPER FUNCTIONS**

### **A. Check if Feature Enabled**
```php
is_discount_enabled()         // Returns true/false
is_tax_enabled()              // Returns true/false
is_service_charge_enabled()   // Returns true/false
```

### **B. Get Values**
```php
tax_percentage()                // Returns "11" (from settings)
get_active_service_charge()     // Returns service charge % from database
```

### **C. Usage in Blade:**
```blade
@if(is_discount_enabled())
    <select name="discount_id">...</select>
@endif

@if(is_tax_enabled())
    <div>Tax ({{ tax_percentage() }}%)</div>
@endif

@if(is_service_charge_enabled())
    <div>Service Charge</div>
@endif
```

---

## 🎨 **5. CALCULATION LOGIC**

### **Backend Method:**
```php
calculateOrderTotals($cart, $discountId = null)
```

### **Step-by-Step:**
```php
// 1. Calculate items subtotal
$itemsSubtotal = sum(price × qty) // Rp 100,000

// 2. Apply discount (if enabled & selected)
if (is_discount_enabled() && $discountId) {
    $discount = Discount::find($discountId);
    if ($discount->type == 'percentage') {
        $discountAmount = $itemsSubtotal × (value / 100)
    } else {
        $discountAmount = min(value, $itemsSubtotal)
    }
} // -Rp 20,000

// 3. Subtotal after discount
$subtotal = $itemsSubtotal - $discountAmount // Rp 80,000

// 4. Calculate tax (if enabled)
if (is_tax_enabled()) {
    $taxAmount = $subtotal × (tax_percentage / 100)
} // +Rp 8,800

// 5. Calculate service charge (if enabled)
if (is_service_charge_enabled()) {
    $serviceAmount = $subtotal × (service_percentage / 100)
} // +Rp 4,000

// 6. Total
$totalAmount = $subtotal + $taxAmount + $serviceAmount // Rp 92,800
```

---

## 💳 **6. MIDTRANS INTEGRATION**

### **Item Details Structure:**
```php
[
    // Cart items
    ["id" => 1, "name" => "Nasi Goreng", "price" => 25000, "qty" => 2],
    ["id" => 2, "name" => "Es Teh", "price" => 5000, "qty" => 10],
    
    // Discount (as negative price)
    ["id" => "discount", "name" => "Discount", "price" => -20000, "qty" => 1],
    
    // Tax
    ["id" => "tax", "name" => "Tax (11%)", "price" => 8800, "qty" => 1],
    
    // Service Charge
    ["id" => "service", "name" => "Service Charge (5%)", "price" => 4000, "qty" => 1]
]
```

### **Transaction Details:**
```php
[
    "order_id" => "JG-251112-0001",
    "gross_amount" => 92800 // Total final
]
```

---

## 🧪 **7. TESTING SCENARIOS**

### **Test 1: Enable All Features**
```
1. Go to /order-settings
2. Enable: ✅ Discount, ✅ Tax, ✅ Service
3. Save
4. Go to checkout
5. Expected: Discount dropdown visible, tax & service calculated
```

### **Test 2: Disable Discount**
```
1. Go to /order-settings
2. Disable: ❌ Discount
3. Save
4. Go to checkout
5. Expected: No discount dropdown, only tax & service
```

### **Test 3: Apply Percentage Discount**
```
1. Create discount: "20% OFF"
2. Cart total: Rp 100,000
3. Select discount at checkout
4. Expected:
   - Discount: -Rp 20,000
   - Subtotal: Rp 80,000
   - Tax 11%: +Rp 8,800
   - Service 5%: +Rp 4,000
   - Total: Rp 92,800
```

### **Test 4: Apply Fixed Discount**
```
1. Create discount: "Rp 50,000 OFF"
2. Cart total: Rp 100,000
3. Select discount at checkout
4. Expected:
   - Discount: -Rp 50,000
   - Subtotal: Rp 50,000
   - Tax 11%: +Rp 5,500
   - Service 5%: +Rp 2,500
   - Total: Rp 58,000
```

### **Test 5: Complete Order Flow**
```
1. Customer add items to cart (Rp 100,000)
2. Go to checkout
3. Select discount "20% OFF"
4. Fill customer info
5. Click "Pay"
6. Check order in database:
   ✅ discount_id: saved
   ✅ discount_amount: 20,000
   ✅ subtotal: 80,000
   ✅ tax_amount: 8,800
   ✅ service_charge_amount: 4,000
   ✅ total_amount: 92,800
7. QRIS page shows correct total
8. Midtrans receives itemized breakdown
```

---

## 📋 **8. CONFIGURATION**

### **Discount Management:**
```
Admin Panel → Discounts
- Create new discounts
- Set type: Percentage or Fixed
- Set value
- Set expired date
- Set status: Active/Inactive
```

### **Tax Configuration:**
```
Admin Panel → Settings
- Key: tax_percentage
- Value: 11 (default)
```

### **Service Charge Configuration:**
```
Admin Panel → Taxes
- Create tax record
- Type: "layanan"
- Value: 5 (percentage)
- Status: Active
```

---

## 🎯 **9. KEY FEATURES**

### **✅ Admin Can:**
- Enable/disable each feature independently
- Manage discounts (CRUD)
- Set tax percentage globally
- Set service charge percentage
- View complete order breakdown in database

### **✅ Customer Can:**
- Select discount from active discounts (if enabled)
- See real-time calculation breakdown
- Know exact amounts before payment
- Receive itemized receipt

### **✅ System Automatically:**
- Calculate discount based on type (% or fixed)
- Apply tax after discount
- Apply service charge after tax
- Store all calculation details
- Send breakdown to payment gateway

---

## 🚀 **10. QUICK START**

### **Step 1: Access Settings**
```
http://your-domain/order-settings
```

### **Step 2: Configure**
```
✅ Enable Discount
✅ Enable Tax (11%)
✅ Enable Service (5%)
Save
```

### **Step 3: Create Discount**
```
Admin Panel → Discounts → Create
Name: Weekend Promo
Type: Percentage
Value: 20
Status: Active
Save
```

### **Step 4: Test Order**
```
1. Open /order/{table-number}
2. Add items
3. Go to checkout
4. Select "Weekend Promo" discount
5. See breakdown:
   - Items: Rp 100,000
   - Discount: -Rp 20,000
   - Tax: +Rp 8,800
   - Service: +Rp 4,000
   - Total: Rp 92,800
6. Complete payment
7. Check database for complete record
```

---

## 📊 **11. DATABASE QUERIES**

### **Get Orders with Discount:**
```sql
SELECT * FROM orders 
WHERE discount_id IS NOT NULL 
ORDER BY created_at DESC;
```

### **Calculate Total Discounts Given:**
```sql
SELECT SUM(discount_amount) as total_discounts
FROM orders 
WHERE created_at >= '2025-11-01';
```

### **Most Used Discount:**
```sql
SELECT discount_id, d.name, COUNT(*) as usage_count
FROM orders o
JOIN discounts d ON o.discount_id = d.id
GROUP BY discount_id
ORDER BY usage_count DESC;
```

---

## 🎉 **STATUS: PRODUCTION READY**

**All Features Implemented:**
- ✅ Settings page with toggles
- ✅ Discount dropdown at checkout
- ✅ Real-time calculation
- ✅ Backend validation
- ✅ Database storage
- ✅ Midtrans integration
- ✅ Helper functions
- ✅ Responsive UI

**Files Created/Modified:**
- ✅ Migration: `2025_11_12_030421_add_discount_tax_to_orders_table.php`
- ✅ Controller: `App\Http\Controllers\Web\OrderSettingController.php`
- ✅ View: `resources/views/order-settings/index.blade.php`
- ✅ View: `resources/views/order/checkout.blade.php` (updated)
- ✅ Controller: `App\Http\Controllers\OrderController.php` (updated)
- ✅ Model: `App\Models\Order.php` (updated)
- ✅ Helpers: `app/helpers.php` (updated)
- ✅ Routes: `routes/web.php` (updated)

**Ready to Use!** 🚀
