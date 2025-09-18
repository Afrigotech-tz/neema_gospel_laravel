<?php

use App\Http\Middleware\ApiKeyMiddleware;
use App\Http\Middleware\Cors;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Request;
use Mockery\Exception\InvalidOrderException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            Cors::class,
        ]);

        $middleware->alias([
            'api.key' => ApiKeyMiddleware::class,
        ]);
    })
    ->withExceptions(function ($exceptions) {
        
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Route not found',
                    'status' => 404
                ], 404);
            }

            return response()->view('errors.404', [], 404);

        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, $request) {

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Method not allowed',
                    'status' => 405
                ], 405);
            }

        });
        
    })
    ->create();
