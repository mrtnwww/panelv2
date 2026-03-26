<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
        // Límite general para la API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(fn() => response()->json([
                    'message' => 'Demasiadas peticiones. Intenta nuevamente en un momento.'
                ], 429));
        });

        // Límite estricto para el login
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(fn() => response()->json([
                    'message' => 'Demasiados intentos de inicio de sesión. Intenta nuevamente en 1 minuto.'
                ], 429));
        });
    }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi(); // Habilita el soporte de cookies en la API
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            if ($e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
                return $e->getResponse();
            }

            if ($request->expectsJson() || $request->is('api/*') || $request->is('login')) {
                if ($e instanceof AuthenticationException) {
                    return response()->json(['message' => 'No autenticado'], 401);
                }

                if ($e instanceof ValidationException) {
                    return response()->json([
                        'message' => 'Error de validación',
                        'errors'  => $e->errors(),
                    ], 422);
                }

                if ($e instanceof ModelNotFoundException) {
                    return response()->json(['message' => 'Recurso no encontrado'], 404);
                }

                if ($e instanceof QueryException) {
                    return response()->json(['message' => 'Error en la base de datos'], 500);
                }

                if ($e instanceof ThrottleRequestsException) {
                    return response()->json([
                        'message' => 'Demasiados intentos. Intenta nuevamente en un momento.'
                    ], 429);
                }

                return response()->json([
                    'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                ], 500);
            }
        });
    })->create();
