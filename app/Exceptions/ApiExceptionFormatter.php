<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class ApiExceptionFormatter
{
    public function format(\Throwable $e): ?array
    {
        $status = 500;
        $message = 'Internal Server Error';
        $errors = null;

        // Validation
        if ($e instanceof ValidationException) {
            $status = 422;
            $message = 'Validation failed';
            $errors = $e->errors();
        }

        // Authentication
        elseif ($e instanceof AuthenticationException) {
            $status = 401;
            $message = 'Unauthenticated';
        }

        // Authorization
        elseif ($e instanceof AccessDeniedHttpException) {
            $status = 403;
            $message = 'Forbidden';
        }

        // Not Found
        elseif ($e instanceof NotFoundHttpException) {
            $status = 404;

            $previous = $e->getPrevious();

            if ($previous instanceof ModelNotFoundException) {
                $modelMessages = [
                    \App\Models\UserAddress::class => 'Address not found',
                ];

                $model = $previous->getModel();

                $message = $modelMessages[$model]
                    ?? Str::of(class_basename($model))
                    ->snake()
                    ->replace('_', ' ')
                    ->ucfirst() . ' not found';
            } else {
                $message = 'Resource not found';
            }
        }

        // Method Not Allowed
        elseif ($e instanceof MethodNotAllowedHttpException) {
            $status = 405;
            $message = 'Method not allowed';
        }

        // Category Exception
        elseif ($e instanceof CircularCategoryException) {
            $status = 422;
            $message = $e->getMessage();
        }

        // Other HTTP Exception
        elseif ($e instanceof HttpException) {
            $status = $e->getStatusCode();

            $message = match ($status) {
                401 => 'Unauthenticated',
                403 => 'Forbidden',
                404 => 'Not found',
                default => 'HTTP Error',
            };
        }

        // Fallback 500
        if ($status === 500) {
            Log::error($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'Internal Server Error';
        }

        return [
            'status' => $status,
            'body' => [
                'success' => false,
                'message' => $message,
                'data' => null,
                'errors' => $errors,
            ],
        ];
    }
}
