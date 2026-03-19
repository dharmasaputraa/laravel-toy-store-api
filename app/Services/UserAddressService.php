<?php

namespace App\Services;

use App\DTOs\User\Address\SaveUserAddressData;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserAddressService
{
    private function getAuthUser()
    {
        return Auth::guard('api')->user();
    }

    public function getAll(): Collection
    {
        $user = $this->getAuthUser();

        $cacheKey = "user:addresses:{$user->id}";

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($user) {
            return $user->addresses()
                ->orderByDesc('is_default')
                ->latest()
                ->get();
        });
    }

    public function store(SaveUserAddressData $data): UserAddress
    {
        $user = $this->getAuthUser();

        if ($data->is_default) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($data->toArray());

        // Invalidate cache
        Cache::forget("user:addresses:{$user->id}");

        return $address;
    }

    public function update(UserAddress $address, SaveUserAddressData $data): UserAddress
    {
        $user = $this->getAuthUser();

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

    public function delete(UserAddress $address): void
    {
        $user = $this->getAuthUser();

        $address->delete();

        // Invalidate cache
        Cache::forget("user:addresses:{$user->id}");
    }
}
