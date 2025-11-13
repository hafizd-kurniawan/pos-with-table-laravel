# 📊 REPORTING API DOCUMENTATION

## Overview
Comprehensive reporting system untuk POS multi-tenant dengan cache otomatis dan comparison features.

---

## 🔑 Authentication
All endpoints require authentication via Bearer token.

Headers:
```
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}  (optional, jika tidak ada akan ambil dari user)
```

---

## 📌 ENDPOINTS

### 1. Daily Summary Report
**Endpoint:** `GET /api/reports/daily-summary`

**Description:** Mendapatkan ringkasan penjualan harian lengkap (dari cache atau real-time calculation)

**Parameters:**
- `date` (required): Format `Y-m-d` (contoh: `2025-11-13`)

**Example Request:**
```bash
GET /api/reports/daily-summary?date=2025-11-13
Authorization: Bearer YOUR_TOKEN
X-Tenant-ID: 3
```

**Example Response:**
```json
{
  "success": true,
  "data": {
    "date": "2025-11-13",
    "summary": {
      "total_orders": 2,
      "total_items": 17,
      "total_customers": 2,
      "gross_sales": 1966600.00,
      "total_discount": 100000.00,
      "subtotal": 1866600.00,
      "total_tax": 99000.00,
      "total_service": 99000.00,
      "net_sales": 1866600.00,
      "average_transaction": 933300.00
    },
    "payment_breakdown": [
      {
        "method": "cash",
        "amount": 0.00,
        "count": 0,
        "percentage": 0
      },
      {
        "method": "qris",
        "amount": 1866600.00,
        "count": 2,
        "percentage": 100
      },
      {
        "method": "gopay",
        "amount": 0.00,
        "count": 0,
        "percentage": 0
      }
    ],
    "top_products": [
      {
        "id": 1,
        "name": "coca",
        "category": "Beverages",
        "quantity": 17,
        "total": 1866600.00,
        "percentage": 100
      }
    ]
  },
  "meta": {
    "currency": "IDR",
    "timezone": "Asia/Jakarta",
    "generated_at": "2025-11-13 07:50:00"
  }
}
```

---

### 2. Period Summary Report
**Endpoint:** `GET /api/reports/period-summary`

**Description:** Ringkasan penjualan untuk periode tertentu (multiple days) dengan comparison vs periode sebelumnya

**Parameters:**
- `start_date` (required): Format `Y-m-d`
- `end_date` (required): Format `Y-m-d`

**Example Request:**
```bash
GET /api/reports/period-summary?start_date=2025-11-01&end_date=2025-11-30
Authorization: Bearer YOUR_TOKEN
X-Tenant-ID: 3
```

**Example Response:**
```json
{
  "success": true,
  "data": {
    "period": {
      "start": "2025-11-01",
      "end": "2025-11-30",
      "days": 30,
      "type": "monthly"
    },
    "summary": {
      "total_orders": 45,
      "total_items": 234,
      "gross_sales": 15500000.00,
      "total_discount": 500000.00,
      "subtotal": 15000000.00,
      "total_tax": 1500000.00,
      "total_service": 750000.00,
      "net_sales": 17250000.00,
      "average_transaction": 383333.33
    },
    "comparison": {
      "previous_period": {
        "start": "2025-10-02",
        "end": "2025-10-31",
        "net_sales": 12000000.00
      },
      "growth": {
        "amount": 5250000.00,
        "percentage": 43.75,
        "trend": "up",
        "status": "excellent"
      }
    },
    "payment_breakdown": [
      {
        "method": "cash",
        "amount": 8625000.00,
        "count": 20,
        "percentage": 50
      },
      {
        "method": "qris",
        "amount": 6900000.00,
        "count": 18,
        "percentage": 40
      },
      {
        "method": "gopay",
        "amount": 1725000.00,
        "count": 7,
        "percentage": 10
      }
    ],
    "top_products": [
      {
        "id": 5,
        "name": "Nasi Goreng Special",
        "category": "Food",
        "quantity": 78,
        "total": 3900000.00,
        "percentage": 22.61
      }
    ]
  },
  "meta": {
    "currency": "IDR",
    "timezone": "Asia/Jakarta",
    "generated_at": "2025-11-13 07:50:00"
  }
}
```

**Growth Status:**
- `excellent`: ≥ 10%
- `good`: 5% - 9.99%
- `stable`: 0% - 4.99%
- `warning`: -5% - -0.01%
- `danger`: < -5%

---

### 3. Top Products Report
**Endpoint:** `GET /api/reports/top-products`

**Description:** Produk terlaris untuk periode tertentu

**Parameters:**
- `start_date` (required): Format `Y-m-d`
- `end_date` (required): Format `Y-m-d`
- `limit` (optional): Integer 1-50, default: 10

**Example Request:**
```bash
GET /api/reports/top-products?start_date=2025-11-01&end_date=2025-11-30&limit=5
Authorization: Bearer YOUR_TOKEN
X-Tenant-ID: 3
```

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "Nasi Goreng Special",
      "category": "Food",
      "quantity": 78,
      "total": 3900000.00,
      "percentage": 22.61
    },
    {
      "id": 12,
      "name": "Es Teh Manis",
      "category": "Beverages",
      "quantity": 65,
      "total": 325000.00,
      "percentage": 1.88
    }
  ],
  "meta": {
    "period": {
      "start": "2025-11-01",
      "end": "2025-11-30"
    },
    "limit": 5,
    "total": 2
  }
}
```

---

### 4. Generate Daily Summary (Manual Cache)
**Endpoint:** `POST /api/reports/generate-daily-summary`

**Description:** Generate atau refresh cache daily summary secara manual

**Parameters:**
- `date` (required): Format `Y-m-d`
- `force` (optional): Boolean, default: false (set true untuk regenerate)

**Example Request:**
```bash
POST /api/reports/generate-daily-summary
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN
X-Tenant-ID: 3

{
  "date": "2025-11-13",
  "force": true
}
```

**Example Response:**
```json
{
  "success": true,
  "message": "Daily summary generated successfully",
  "data": {
    "id": 1,
    "tenant_id": 3,
    "date": "2025-11-13",
    "total_orders": 2,
    "total_items": 17,
    "net_sales": 1866600.00,
    "is_closed": false,
    "closed_at": null,
    "created_at": "2025-11-13 07:50:00",
    "updated_at": "2025-11-13 07:50:00"
  }
}
```

---

## 🤖 AUTOMATIC FEATURES

### Daily Summary Auto-Generation
Command yang berjalan otomatis setiap hari jam 00:00 (midnight):

```bash
php artisan reports:generate-daily
```

**Features:**
- Generate summary untuk semua tenant
- Skip tenant yang tidak ada transaksi
- Log hasil generation
- Error handling per tenant

**Manual Usage:**
```bash
# Generate untuk hari kemarin (default)
php artisan reports:generate-daily

# Generate untuk tanggal tertentu
php artisan reports:generate-daily --date=2025-11-13

# Generate untuk tenant tertentu saja
php artisan reports:generate-daily --tenant=3

# Force regenerate
php artisan reports:generate-daily --date=2025-11-13 --force
```

---

## 📁 DATABASE SCHEMA

### Table: `daily_summaries`
Cache table untuk performance optimization

**Columns:**
- `id` - Primary key
- `tenant_id` - Foreign key to tenants
- `date` - Summary date (unique per tenant)
- `total_orders` - Total order count
- `total_items` - Total items sold
- `total_customers` - Unique customer count
- `gross_sales` - Before discount
- `total_discount` - Total discount amount
- `subtotal` - After discount
- `total_tax` - Total tax
- `total_service` - Total service charge
- `net_sales` - Final amount (subtotal + tax + service)
- `cash_amount`, `cash_count` - Cash payment breakdown
- `qris_amount`, `qris_count` - QRIS payment breakdown
- `gopay_amount`, `gopay_count` - Gopay payment breakdown
- `is_closed` - Daily closing status
- `closed_at` - Closing timestamp
- `created_at`, `updated_at` - Timestamps

**Indexes:**
- `(tenant_id, date)` - Unique index
- `date` - Index
- `is_closed` - Index
- `(tenant_id, date, is_closed)` - Composite index

### Enhanced Orders Table
New columns added:
- `payment_status` - pending/paid/failed/refunded
- `cashier_id` - User who processed order
- `closed_at` - Daily closing timestamp

**New Indexes:**
- `payment_status`
- `payment_method`
- `closed_at`
- `(tenant_id, created_at)`
- `(tenant_id, payment_method)`
- `(tenant_id, status)`

---

## 🔄 WORKFLOW

### 1. Real-time Reporting
1. Order dibuat → status `pending`
2. Payment completed → status `paid`
3. GET `/api/reports/daily-summary` → Calculate real-time (no cache yet)

### 2. Daily Closing (Automatic)
1. Jam 00:00 → Command `reports:generate-daily` runs
2. Calculate semua transaksi kemarin untuk setiap tenant
3. Save ke `daily_summaries` table (cache)
4. Log hasil ke Laravel logs

### 3. Historical Reports
1. GET `/api/reports/period-summary?start=2025-11-01&end=2025-11-30`
2. Query dari cache jika ada
3. Calculate comparison dengan periode sebelumnya
4. Return dengan growth percentage dan trend

---

## 🎯 USE CASES

### Flutter Mobile App
```dart
// Daily report screen
final response = await http.get(
  Uri.parse('$baseUrl/api/reports/daily-summary?date=$today'),
  headers: {
    'Authorization': 'Bearer $token',
    'X-Tenant-ID': '$tenantId',
  },
);

// Period report with comparison
final response = await http.get(
  Uri.parse('$baseUrl/api/reports/period-summary?start_date=$startDate&end_date=$endDate'),
  headers: {
    'Authorization': 'Bearer $token',
    'X-Tenant-ID': '$tenantId',
  },
);

// Top selling products
final response = await http.get(
  Uri.parse('$baseUrl/api/reports/top-products?start_date=$startDate&end_date=$endDate&limit=10'),
  headers: {
    'Authorization': 'Bearer $token',
    'X-Tenant-ID': '$tenantId',
  },
);
```

### Web Dashboard
```javascript
// Fetch daily summary
fetch('/api/reports/daily-summary?date=2025-11-13', {
  headers: {
    'Authorization': 'Bearer ' + token,
    'X-Tenant-ID': tenantId
  }
})
.then(res => res.json())
.then(data => {
  console.log('Net Sales:', data.data.summary.net_sales);
  console.log('Orders:', data.data.summary.total_orders);
  console.log('Top Product:', data.data.top_products[0].name);
});
```

---

## ⚡ PERFORMANCE NOTES

1. **Cache Strategy**: Daily summaries di-cache untuk fast retrieval
2. **Real-time Calculation**: Date hari ini calculated on-the-fly
3. **Indexes**: Multiple indexes untuk fast queries
4. **Background Jobs**: Daily generation runs in background
5. **No Overlapping**: Scheduled task won't run if previous still running

---

## 🐛 ERROR HANDLING

**Common Errors:**

1. **400 - Tenant ID required**
   - Solution: Provide `X-Tenant-ID` header atau login dengan authenticated user

2. **422 - Validation error**
   - Check required parameters dan format tanggal

3. **500 - Server error**
   - Check logs: `storage/logs/laravel.log`
   - Common: No orders found, database connection

---

## 📊 NEXT PHASE FEATURES (Coming Soon)

**Phase 2: Visualization**
- Sales trend dengan grouping (hourly/daily/weekly/monthly)
- Category performance breakdown
- Hourly analysis
- Chart-ready data formatting

**Phase 3: Export & Print**
- PDF export (daily/period reports)
- Excel export dengan multiple sheets
- Email automation

**Phase 4: Advanced**
- Real-time dashboard updates (WebSocket/Pusher)
- Advanced filtering
- Target setting & alerts
- Predictive analytics

**Phase 5: Polish**
- Performance optimization
- Response caching
- Background jobs untuk heavy reports
- Rate limiting

---

## 🔧 MAINTENANCE

### Check Scheduled Tasks
```bash
php artisan schedule:list
```

### Test Manual Generation
```bash
php artisan reports:generate-daily --date=2025-11-13
```

### Clear Cache
```bash
php artisan optimize:clear
```

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

---

**Version:** 1.0.0  
**Last Updated:** 2025-11-13  
**Status:** ✅ Production Ready
