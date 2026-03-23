<?php

namespace App\Services;

use App\DTOs\User\Profile\ChangePasswordData;
use App\DTOs\User\Profile\UpdateProfileData;
use App\DTOs\User\Profile\UploadAvatarData;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserService
{
    public function profile(User $user): User
    {
        return Cache::tags(['users'])->remember(
            "user:profile:{$user->id}",
            now()->addMinutes(30),
            fn() => $user->load('roles')
        );
    }

    public function updateProfile(User $user, UpdateProfileData $data): User
    {
        $user->update($data->toArray());

        $this->clearCache($user);

        return $user->refresh();
    }

    public function uploadAvatar(User $user, UploadAvatarData $data): User
    {
        $user
            ->addMedia($data->avatar)
            ->toMediaCollection('avatar');

        Cache::tags(['users', 'avatar'])->flush();

        return $user->refresh();
    }

    public function changePassword(User $user, ChangePasswordData $data): User
    {
        $user->update([
            'password' => $data->password,
        ]);

        $this->clearCache($user);

        Auth::guard('api')->logout();

        return $user->refresh();
    }

    private function clearCache(): void
    {
        Cache::tags(['users'])->flush();
    }
}
