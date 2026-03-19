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
use Illuminate\Support\Facades\Auth;


class UserController extends BaseApiController
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function me(): JsonResponse
    {
        $user = $this->getAuthUser();
        $user = $this->userService->profile($user);

        return $this->successResponse(
            new UserResource($user)
        );
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $this->getAuthUser();
        $data = UpdateProfileData::fromRequest($request);

        $user = $this->userService->updateProfile($user, $data);

        return $this->successResponse(
            new UserResource($user)
        );
    }

    public function uploadAvatar(UploadAvatarRequest $request)
    {
        $user = $this->getAuthUser();
        $data = UploadAvatarData::fromRequest($request);

        $user = $this->userService->uploadAvatar($user, $data);

        return $this->successResponse(
            new UserResource($user)
        );
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $this->getAuthUser();
        $data = ChangePasswordData::fromRequest($request);

        $this->userService->changePassword($user, $data);

        return $this->successResponse([
            'message' => 'Password changed successfully. Please login again.',
        ]);
    }
}
