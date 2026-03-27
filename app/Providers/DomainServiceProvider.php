<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;

class DomainServiceProvider extends ServiceProvider
{
    protected array $domains = [
        'Menu',
        'Order',
        'Payment',
        'Vendor',
        'Geo',
        'User',
    ];

    public function register(): void
    {
        $this->registerPolicies();
        $this->registerViews();
    }

    public function boot(): void
    {
        //
    }

    protected function registerPolicies(): void
    {
        foreach ($this->domains as $domain) {
            $policyClass = "App\\Domains\\{$domain}\\Policies\\{$domain}Policy";
            
            if (class_exists($policyClass)) {
                $modelClass = "App\\Domains\\{$domain}\\Models\\{$domain}";
                
                if (class_exists($modelClass)) {
                    Gate::policy($modelClass, $policyClass);
                }
            }
        }
    }

    protected function registerViews(): void
    {
        foreach ($this->domains as $domain) {
            $viewsPath = base_path("app/Domains/{$domain}/Resources/views");
            
            if (is_dir($viewsPath)) {
                View::addNamespace($domain, $viewsPath);
            }
        }
    }
}
