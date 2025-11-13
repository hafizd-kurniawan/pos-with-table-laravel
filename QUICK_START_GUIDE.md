# 🚀 QUICK START GUIDE - REPORTING SYSTEM

## ⚡ 5-MINUTE SETUP

### **1. Run Migrations** (Already done ✅)
```bash
cd /home/biru/Downloads/gabungan/laravel
php artisan migrate --force
```

### **2. Clear Cache**
```bash
php artisan optimize:clear
```

### **3. Test Command**
```bash
php artisan reports:generate-daily --date=2025-11-13
```

### **4. Access Web Dashboard**
```
URL: http://YOUR_DOMAIN/admin/reports
Login with your admin credentials
```

---

## 📱 **QUICK API TEST**

### **Test with cURL**
```bash
# Get daily summary
curl "http://localhost:8000/api/reports/daily-summary?date=2025-11-13" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Tenant-ID: 3"

# Download PDF
curl "http://localhost:8000/api/reports/export/daily-pdf?date=2025-11-13" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Tenant-ID: 3" \
  --output laporan.pdf
```

### **Test with Tinker**
```bash
php artisan tinker

# Test service
$service = app(\App\Services\ReportService::class);
$summary = $service->getDailySummary(3, '2025-11-13');
print_r($summary);

# Test export
$controller = app(\App\Http\Controllers\Api\ReportController::class);
```

---

## 🎯 **QUICK REFERENCE**

### **All 14 API Endpoints**
```
# Basic (4)
GET  /api/reports/daily-summary
GET  /api/reports/period-summary
GET  /api/reports/top-products
POST /api/reports/generate-daily-summary

# Visualization (4)
GET /api/reports/sales-trend
GET /api/reports/category-performance
GET /api/reports/hourly-breakdown
GET /api/reports/payment-trends

# Export (4)
GET /api/reports/export/daily-pdf
GET /api/reports/export/daily-excel
GET /api/reports/export/period-pdf
GET /api/reports/export/period-excel

# Legacy (2)
GET /api/reports/summary
GET /api/reports/product-sales
```

### **Command Options**
```bash
# Default (yesterday)
php artisan reports:generate-daily

# Specific date
php artisan reports:generate-daily --date=2025-11-13

# Specific tenant
php artisan reports:generate-daily --tenant=3

# Force regenerate
php artisan reports:generate-daily --date=2025-11-13 --force
```

---

## 🔧 **TROUBLESHOOTING**

### **Issue: No data showing**
```bash
# Check if there are orders
php artisan tinker
> \App\Models\Order::withoutGlobalScope('tenant')->count()

# Generate cache manually
php artisan reports:generate-daily --date=2025-11-13 --force
```

### **Issue: PDF not generating**
```bash
# Clear cache
php artisan optimize:clear

# Check permissions
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

### **Issue: Tenant ID error**
```
Add header: X-Tenant-ID: 3
Or login with tenant user first
```

---

## 📊 **SAMPLE RESPONSES**

### **Daily Summary**
```json
{
  "success": true,
  "data": {
    "date": "2025-11-13",
    "summary": {
      "total_orders": 2,
      "net_sales": 1866600.00
    }
  }
}
```

### **Sales Trend**
```json
{
  "success": true,
  "data": [
    {
      "period": "2025-11-13",
      "label": "13 Nov",
      "total_sales": 1866600.00
    }
  ]
}
```

---

## 🎊 **YOU'RE READY!**

System is 100% functional and ready for:
- ✅ Web dashboard usage
- ✅ Flutter app integration
- ✅ PDF/Excel exports
- ✅ Automated daily reports
- ✅ Multi-tenant operations

**For detailed documentation, see:**
- `REPORTING_SYSTEM_COMPLETE.md` - Complete overview
- `REPORTING_API_DOCS.md` - API reference
- `REPORTING_PHASE_2_COMPLETE.md` - Visualization guide
