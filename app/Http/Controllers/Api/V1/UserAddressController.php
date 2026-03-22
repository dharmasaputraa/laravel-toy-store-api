<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\User\Address\SaveUserAddressData;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\V1\User\Address\DeleteUserAddressRequest;
use App\Http\Requests\V1\User\Address\StoreUserAddressRequest;
use App\Http\Requests\V1\User\Address\UpdateUserAddressRequest;
use App\Http\Resources\V1\User\UserAddressResource;
use App\Models\UserAddress;
use App\Services\UserAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserAddressController extends BaseApiController
{
    public function __construct(
        protected UserAddressService $addressService
    ) {}

    public function index(): JsonResponse
    {
        $user = $this->getAuthUser();
        $addresses = $this->addressService->getAll($user);

        return $this->successResponse(
            UserAddressResource::collection($addresses)
        );
    }

    public function store(StoreUserAddressRequest $request): JsonResponse
    {
        $user = $this->getAuthUser();
        $data = SaveUserAddressData::fromRequest($request);

        $address = $this->addressService->store($user, $data);

        return $this->successResponse(
            new UserAddressResource($address)
        );
    }

    public function update(UpdateUserAddressRequest $request, UserAddress $address): JsonResponse
    {
        $user = $this->getAuthUser();
        $data = SaveUserAddressData::fromRequest($request);

        $updatedAddress = $this->addressService->update($user, $address, $data);

        return $this->successResponse(
            new UserAddressResource($updatedAddress)
        );
    }

    public function destroy(DeleteUserAddressRequest $request, UserAddress $address): JsonResponse
    {
        $user = $this->getAuthUser();
        $this->addressService->delete($user, $address);

        return $this->successResponse([
            'message' => 'Address deleted successfully.',
        ]);
    }
}
