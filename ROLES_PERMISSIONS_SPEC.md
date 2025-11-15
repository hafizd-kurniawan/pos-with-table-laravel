# 🔐 ROLES & PERMISSIONS SYSTEM - SPECIFICATION

## 🎯 **OBJECTIVE**

Build complete Role-Based Access Control (RBAC) system that allows:
1. ✅ Tenant admin can create multiple user accounts
2. ✅ Assign different roles (Cashier, Manager, Chef, Waiter, etc.)
3. ✅ Customize permissions per role
4. ✅ Control menu/feature access based on permissions
5. ✅ Multi-tenant isolated (each tenant manages own roles)

---

## 📊 **DATABASE SCHEMA**

### **1. Roles Table**
```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,              -- e.g., "Cashier", "Manager"
    slug VARCHAR(255) NOT NULL,              -- e.g., "cashier", "manager"
    description TEXT NULL,
    is_default BOOLEAN DEFAULT FALSE,        -- Default role for new users
    is_system BOOLEAN DEFAULT FALSE,         -- System roles (cannot delete)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY unique_slug_per_tenant (tenant_id, slug),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_slug (slug)
);
```

### **2. Permissions Table**
```sql
CREATE TABLE permissions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,              -- e.g., "View Reports", "Manage Products"
    slug VARCHAR(255) NOT NULL UNIQUE,       -- e.g., "view_reports", "manage_products"
    group VARCHAR(255) NOT NULL,             -- e.g., "reports", "products", "orders"
    description TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_group (group)
);
```

**Note:** Permissions are GLOBAL (not tenant-specific) to maintain consistency

### **3. Role-Permission Pivot Table**
```sql
CREATE TABLE role_permissions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP,
    
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    INDEX idx_role (role_id),
    INDEX idx_permission (permission_id)
);
```

### **4. Update Users Table**
```sql
ALTER TABLE users ADD COLUMN role_id BIGINT UNSIGNED NULL AFTER tenant_id;
ALTER TABLE users ADD FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;
ALTER TABLE users ADD INDEX idx_role (role_id);
```

---

## 🏷️ **DEFAULT ROLES**

### **Role Hierarchy (per tenant):**

**1. Owner/Admin** (tenant_id = NULL or is_system = true)
- Full access to everything
- Can create/manage other roles
- Can assign any permission

**2. Manager**
- View all reports
- Manage products, categories, tables
- Manage orders
- View users (cannot create admins)
- Limited settings access

**3. Cashier**
- Create orders
- Process payments
- View own shift reports
- Cannot access settings
- Cannot manage products

**4. Chef/Kitchen**
- View incoming orders
- Update order status (cooking, ready)
- View kitchen reports
- Cannot access payments

**5. Waiter**
- Create orders
- View table status
- Update order status (served)
- Cannot access payments or reports

**6. Viewer** (read-only)
- View reports only
- No modification rights

---

## 🔑 **PERMISSION GROUPS & PERMISSIONS**

### **Group: Dashboard**
- `view_dashboard` - Access main dashboard
- `view_analytics` - View analytics widgets

### **Group: Orders**
- `view_orders` - View all orders
- `create_orders` - Create new orders
- `edit_orders` - Edit existing orders
- `delete_orders` - Delete orders
- `manage_order_status` - Update order status
- `void_orders` - Void/cancel orders

### **Group: Products**
- `view_products` - View products list
- `create_products` - Add new products
- `edit_products` - Edit products
- `delete_products` - Delete products
- `manage_categories` - Manage categories
- `manage_addons` - Manage addons

### **Group: Tables**
- `view_tables` - View tables
- `create_tables` - Create tables
- `edit_tables` - Edit tables
- `delete_tables` - Delete tables
- `manage_table_status` - Change table status

### **Group: Reports**
- `view_reports` - View basic reports
- `view_financial_reports` - View financial reports
- `view_product_reports` - View product reports
- `export_reports` - Export reports (PDF/Excel)
- `view_all_reports` - View all tenant reports

### **Group: Users**
- `view_users` - View users list
- `create_users` - Create new users
- `edit_users` - Edit users
- `delete_users` - Delete users
- `assign_roles` - Assign roles to users

### **Group: Roles & Permissions**
- `view_roles` - View roles
- `create_roles` - Create new roles
- `edit_roles` - Edit roles
- `delete_roles` - Delete roles
- `assign_permissions` - Assign permissions to roles

### **Group: Settings**
- `view_settings` - View settings
- `edit_settings` - Edit settings
- `edit_payment_settings` - Edit payment settings
- `edit_appearance` - Edit appearance settings

### **Group: Payments**
- `process_payments` - Process payments
- `view_payment_history` - View payment history
- `refund_payments` - Refund payments

### **Group: Inventory** (optional)
- `view_inventory` - View inventory
- `manage_inventory` - Manage inventory
- `view_stock_reports` - View stock reports

---

## 🔒 **AUTHORIZATION FLOW**

### **Check Permission:**
```php
// In controller
if (!auth()->user()->hasPermission('view_reports')) {
    abort(403, 'Unauthorized');
}

// In Blade
@can('view_reports')
    <a href="/reports">Reports</a>
@endcan

// In Filament Resource
public static function canViewAny(): bool
{
    return auth()->user()->hasPermission('view_products');
}
```

### **Role-Based Navigation:**
```php
// In Filament Panel
->navigationGroups([
    'Reports' => [
        'visible' => fn() => auth()->user()->hasPermission('view_reports'),
    ],
])
```

---

## 🎨 **UI FEATURES**

### **1. Role Management Page**
- List all roles for current tenant
- Create/Edit/Delete roles
- Assign permissions with grouped checkboxes
- Visual permission matrix

### **2. User Management Page**
- List all users in tenant
- Create new users with role assignment
- Edit user details & role
- Deactivate/activate users

### **3. Permission Assignment UI**
```
┌─────────────────────────────────────────┐
│ Role: Cashier                           │
├─────────────────────────────────────────┤
│ Dashboard                               │
│  ☑ View Dashboard                       │
│  ☐ View Analytics                       │
│                                         │
│ Orders                                  │
│  ☑ View Orders                          │
│  ☑ Create Orders                        │
│  ☐ Edit Orders                          │
│  ☐ Delete Orders                        │
│  ☑ Manage Order Status                  │
│                                         │
│ Products                                │
│  ☑ View Products                        │
│  ☐ Create Products                      │
│  ☐ Edit Products                        │
│  ☐ Delete Products                      │
│                                         │
│ Reports                                 │
│  ☐ View Reports                         │
│  ☐ Export Reports                       │
│                                         │
│ [Cancel]  [Save Permissions]            │
└─────────────────────────────────────────┘
```

---

## 🔧 **IMPLEMENTATION PLAN**

### **Phase 1: Database & Models** (30 min)
1. Create migrations
2. Create models (Role, Permission, RolePermission)
3. Add relationships
4. Add BelongsToTenant trait

### **Phase 2: Seeders** (20 min)
1. Seed global permissions
2. Seed default roles per tenant
3. Assign default permissions

### **Phase 3: Authorization Logic** (30 min)
1. Add methods to User model (hasPermission, hasRole)
2. Create middleware for permission check
3. Create Blade directives

### **Phase 4: Filament Resources** (45 min)
1. RoleResource (CRUD roles)
2. Enhance UserResource (add role assignment)
3. Permission assignment UI
4. Navigation visibility control

### **Phase 5: API Endpoints** (30 min)
1. Role management APIs
2. User role assignment API
3. Permission check API

### **Phase 6: Testing & Documentation** (30 min)
1. Test all permissions
2. Test role assignments
3. Test navigation visibility
4. Document usage

**Total Estimated Time:** ~3 hours

---

## 🎯 **SUCCESS CRITERIA**

- [x] Admin can create custom roles
- [x] Admin can assign permissions to roles
- [x] Admin can create users and assign roles
- [x] Users only see authorized menus
- [x] Permissions enforced on all actions
- [x] Multi-tenant isolated (tenant A cannot see tenant B roles)
- [x] System is performant (cached permissions)
- [x] Well documented

---

## 🚀 **FUTURE ENHANCEMENTS**

1. **Permission Templates**
   - Pre-defined permission sets
   - Quick role creation

2. **Time-Based Permissions**
   - Permissions active only during certain hours
   - Shift-based access control

3. **Permission Logs**
   - Track who changed what permissions
   - Audit trail

4. **Granular Permissions**
   - Column-level permissions
   - Row-level permissions (own data only)

5. **Permission Inheritance**
   - Child roles inherit parent permissions
   - Role hierarchy

---

## 📝 **NOTES**

**Security:**
- Never expose permission IDs to frontend
- Always check permissions server-side
- Cache permissions for performance
- Clear cache on permission changes

**Performance:**
- Use eager loading for role relationships
- Cache user permissions
- Use database indexes
- Avoid N+1 queries

**UX:**
- Clear permission names
- Group related permissions
- Show permission descriptions
- Warn before removing critical permissions

---

**Ready to implement? Let's go! 🚀**
