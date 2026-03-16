<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            // Intercept API routes and force standard JSON
            if ($request->is('api/*') || $request->wantsJson()) {

                $statusCode = 500;
                if ($e instanceof HttpException) $statusCode = $e->getStatusCode();
                if ($e instanceof ValidationException) $statusCode = 422;
                if ($e instanceof ModelNotFoundException) $statusCode = 404;

                $message = $e->getMessage() ?: 'Internal Server Error';
                $data = null;

                if ($e instanceof ValidationException) {
                    $message = 'Validation Failed';
                    $data = $e->errors();
                }

                // Hide sensitive errors in production
                if (!config('app.debug') && $statusCode === 500) {
                    $message = 'Internal Server Error';
                }

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'data'    => $data,
                ], $statusCode);
            }
        });
    })->create();
