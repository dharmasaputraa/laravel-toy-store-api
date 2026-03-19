<?php

namespace App\Services;

use App\DTOs\User\Auth\LoginData;
use App\DTOs\User\Auth\RegisterData;
use App\DTOs\User\Auth\ResetPasswordData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Events\UserRegistered;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function me(): User
    {
        return Auth::guard('api')->user();
    }

    public function register(RegisterData $data): User
    {
        return DB::transaction(function () use ($data) {

            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'password' => $data->password,
                'locale' => $data->locale,
                'is_active' => true,
            ]);

            event(new UserRegistered($user));

            return $user;
        });
    }
    public function login(LoginData $data): array
    {
        $guard = Auth::guard('api');

        if (! $token = $guard->attempt([
            'email' => $data->email,
            'password' => $data->password,
        ])) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial tidak valid.'],
            ]);
        }

        $user = $guard->user();

        if (! $user->is_active) {
            $guard->logout();
            throw ValidationException::withMessages([
                'email' => ['Account is disabled.'],
            ]);
        }

        return $this->respondWithToken($token);
    }

    public function refresh(): array
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');

        $newToken = $guard->refresh();

        return $this->respondWithToken($newToken);
    }

    public function revokeToken(): void
    {
        Auth::guard('api')->logout();
    }


    public function sendPasswordResetLink(string $email): void
    {
        $status = Password::broker()->sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }
    public function resetPassword(ResetPasswordData $data): void
    {
        $data = $data->toArray();

        // $data harus berisi array assosiatif: ['token', 'email', 'password', 'password_confirmation']
        $status = Password::broker()->reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password
                ])->setRememberToken(Str::random(60));

                $user->save();

                // Trigger event bawaan Laravel
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }

    protected function respondWithToken(string $token): array
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $guard->factory()->getTTL() * 60,
        ];
    }

    /**
     * Verify user's email address.
     *
     * @param  string  $id
     * @param  string  $hash
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    public function verifyEmail(string $id, string $hash): void
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            throw ValidationException::withMessages([
                'email' => ['Invalid verification link.'],
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Email already verified.'],
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($user));
        }
    }

    /**
     * Resend the email verification notification.
     *
     * @param  User  $user
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    public function resendVerificationEmail(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Email already verified.'],
            ]);
        }

        $user->sendEmailVerificationNotification();
    }
}
