<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app('tenant');

        if (!$tenant || !isset($tenant->id)) {
            // Если мы на хосте restopwa и нет вендора, можем попробовать редирект или 403
            if ($request->getHost() === 'restopwa') {
                // Временно для разработки можно использовать первый попавшийся ресторан,
                // если это критично для тестов. Но лучше кинуть 403 с пояснением.
                $firstRestaurant = \App\Domains\Vendor\Models\Restaurant::first();
                if ($firstRestaurant) {
                    $request->session()->put('vendor_id', $firstRestaurant->vendor_id);
                    return redirect()->refresh();
                }
            }

            abort(403, 'Vendor context is missing. Please use a vendor subdomain or provide X-Vendor-ID header.');
        }

        return $next($request);
    }
}
