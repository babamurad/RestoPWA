<?php

namespace App\Http\Middleware;

use App\Domains\Vendor\Services\TenantContext;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncFilamentTenant
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant) {
            $this->tenantContext->setCurrentVendor((string) $tenant->getKey());
        }

        return $next($request);
    }
}
