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
        if (class_exists(\Filament\Actions\ViewAction::class) && !class_exists('Filament\Tables\Actions\ViewAction')) {
            class_alias(\Filament\Actions\ViewAction::class, 'Filament\Tables\Actions\ViewAction');
        }
        if (class_exists(\Filament\Actions\EditAction::class) && !class_exists('Filament\Tables\Actions\EditAction')) {
            class_alias(\Filament\Actions\EditAction::class, 'Filament\Tables\Actions\EditAction');
        }
        if (class_exists(\Filament\Actions\DeleteAction::class) && !class_exists('Filament\Tables\Actions\DeleteAction')) {
            class_alias(\Filament\Actions\DeleteAction::class, 'Filament\Tables\Actions\DeleteAction');
        }
        if (class_exists(\Filament\Actions\DeleteBulkAction::class) && !class_exists('Filament\Tables\Actions\DeleteBulkAction')) {
            class_alias(\Filament\Actions\DeleteBulkAction::class, 'Filament\Tables\Actions\DeleteBulkAction');
        }
        if (class_exists(\Filament\Actions\BulkActionGroup::class) && !class_exists('Filament\Tables\Actions\BulkActionGroup')) {
            class_alias(\Filament\Actions\BulkActionGroup::class, 'Filament\Tables\Actions\BulkActionGroup');
        }

        $this->app->singleton(TenantContext::class);
        $this->app->singleton(GeoService::class);
        
        $this->app->singleton(\App\Services\Sms\SmsProviderInterface::class, \App\Services\Sms\TmSmsService::class);

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
        if (str_starts_with(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Event::listen(
            OrderStatusUpdated::class,
            LogOrderStatusUpdate::class
        );

        Order::observe(OrderObserver::class);
    }
}
