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
                if ($request->hasSession()) {
                    $vendorId = $request->session()->get('vendor_id');
                }
            }
        }

        // Fallback to route parameters, URL segments, or request body
        if (! $vendorId) {
            $vendorParam = $request->route('vendor');
            if ($vendorParam) {
                $vendorId = is_object($vendorParam) ? ($vendorParam->id ?? $vendorParam->vendor_id) : $vendorParam;
            } elseif ($request->is('api/v1/menu/product/*')) {
                $productId = $request->segment(5);
                $vendorId = \Illuminate\Support\Facades\DB::table('products')
                    ->where('id', $productId)
                    ->value('vendor_id');
            } elseif ($request->is('api/v1/menu/*')) {
                $segment4 = $request->segment(4);
                if ($segment4 !== 'product') {
                    $vendorId = $segment4;
                }
            } elseif ($request->is('api/v1/restaurants/*')) {
                $vendorId = $request->segment(4);
            } elseif ($request->is('api/v1/cart/sync') || $request->is('api/v1/orders') || $request->is('api/v1/geo/zone-check')) {
                $vendorId = $request->input('vendor_id');
            }
        }

        if ($vendorId) {
            // Resolve raw slug or subdomain string to vendor UUID if it is not already a valid UUID
            if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string) $vendorId)) {
                $resolved = \Illuminate\Support\Facades\DB::table('restaurants')
                    ->where('slug', $vendorId)
                    ->orWhere('id', $vendorId)
                    ->first();
                if ($resolved) {
                    $vendorId = $resolved->id;
                } else {
                    $vendorId = null;
                }
            }
            if ($vendorId) {
                $this->tenantContext->setCurrentVendor((string) $vendorId);
            }
        } elseif (
            $request->is('api/ping') ||
            $request->is('api/order/*/track*') ||
            $request->is('api/v1/push/*') ||
            $request->is('api/v1/restaurants*') ||
            $request->is('api/v1/categories*') ||
            $request->is('api/v1/login') ||
            $request->is('api/v1/register') ||
            $request->is('api/v1/logout') ||
            $request->is('api/v1/user') ||
            $request->is('api/v1/orders*') ||
            $request->is('api/v1/telemetry') ||
            $request->is('api/v1/geo/*') ||
            $request->is('api/v1/profile/*')
        ) {
            // Allow health check, guest tracking, push endpoints, global restaurants/categories lists, and global auth endpoints without tenant header
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
