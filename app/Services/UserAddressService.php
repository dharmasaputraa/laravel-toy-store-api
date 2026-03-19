<?php

namespace App\Services;

use App\DTOs\User\Address\SaveUserAddressData;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class UserAddressService
{
    private function getAuthUser()
    {
        return Auth::guard('api')->user();
    }

    public function getAll(): Collection
    {
        return $this->getAuthUser()
            ->addresses()
            ->orderByDesc('is_default')
            ->latest()
            ->get();
    }

    public function store(SaveUserAddressData $data): UserAddress
    {
        $user = $this->getAuthUser();

        if ($data->is_default) {
            $user->addresses()->update(['is_default' => false]);
        }

        return $user->addresses()->create($data->toArray());
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

        return $address->refresh();
    }

    public function delete(UserAddress $address): void
    {
        $address->delete();
    }
}
