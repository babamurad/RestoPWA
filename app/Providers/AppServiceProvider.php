<?php

namespace App\Providers;

use App\Domains\Geo\Services\GeoService;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Domains\Vendor\Services\TenantContext;
use App\Events\OrderStatusUpdated;
use App\Listeners\LogOrderStatusUpdate;
use App\Observers\OrderObserver;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Minishlink\WebPush\WebPush;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(GeoService::class);

        $this->app->singleton(PushNotificationService::class, function ($app) {
            if (! class_exists(WebPush::class)) {
                return new class
                {
                    public function sendToUser($userId, $title, $body, $data = []): int
                    {
                        return 0;
                    }

                    public function sendToSubscription($subscription, $title, $body, $data = []): int
                    {
                        return 0;
                    }
                };
            }

            return new PushNotificationService;
        });

        $this->app->bind('tenant', function ($app) {
            $tenantContext = $app->make(TenantContext::class);
            $vendorId = $tenantContext->getCurrentVendor();

            if ($vendorId) {
                return Restaurant::where('vendor_id', $vendorId)->first()
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
