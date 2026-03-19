<?php

namespace App\Services;

use App\DTOs\User\Address\SaveUserAddressData;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class UserAddressService
{
    public function getAll(User $user): Collection
    {
        $cacheKey = "user:addresses:{$user->id}";

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($user) {
            return $user->addresses()
                ->with(['province', 'city'])
                ->orderByDesc('is_default')
                ->latest()
                ->get();
        });
    }

    public function store(User $user, SaveUserAddressData $data): UserAddress
    {
        if ($data->is_default) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($data->toArray());

        // Invalidate cache
        Cache::forget("user:addresses:{$user->id}");

        return $address;
    }

    public function update(User $user, UserAddress $address, SaveUserAddressData $data): UserAddress
    {
        if ($data->is_default && ! $address->is_default) {
            $user->addresses()
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($data->toArray());

        // Invalidate cache
        Cache::forget("user:addresses:{$user->id}");

        return $address->refresh();
    }

    public function delete(User $user, UserAddress $address): void
    {
        $address->delete();

        // Invalidate cache
        Cache::forget("user:addresses:{$user->id}");
    }
}
