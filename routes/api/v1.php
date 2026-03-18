<?php

use App\Enums\RoleType;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health
|--------------------------------------------------------------------------
*/

Route::get('/health', [HealthController::class, 'basic'])->name('health');

Route::middleware(['auth:api', 'verified', 'role:' . RoleType::SUPER_ADMIN->value])
    ->get('/health/full', [HealthController::class, 'full'])
    ->name('health.full');


/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->name('auth.')->group(function () {

    $strictThrottle = app()->isLocal() ? 'throttle:100,1' : 'throttle:3,1';
    $loginThrottle  = app()->isLocal() ? 'throttle:100,1' : 'throttle:30,1';

    // Public (strict)
    Route::middleware($strictThrottle)->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.forgot');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
    });

    // Login (custom throttle)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware($loginThrottle)
        ->name('login');

    // Authenticated
    Route::middleware('auth:api')->group(function () {
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('token.refresh');
        Route::post('/revoke', [AuthController::class, 'revokeToken'])->name('token.revoke');

        Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
            ->name('email.verification.resend');
    });

    // Email verification (signed URL)
    Route::middleware('signed')
        ->get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('email.verification.verify');
});


/*
|--------------------------------------------------------------------------
| User Profile
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')
    ->prefix('profile')
    ->name('user.')
    ->controller(UserController::class)
    ->group(function () {

        Route::get('/', 'me')->name('profile');

        Route::middleware('verified')->group(function () {
            Route::put('/', 'update')->name('profile.update');
            Route::post('/avatar', 'uploadAvatar')->name('profile.avatar.upload');
            Route::post('/change-password', 'changePassword')->name('password.change');
        });
    });
