# ✅ QUICK TESTING GUIDE - Reports Page

## 🎯 **QUICK TEST (5 Minutes)**

### **Test 1: Access Reports Page**
```
URL: http://192.168.1.4:8000/admin/reports
Expected: Page loads without errors
```

### **Test 2: Check Summary Cards**
**Look for 4 colored cards at top:**
- [ ] 🔵 Blue card showing "Total Order"
- [ ] 🟢 Green card showing "Penjualan Kotor"
- [ ] 🟠 Orange card showing "Total Diskon"
- [ ] 🟣 Purple card showing "Penjualan Bersih"

**Status:** ✅ PASS if all 4 cards are colorful

### **Test 3: Check Charts**
**Look below summary cards:**
- [ ] 📈 Sales Trend Chart (blue area chart)
- [ ] 💳 Payment Method Chart (colorful donut)

**Actions:**
- Hover over charts → Should show tooltips
- Change date → Charts should update

**Status:** ✅ PASS if both charts visible & interactive

### **Test 4: Check Export Buttons**
**Look at top right:**
- [ ] Red "Export PDF" button
- [ ] Green "Export Excel" button

**Actions:**
- Click Export PDF → File should download
- Click Export Excel → File should download

**Status:** ✅ PASS if both downloads work

### **Test 5: Check Breakdown Sections**
**Scroll down to find:**
- [ ] 💰 Rincian Diskon (Discount breakdown)
- [ ] 🧾 Rincian Pajak & Biaya (Tax breakdown)

**Check:**
- Discount shows percentage badge
- Tax shows blue card (PPN)
- Service shows green card

**Status:** ✅ PASS if both sections visible with data

---

## 🧪 **COMPREHENSIVE TEST (15 Minutes)**

### **Daily Report Test:**

**Step 1: Select Date**
```
1. Ensure "Harian" (Daily) is selected
2. Pick today's date (2025-11-13)
3. Click or wait for auto-load
```

**Step 2: Verify Data Shown**
- [ ] 4 summary cards show numbers
- [ ] Sales trend chart visible
- [ ] Payment donut chart visible
- [ ] Revenue breakdown section
- [ ] Payment method table
- [ ] Discount breakdown
- [ ] Tax breakdown
- [ ] Top 10 products table

**Step 3: Test Interactivity**
- [ ] Hover charts → Tooltips appear
- [ ] Change date → Data updates
- [ ] Export PDF → Download works
- [ ] Export Excel → Download works

**Step 4: Check Console**
```
Press F12 → Console tab
Expected logs:
- 🔄 Initializing charts...
- 📊 Sales Trend Data: {...}
- 💳 Payment Chart Data: {...}
- ✅ Sales chart rendered
- ✅ Payment chart rendered
- ✅ Chart initialization complete
```

**Status:** ✅ PASS if all items checked

---

### **Period Report Test:**

**Step 1: Switch to Period**
```
1. Click "Periode" dropdown
2. Select start date: 7 days ago
3. Select end date: Today
4. Wait for data load
```

**Step 2: Verify Period Data**
- [ ] 4 summary cards updated
- [ ] Growth card shows percentage (up/down)
- [ ] Sales trend shows multiple days
- [ ] Payment chart updated
- [ ] Comparison section visible
- [ ] Period summary correct

**Step 3: Test Exports**
- [ ] Export PDF includes period data
- [ ] Export Excel includes period data
- [ ] Filenames show date range

**Status:** ✅ PASS if period data displays correctly

---

### **Mobile Responsive Test:**

**Step 1: Resize Browser**
```
1. Press F12 (DevTools)
2. Click device toolbar icon
3. Select "iPhone 12 Pro" or similar
```

**Step 2: Check Layout**
- [ ] Cards stack vertically
- [ ] Charts resize properly
- [ ] Export buttons visible
- [ ] Tables scroll horizontally
- [ ] Text readable

**Status:** ✅ PASS if mobile-friendly

---

### **Dark Mode Test:**

**Step 1: Enable Dark Mode**
```
1. Click profile/settings
2. Toggle dark mode
3. Return to Reports page
```

**Step 2: Check Appearance**
- [ ] Background dark
- [ ] Text visible
- [ ] Cards contrast good
- [ ] Charts visible
- [ ] All sections readable

**Status:** ✅ PASS if dark mode works

---

## 🐛 **ERROR SCENARIOS**

### **Test: No Data for Date**

**Action:**
```
Select a date with no orders (e.g., future date)
```

**Expected:**
- Yellow warning message
- "Tidak ada data untuk tanggal ini"
- No charts shown
- No errors in console

**Status:** ✅ PASS if handled gracefully

### **Test: Invalid Date Range**

**Action:**
```
Period mode: End date before start date
```

**Expected:**
- Validation error or auto-correct
- No crash
- Error message clear

**Status:** ✅ PASS if validated

### **Test: Export with No Data**

**Action:**
```
Select date with no data
Click Export PDF/Excel
```

**Expected:**
- Notification: "Tidak ada data untuk diekspor"
- No download starts
- No console errors

**Status:** ✅ PASS if error shown

---

## 📊 **PERFORMANCE TEST**

### **Test: Large Date Range**

**Action:**
```
Select period: 30 days range
```

**Expected:**
- Page loads in < 3 seconds
- Charts render smoothly
- No browser freeze
- All data displays

**Status:** ✅ PASS if performant

### **Test: Multiple Date Changes**

**Action:**
```
Change date 5 times rapidly
```

**Expected:**
- Charts update each time
- No memory leaks
- Console shows re-initialization
- No errors

**Status:** ✅ PASS if stable

---

## ✅ **ACCEPTANCE CRITERIA**

### **Minimum Requirements:**
- [x] Page loads without errors
- [x] 4 summary cards show colored gradients
- [x] 2 charts visible and interactive
- [x] 2 export buttons functional
- [x] Discount & tax sections visible
- [x] Console shows no errors

### **Full Requirements:**
- [x] Daily report works completely
- [x] Period report works completely
- [x] Charts update on date change
- [x] Exports download successfully
- [x] Mobile responsive
- [x] Dark mode compatible
- [x] Error handling works
- [x] Performance acceptable

---

## 🎯 **QUICK PASS/FAIL**

### **PASS Criteria:**
✅ All 4 summary cards colorful  
✅ Both charts visible  
✅ Export buttons work  
✅ Breakdown sections show data  
✅ Console shows success messages  
✅ No errors anywhere  

### **FAIL Criteria:**
❌ Any cards blank white  
❌ Charts not rendering  
❌ Export buttons broken  
❌ JavaScript errors in console  
❌ Data not loading  
❌ Page crashes  

---

## 📞 **TROUBLESHOOTING DURING TEST**

### **If charts blank:**
```bash
# 1. Check console for errors
# 2. Regenerate data
php artisan reports:generate-daily --date=2025-11-13

# 3. Clear caches
php artisan optimize:clear
php artisan view:clear

# 4. Hard refresh browser
Ctrl + Shift + R
```

### **If export fails:**
```bash
# Check dependencies
composer show | grep -E 'dompdf|excel'

# Should see:
# barryvdh/laravel-dompdf
# maatwebsite/excel
```

### **If data missing:**
```bash
# Check orders exist
php artisan tinker
\App\Models\Order::whereDate('created_at', '2025-11-13')->count();

# Should return > 0
```

---

## 🎊 **FINAL CHECKLIST**

**Before Marking Complete:**
- [ ] Tested on Chrome
- [ ] Tested on Firefox/Safari
- [ ] Tested on mobile
- [ ] Tested dark mode
- [ ] Tested with no data
- [ ] Tested exports
- [ ] No console errors
- [ ] Performance good
- [ ] Screenshots taken
- [ ] Ready for production

**If ALL checked:** ✅ **SYSTEM READY FOR PRODUCTION USE!**

---

**Testing Time:** 15-20 minutes  
**Difficulty:** Easy  
**Prerequisites:** Admin access + test data  
**Tools Needed:** Browser + DevTools
