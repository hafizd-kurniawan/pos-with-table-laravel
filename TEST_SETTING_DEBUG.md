# SETTING DEBUG TEST

## Step 1: Clear ALL caches
```bash
php artisan optimize:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

## Step 2: Test in Tinker
```bash
php artisan tinker
```

Then run:
```php
$setting = App\Models\Setting::where('key', 'receipt_footer_text')->first();
echo "Value raw: " . $setting->getRawOriginal('value') . "\n";
echo "Value accessor: " . $setting->value . "\n";
echo "Type: " . $setting->type . "\n";
```

## Step 3: Check Form in Browser
1. Go to Settings page
2. Click Edit on "Teks Footer Struk"
3. Open Browser DevTools (F12)
4. Go to Console tab
5. Run this JavaScript:
```javascript
// Check what value Filament loaded
document.querySelector('textarea[name="value"]')?.value
```

## Step 4: Check Network Tab
1. Keep DevTools open
2. Click Edit
3. Go to Network tab
4. Find the request that loads edit form
5. Check Response → data → value

## If value still empty, the problem is in Filament form hydration!
