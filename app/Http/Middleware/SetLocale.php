<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the language of the customer app: the signed-in user's saved language,
 * otherwise the X-Locale header / "locale" cookie the PWA sets, otherwise the
 * default (Russian). Restaurant staff and admins always work in Russian.
 */
class SetLocale
{
    public const COOKIE = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('app.supported_locales');
        $user = $request->user();

        $locale = $user?->locale ?? $request->header('X-Locale') ?? $request->cookie(self::COOKIE);

        if (! in_array($locale, $supported, true) || ($user && ($user->isAdmin() || $user->isRestaurateur()))) {
            $locale = $supported[0];
        }

        // Always set it: a long-lived worker must not keep the previous request's language
        app()->setLocale($locale);

        return $next($request);
    }
}
