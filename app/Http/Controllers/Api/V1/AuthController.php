<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\User\Auth\LoginData;
use App\DTOs\User\Auth\RegisterData;
use App\DTOs\User\Auth\ResetPasswordData;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\V1\Auth\LinkSocialAccountRequest;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Requests\V1\Auth\RegisterRequest;
use App\Http\Requests\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\V1\Auth\SocialAccountResource;
use App\Http\Resources\V1\User\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Services\SocialAuthService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    public function __construct(
        protected AuthService $authService,
        protected SocialAuthService $socialAuthService
    ) {}

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

    public function verifyEmail(string $id, string $hash): JsonResponse
    {
        if (! URL::hasValidSignature(request())) {
            throw ValidationException::withMessages([
                'email' => ['Invalid or expired verification link.'],
            ]);
        }

        $this->authService->verifyEmail($id, $hash);

        return $this->successResponse(null, 'Email verified successfully');
    }

    public function resendVerificationEmail(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $this->authService->resendVerificationEmail($user);

        return $this->successResponse(null, 'Verification link sent successfully');
    }

    public function redirectToProvider(string $provider): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return $this->socialAuthService->redirect($provider);
    }

    public function handleProviderCallback(string $provider): JsonResponse
    {
        $result = $this->socialAuthService->handleSocialLogin($provider);

        $message = $result['is_new']
            ? 'Registration successful via ' . ucfirst($provider)
            : 'Login successful via ' . ucfirst($provider);

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'is_new' => $result['is_new'],
        ], $message);
    }

    public function linkSocialAccount(LinkSocialAccountRequest $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $socialAccount = $this->socialAuthService->linkAccount(
            $user,
            $request->validated('provider'),
            $request->validated('access_token')
        );

        return $this->successResponse(
            new SocialAccountResource($socialAccount),
            'Social account linked successfully'
        );
    }

    public function unlinkSocialAccount(string $provider): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $this->socialAuthService->unlinkAccount($user, $provider);

        return $this->successResponse(null, 'Social account unlinked successfully');
    }

    public function getLinkedAccounts(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $linkedAccounts = $this->socialAuthService->getLinkedAccounts($user);

        return $this->successResponse([
            'providers' => $linkedAccounts,
        ], 'Linked accounts retrieved successfully');
    }
}
