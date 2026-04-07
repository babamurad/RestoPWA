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

        $this->app->singleton(\App\Services\PushNotificationService::class, function ($app) {
            if (!class_exists(\Minishlink\WebPush\WebPush::class)) {
                return new class {
                    public function sendToUser($userId, $title, $body, $data = []): int { return 0; }
                    public function sendToSubscription($subscription, $title, $body, $data = []): int { return 0; }
                };
            }
            return new \App\Services\PushNotificationService();
        });

        $this->app->bind('tenant', function ($app) {
            $tenantContext = $app->make(\App\Domains\Vendor\Services\TenantContext::class);
            $vendorId = $tenantContext->getCurrentVendor();

            if ($vendorId) {
                return \App\Domains\Vendor\Models\Restaurant::where('vendor_id', $vendorId)->first()
                    ?? (object) ['id' => $vendorId];
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
