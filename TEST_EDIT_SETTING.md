# TEST EDIT SETTING - STEP BY STEP

## CRITICAL: Follow these steps EXACTLY!

### Step 1: Clear ALL Caches
```bash
cd /home/biru/Downloads/gabungan/laravel
php artisan optimize:clear
php artisan config:clear  
php artisan view:clear
php artisan cache:clear
```

### Step 2: Start Log Monitoring (NEW TERMINAL)
```bash
cd /home/biru/Downloads/gabungan/laravel
tail -f storage/logs/laravel.log | grep "EditSetting"
```

### Step 3: Test Edit in Browser
1. Go to: http://localhost:8000/admin/settings
2. Find "App Name" (app_name)
3. Click "Edit"
4. Change value from "Self Order POS" to "My Restaurant"
5. Click "Save"

### Step 4: Check What Happened

**If Save Success:**
✅ Value changed to "My Restaurant"
✅ Check logs - should show:
```
EditSetting mutateFormDataBeforeSave BEFORE: [...key => 'app_name'...]
EditSetting mutateFormDataBeforeSave AFTER: [NO key field]
```

**If Error "key has already been taken":**
❌ Check logs - will show which step failed
❌ Key is still being sent somehow

### Step 5: Alternative Test (Tinker)
If browser test fails, test via tinker:
```bash
php artisan tinker
```

```php
$setting = App\Models\Setting::where('key', 'app_name')->first();
$setting->value = 'Test via Tinker';
$setting->save();
echo "Success!\n";
```

### Step 6: Check Database Directly
```bash
php artisan tinker
```

```php
DB::table('settings')->where('key', 'app_name')->update(['value' => 'Direct DB Update']);
echo "Updated!\n";
```

## Expected Results:
- Tinker direct update: ✅ SHOULD WORK
- Tinker model save: ✅ SHOULD WORK  
- Browser Filament edit: ❓ Check logs to see where it fails

## If Still Failing:
The logs will show EXACTLY where the 'key' field is coming from!
