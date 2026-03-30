<?php

namespace App\Providers;

use App\Events\OrderStatusUpdated;
use App\Listeners\LogOrderStatusUpdate;
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
    }
}
