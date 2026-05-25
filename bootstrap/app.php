<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureTenant;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [
            SetTenantContext::class,
        ]);

        $middleware->prependToGroup('api', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ]);
        $middleware->appendToGroup('api', [
            SetTenantContext::class,
        ]);

        $middleware->alias([
            'ensure.tenant' => EnsureTenant::class,
            'admin' => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $code = 500;
                $message = $e->getMessage();
                $errors = [];

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    $code = 422;
                    $message = 'Ошибка валидации данных';
                    $errors = $e->errors();
                } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException || 
                          $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    $code = 404;
                    $message = 'Ресурс не найден';
                } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $code = 401;
                    $message = 'Не авторизован';
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
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
                        'trace' => array_slice($e->getTrace(), 0, 10)
                    ];
                }

                return response()->json($response, $code);
            }
        });
    })->create();
