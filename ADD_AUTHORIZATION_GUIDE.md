# 🔐 ADD AUTHORIZATION TO RESOURCES - GUIDE

## ✅ **ALREADY DONE**
- SettingResource ✅
- ProductResource ✅
- UserResource ✅ (has own logic)
- RoleResource ✅ (has own logic)

## 🔧 **NEED TO ADD**

### **OrderResource** - Orders permissions
```php
public static function canViewAny(): bool
{
    return auth()->user()->hasPermission('view_orders');
}

public static function canCreate(): bool
{
    return auth()->user()->hasPermission('create_orders');
}

public static function canEdit($record): bool
{
    return auth()->user()->hasPermission('edit_orders');
}

public static function canDelete($record): bool
{
    return auth()->user()->hasPermission('delete_orders');
}
```

### **TableResource** - Tables permissions
```php
public static function canViewAny(): bool
{
    return auth()->user()->hasPermission('view_tables');
}

public static function canCreate(): bool
{
    return auth()->user()->hasPermission('create_tables');
}

public static function canEdit($record): bool
{
    return auth()->user()->hasPermission('edit_tables');
}

public static function canDelete($record): bool
{
    return auth()->user()->hasPermission('delete_tables');
}
```

### **CategoryResource** - Products permissions (use manage_categories)
```php
public static function canViewAny(): bool
{
    return auth()->user()->hasPermission('manage_categories');
}

public static function canCreate(): bool
{
    return auth()->user()->hasPermission('manage_categories');
}

public static function canEdit($record): bool
{
    return auth()->user()->hasPermission('manage_categories');
}

public static function canDelete($record): bool
{
    return auth()->user()->hasPermission('manage_categories');
}
```

### **DiscountResource** - Pricing permissions
```php
public static function canViewAny(): bool
{
    return auth()->user()->hasPermission('manage_discounts');
}

public static function canCreate(): bool
{
    return auth()->user()->hasPermission('manage_discounts');
}

public static function canEdit($record): bool
{
    return auth()->user()->hasPermission('manage_discounts');
}

public static function canDelete($record): bool
{
    return auth()->user()->hasPermission('manage_discounts');
}
```

### **TaxResource** - Pricing permissions
```php
public static function canViewAny(): bool
{
    return auth()->user()->hasPermission('manage_taxes');
}

public static function canCreate(): bool
{
    return auth()->user()->hasPermission('manage_taxes');
}

public static function canEdit($record): bool
{
    return auth()->user()->hasPermission('manage_taxes');
}

public static function canDelete($record): bool
{
    return auth()->user()->hasPermission('manage_taxes');
}
```

### **TableCategoryResource** - Tables permissions
```php
public static function canViewAny(): bool
{
    return auth()->user()->hasPermission('view_tables');
}

public static function canCreate(): bool
{
    return auth()->user()->hasPermission('create_tables');
}

public static function canEdit($record): bool
{
    return auth()->user()->hasPermission('edit_tables');
}

public static function canDelete($record): bool
{
    return auth()->user()->hasPermission('delete_tables');
}
```

### **ReservationResource** - Tables permissions (or create new permission)
```php
// Option 1: Use manage_tables permission
public static function canViewAny(): bool
{
    return auth()->user()->hasPermission('manage_table_status');
}

// Option 2: For now, allow all users with table access
public static function canViewAny(): bool
{
    return auth()->user()->hasPermission('view_tables');
}
```

---

## 📝 **WHERE TO ADD**

Add these methods RIGHT AFTER the navigation properties, before `form()` method:

```php
class SomeResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = SomeModel::class;
    protected static ?string $navigationIcon = '...';
    protected static ?string $navigationGroup = '...';
    protected static ?int $navigationSort = 1;

    // ✅ ADD AUTHORIZATION HERE
    public static function canViewAny(): bool
    {
        return auth()->user()->hasPermission('view_something');
    }
    // ... rest of authorization methods

    public static function form(Form $form): Form
    {
        // ... existing code
    }
}
```

---

## 🎯 **QUICK TEST**

After adding:
1. Clear cache: `php artisan optimize:clear`
2. Login as Cashier user
3. Check sidebar - should only see authorized menus
4. Try accessing Settings directly: `http://192.168.1.4:8000/admin/settings`
5. Should get 403 or redirect
