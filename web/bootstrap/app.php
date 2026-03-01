<?php

declare(strict_types = 1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verified'  => App\Http\Middleware\EnsureEmailIsVerified::class,
            'approved'  => App\Http\Middleware\EnsureUserIsApproved::class,
            'abilities' => Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
        ]);

        $middleware->api(append: [
            App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            if ($request->wantsJson()) {
                $isLoginRoute = $request->routeIs('api.v1.auth.login');

                $message = $isLoginRoute
                    ? 'Too many login attempts. Please try again later.'
                    : 'Too many requests. Please try again later.';

                $errorKey = $isLoginRoute ? 'email' : 'rate_limit';

                return response()->json([
                    'message' => $message,
                    'errors'  => [
                        $errorKey => [$message],
                    ],
                ], Response::HTTP_TOO_MANY_REQUESTS, $e->getHeaders());
            }
        });
    })->create();
