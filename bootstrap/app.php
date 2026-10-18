<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureTenant;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->appendToGroup('web', [
            SetTenantContext::class,
            SetLocale::class,
        ]);

        // Before auth, so "unauthenticated" errors are already translated
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: SetLocale::class,
        );

        // Written by the PWA in plain text so guests get pages in their language
        $middleware->encryptCookies(except: [SetLocale::COOKIE]);

        $middleware->prependToGroup('api', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
        ]);
        $middleware->appendToGroup('api', [
            SetTenantContext::class,
            SetLocale::class,
        ]);

        $middleware->alias([
            'ensure.tenant' => EnsureTenant::class,
            'admin' => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Report unhandled errors to Sentry (does nothing until SENTRY_LARAVEL_DSN is set)
        Integration::handles($exceptions);

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $code = 500;
                $message = $e->getMessage();
                $errors = [];

                if ($e instanceof ValidationException) {
                    $code = 422;
                    $message = __('api.validation_failed');
                    $errors = $e->errors();
                } elseif ($e instanceof ModelNotFoundException ||
                          $e instanceof NotFoundHttpException) {
                    $code = 404;
                    $message = __('api.not_found');
                } elseif ($e instanceof AuthenticationException) {
                    $code = 401;
                    $message = __('api.unauthenticated');
                } elseif ($e instanceof HttpExceptionInterface) {
                    $code = $e->getStatusCode();
                }

                $response = [
                    'success' => false,
                    'message' => $message,
                    'code' => $code,
                ];

                if (! empty($errors)) {
                    $response['errors'] = $errors;
                }

                if (config('app.debug') && $code === 500) {
                    $response['debug'] = [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => array_slice($e->getTrace(), 0, 10),
                    ];
                }

                return response()->json($response, $code);
            }
        });
    })->create();
