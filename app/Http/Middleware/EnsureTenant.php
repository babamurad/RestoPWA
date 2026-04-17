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
        $restaurant = Restaurant::where('vendor_id', $user->id)->first();

        if (! $restaurant) {
            abort(403, 'Доступ запрещен. Вы не являетесь администратором ресторана.');
        }

        // Determine if tenant matches the user's restaurant
        $tenant = app('tenant');
        
        if (! $tenant || ! isset($tenant->vendor_id) || $tenant->vendor_id !== $user->id) {
            $request->session()->put('vendor_id', $restaurant->vendor_id);
        }

        return $next($request);
    }
}
