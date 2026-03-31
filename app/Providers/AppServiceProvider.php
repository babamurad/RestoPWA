<?php

namespace App\Providers;

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Services\TenantContext;
use App\Events\OrderStatusUpdated;
use App\Listeners\LogOrderStatusUpdate;
use App\Observers\OrderObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Domains\Vendor\Services\TenantContext::class);
        $this->app->singleton(\App\Domains\Geo\Services\GeoService::class);
        $this->app->singleton(\App\Services\PushNotificationService::class);

        $this->app->bind('tenant', function ($app) {
            $tenantContext = $app->make(TenantContext::class);
            $vendorId = $tenantContext->getCurrentVendor();
            
            if ($vendorId) {
                return (object) ['id' => $vendorId];
            }
            
            return null;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            OrderStatusUpdated::class,
            LogOrderStatusUpdate::class
        );

        Order::observe(OrderObserver::class);
    }
}
