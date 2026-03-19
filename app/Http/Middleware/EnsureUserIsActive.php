<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::guard('api')->user();

        if ($user) {
            // Refresh the user from database to get latest status
            $user->refresh();

            if (! $user->is_active) {
                // Revoke the user's token since they are inactive
                Auth::guard('api')->logout();

                throw ValidationException::withMessages([
                    'email' => ['Account is disabled.'],
                ]);
            }
        }

        return $next($request);
    }
}
