<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Vendor\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $vendorId = $request->header('X-Vendor-ID');

        if (! $vendorId) {
            $host = $request->getHost();
            // Expected format: {vendor}.resto.local
            if (preg_match('/^([^.]+)\.resto\.local$/', $host, $matches)) {
                $vendorId = $matches[1];
            } elseif ($host === 'restopwa') {
                // Фоллбек для локальной разработки: берем из сессии или заголовка
                $vendorId = $request->session()->get('vendor_id');
            }
        }

        if ($vendorId) {
            $this->tenantContext->setCurrentVendor((string) $vendorId);
        } elseif ($request->is('api/ping')) {
            // Allow health check without tenant
            return $next($request);
        } elseif ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant identification required (X-Vendor-ID header or subdomain)',
                'code' => 400,
            ], 400);
        }

        return $next($request);
    }
}
