<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait ThrottlesRequests
{
    protected function checkTooManyFailedAttempts(
        Request $request,
        int $maxAttempts = 5,
        string $identifier = 'email'
    ): void {
        $throttleKey = $this->getThrottleKey($request, $identifier);

        if (! RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($throttleKey);

        throw ValidationException::withMessages([
            $identifier => ['Terlalu banyak percobaan. Silakan coba lagi dalam ' . $seconds . ' detik.'],
        ]);
    }

    protected function hitThrottleLimiter(Request $request, int $decaySeconds = 60, string $identifier = 'email'): void
    {
        RateLimiter::hit($this->getThrottleKey($request, $identifier), $decaySeconds);
    }

    protected function clearThrottleLimiter(Request $request, string $identifier = 'email'): void
    {
        RateLimiter::clear($this->getThrottleKey($request, $identifier));
    }

    protected function getThrottleKey(Request $request, string $identifier): string
    {
        $value = $request->input($identifier) ? Str::lower($request->input($identifier)) : '';
        return Str::transliterate($value . '|' . $request->ip());
    }
}
