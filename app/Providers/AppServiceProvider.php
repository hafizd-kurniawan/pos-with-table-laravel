<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Midtrans\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Event;
use App\Models\Reservation;
use App\Observers\ReservationObserver;
use App\Models\PurchaseOrder;
use App\Observers\PurchaseOrderObserver;
use App\Events\LowStockDetected;
use App\Listeners\SendLowStockNotification;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register ReportService as singleton
        $this->app->singleton(\App\Services\ReportService::class, function ($app) {
            return new \App\Services\ReportService();
        });

        // Register InventoryService as singleton
        $this->app->singleton(\App\Services\InventoryService::class, function ($app) {
            return new \App\Services\InventoryService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('development')) { 
        URL::forceScheme('https');
        } 
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // Register Observers
        Reservation::observe(ReservationObserver::class);
        PurchaseOrder::observe(PurchaseOrderObserver::class);

        // Register Event Listeners
        Event::listen(
            LowStockDetected::class,
            SendLowStockNotification::class,
        );
    }
}
