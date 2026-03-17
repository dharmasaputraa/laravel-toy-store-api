<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\User\Auth\LoginData;
use App\DTOs\User\Auth\RegisterData;
use App\DTOs\User\Auth\ResetPasswordData;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Requests\V1\Auth\RegisterRequest;
use App\Http\Requests\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function me(): JsonResponse
    {
        $user = $this->authService->me();

        return $this->successResponse(
            new UserResource($user)
        );
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register(
            RegisterData::fromRequest($request)
        );

        return $this->successResponse(
            new UserResource($user),
            'Registration successful',
            201
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $this->checkTooManyFailedAttempts($request, 5, 'email');

        $data = LoginData::fromRequest($request);

        try {
            $token = $this->authService->login($data);

            $this->clearThrottleLimiter($request, 'email');

            return $this->successResponse([
                'user' => new UserResource(Auth::guard('api')->user()),
                'token' => $token,
            ]);
        } catch (ValidationException $e) {
            $this->hitThrottleLimiter($request, 60, 'email');
            throw $e;
        }
    }

    public function refresh(): JsonResponse
    {
        $tokenData = $this->authService->refresh();

        return $this->successResponse($tokenData, 'Token refreshed successfully');
    }

    public function revokeToken(): JsonResponse
    {
        $this->authService->revokeToken();

        return $this->successResponse(null, 'Token revoked successfully');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetLink($request->validated('email'));

        return $this->successResponse(null, 'Password reset link has been sent to your email');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = ResetPasswordData::fromRequest($request);

        $status = Password::broker()->reset(
            $data->toArray(),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $this->successResponse(null, __($status));
    }
}
