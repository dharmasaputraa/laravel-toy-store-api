<?php

namespace App\Services;

use App\DTOs\User\Auth\SocialLoginData;
use App\Models\SocialAccount;
use App\Models\User;
use App\Enums\RoleType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\User as SocialUser;
use Illuminate\Validation\ValidationException;

class SocialAuthService
{
    /**
     * Redirect the user to the OAuth provider.
     */
    public function redirect(string $provider)
    {
        $this->validateProvider($provider);

        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        return $driver->stateless()->redirect();
    }

    /**
     * Obtain the user information from the provider.
     */
    public function callback(string $provider): SocialUser
    {
        $this->validateProvider($provider);

        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        return $driver->stateless()->user();
    }

    /**
     * Handle social login callback.
     */
    public function handleSocialLogin(string $provider): array
    {
        $socialUser = $this->callback($provider);
        $socialData = SocialLoginData::fromSocialUser($provider, $socialUser);

        return DB::transaction(function () use ($socialData) {
            // Find social account
            $socialAccount = SocialAccount::where('provider_name', $socialData->provider)
                ->where('provider_id', $socialData->providerId)
                ->first();

            if ($socialAccount) {
                // Existing social account - login
                $user = $socialAccount->user;

                if (!$user->is_active) {
                    throw ValidationException::withMessages([
                        'email' => ['Account is disabled.'],
                    ]);
                }

                /** @var \Tymon\JWTAuth\JWTGuard $guard */
                $guard = Auth::guard('api');
                $token = $guard->login($user);

                return [
                    'user' => $user,
                    'token' => $this->respondWithToken($token),
                    'is_new' => false,
                ];
            }

            // Check if user exists with the same email
            $user = User::where('email', $socialData->email)->first();

            if ($user) {
                // User exists but doesn't have this provider - link it
                if ($user->hasSocialAccount($socialData->provider)) {
                    throw ValidationException::withMessages([
                        'email' => ['This account is already connected to this provider.'],
                    ]);
                }

                $this->linkSocialAccount($user, $socialData);

                if (!$user->is_active) {
                    throw ValidationException::withMessages([
                        'email' => ['Account is disabled.'],
                    ]);
                }

                /** @var \Tymon\JWTAuth\JWTGuard $guard */
                $guard = Auth::guard('api');
                $token = $guard->login($user);

                return [
                    'user' => $user,
                    'token' => $this->respondWithToken($token),
                    'is_new' => false,
                ];
            }

            // Create new user
            $user = $this->createUserFromSocialData($socialData);
            $this->linkSocialAccount($user, $socialData);

            /** @var \Tymon\JWTAuth\JWTGuard $guard */
            $guard = Auth::guard('api');
            $token = $guard->login($user);

            return [
                'user' => $user,
                'token' => $this->respondWithToken($token),
                'is_new' => true,
            ];
        });
    }

    /**
     * Link social account to authenticated user.
     */
    public function linkAccount(User $user, string $provider, string $accessToken): SocialAccount
    {
        $this->validateProvider($provider);

        // Check if user already has this provider
        if ($user->hasSocialAccount($provider)) {
            throw ValidationException::withMessages([
                'provider' => ['This account is already connected to this provider.'],
            ]);
        }

        // Get user data from provider
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        // Get user data from provider
        $socialUser = $driver->userFromToken($accessToken);
        $socialData = SocialLoginData::fromSocialUser($provider, $socialUser);

        // Check if this social account is already linked to another user
        $existingAccount = SocialAccount::where('provider_name', $provider)
            ->where('provider_id', $socialData->providerId)
            ->first();

        if ($existingAccount) {
            throw ValidationException::withMessages([
                'provider' => ['This social account is already connected to another user.'],
            ]);
        }

        // Link the account
        return $this->linkSocialAccount($user, $socialData);
    }

    /**
     * Unlink social account from authenticated user.
     */
    public function unlinkAccount(User $user, string $provider): void
    {
        $this->validateProvider($provider);

        $socialAccount = $user->socialAccounts()->where('provider_name', $provider)->first();

        if (!$socialAccount) {
            throw ValidationException::withMessages([
                'provider' => ['Akun social ini tidak ditemukan.'],
            ]);
        }

        // Check if user has password (to prevent locking out)
        if (!$user->password && $user->socialAccounts()->count() <= 1) {
            throw ValidationException::withMessages([
                'provider' => ['Tidak bisa memutus koneksi social account. Anda harus memiliki password atau minimal satu social account aktif.'],
            ]);
        }

        $socialAccount->delete();
    }

    /**
     * Get linked social accounts for user.
     */
    public function getLinkedAccounts(User $user): array
    {
        return $user->socialAccounts->pluck('provider_name')->toArray();
    }

    /**
     * Validate provider name.
     */
    protected function validateProvider(string $provider): void
    {
        $validProviders = ['google', 'facebook'];

        if (!in_array($provider, $validProviders)) {
            throw ValidationException::withMessages([
                'provider' => ['Provider tidak valid.'],
            ]);
        }
    }

    /**
     * Create user from social data.
     */
    protected function createUserFromSocialData(SocialLoginData $data): User
    {
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => null, // OAuth users have null password
            'avatar' => $data->avatar,
            'is_active' => true,
            'locale' => 'id',
            'email_verified_at' => now(), // Auto-verify email from OAuth
        ]);

        // Assign default role
        $user->assignRole(RoleType::CUSTOMER->value);

        return $user;
    }

    /**
     * Link social account to user.
     */
    protected function linkSocialAccount(User $user, SocialLoginData $data): SocialAccount
    {
        return $user->socialAccounts()->create([
            'provider_name' => $data->provider,
            'provider_id' => $data->providerId,
            'provider_data' => [
                'name' => $data->name,
                'avatar' => $data->avatar,
            ],
        ]);
    }

    /**
     * Get token response.
     */
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
}
