#!/bin/bash

echo "🧹 FORCE CLEAR ALL CACHES - NUCLEAR OPTION"
echo "=========================================="
echo ""

# Laravel caches
echo "1. Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
echo "✓ Laravel caches cleared"
echo ""

# Livewire
echo "2. Clearing Livewire..."
php artisan livewire:discover
echo "✓ Livewire refreshed"
echo ""

# Storage framework caches
echo "3. Clearing storage framework..."
rm -rf storage/framework/cache/data/* 2>/dev/null
rm -rf storage/framework/views/* 2>/dev/null
rm -rf storage/framework/sessions/* 2>/dev/null
echo "✓ Storage framework cleared"
echo ""

# Bootstrap cache
echo "4. Clearing bootstrap cache..."
rm -rf bootstrap/cache/*.php 2>/dev/null
echo "✓ Bootstrap cache cleared"
echo ""

# Composer autoload
echo "5. Optimizing composer..."
composer dump-autoload -o
echo "✓ Composer optimized"
echo ""

echo "=========================================="
echo "✅ ALL CACHES CLEARED!"
echo ""
echo "NEXT STEPS:"
echo "1. Close browser COMPLETELY"
echo "2. Wait 5 seconds"
echo "3. Open browser fresh"
echo "4. Hard refresh: Ctrl+Shift+R"
echo "5. Login & test Period Report"
echo ""
echo "Cash SHOULD appear now! 🚀"
