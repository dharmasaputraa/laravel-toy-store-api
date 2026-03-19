<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\User\Profile\ChangePasswordData;
use App\DTOs\User\Profile\UpdateProfileData;
use App\DTOs\User\Profile\UploadAvatarData;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\Profile\ChangePasswordRequest;
use App\Http\Requests\V1\User\Profile\UpdateProfileRequest;
use App\Http\Requests\V1\User\Profile\UploadAvatarRequest;
use App\Http\Resources\V1\UserResource;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class UserController extends BaseApiController
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function me(): JsonResponse
    {
        $user = $this->userService->profile();

        return $this->successResponse(
            new UserResource($user)
        );
    }

    public function update(UpdateProfileRequest $request, UserService $service)
    {
        $data = UpdateProfileData::fromRequest($request);

        $user = $service->updateProfile($data);

        return $this->successResponse(
            new UserResource($user)
        );
    }

    public function uploadAvatar(UploadAvatarRequest $request, UserService $service)
    {
        $data = UploadAvatarData::fromRequest($request);

        $user = $service->uploadAvatar($data);

        return $this->successResponse(
            new UserResource($user)
        );
    }

    public function changePassword(ChangePasswordRequest $request, UserService $service)
    {
        $data = ChangePasswordData::fromRequest($request);

        $service->changePassword($data);

        return $this->successResponse([
            'message' => 'Password changed successfully. Please login again.',
        ]);
    }
}
