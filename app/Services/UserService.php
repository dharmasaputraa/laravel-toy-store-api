<?php

namespace App\Services;

use App\DTOs\User\Profile\ChangePasswordData;
use App\DTOs\User\Profile\UpdateProfileData;
use App\DTOs\User\Profile\UploadAvatarData;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function me(): User
    {
        return Auth::guard('api')->user();
    }

    public function profile(): User
    {
        $user = $this->me();

        $cacheKey = "user:profile:{$user->id}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user) {
            return $user->load('roles');
        });
    }

    public function updateProfile(UpdateProfileData $data): User
    {
        $user = $this->me();

        $user->update($data->toArray());

        // Invalidate cache
        Cache::forget("user:profile:{$user->id}");

        return $user->refresh();
    }

    public function uploadAvatar(UploadAvatarData $data): User
    {
        $user = $this->me();

        $path = $data->avatar->store('avatars', 's3');

        $user->update([
            'avatar' => $path,
        ]);

        // Invalidate cache
        Cache::forget("user:profile:{$user->id}");

        return $user;
    }

    public function changePassword(ChangePasswordData $data): User
    {
        $user = $this->me();

        // Update the password (Laravel will hash it automatically due to the model cast)
        $user->update([
            'password' => $data->password,
        ]);

        // Invalidate cache
        Cache::forget("user:profile:{$user->id}");

        // Invalidate all tokens for security (force user to login again)
        Auth::guard('api')->logout();

        return $user->refresh();
    }
}
