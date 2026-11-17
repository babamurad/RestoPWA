<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Vendor\Models\Restaurant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // We check if the user is a restaurateur by looking for their restaurant.
        $restaurant = Restaurant::where('owner_id', $user->id)->first();

        if (! $restaurant) {
            abort(403, 'Доступ запрещен. Вы не являетесь администратором ресторана.');
        }

        // The tenant context holds the restaurant id (not the legacy vendor_id column).
        $tenant = app('tenant');

        if (! $tenant || ($tenant->id ?? null) !== $restaurant->id) {
            $request->session()->put('vendor_id', $restaurant->id);
        }

        return $next($request);
    }
}
