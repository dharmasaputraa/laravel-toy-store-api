<?php

use App\Enums\RoleType;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'basic']);

Route::middleware(['auth:api', 'role:' . RoleType::SUPER_ADMIN->value])->group(function () {
    Route::get('/health/full', [HealthController::class, 'full']);
});

Route::prefix('auth')->group(function () {
    $strictThrottle = app()->isLocal() ? 'throttle:100,1' : 'throttle:3,1';
    $loginThrottle  = app()->isLocal() ? 'throttle:100,1' : 'throttle:30,1';

    Route::middleware($strictThrottle)->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    Route::middleware($loginThrottle)->post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/logout', [AuthController::class, 'revokeToken']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('profile')->controller(UserController::class)->group(function () {
        Route::get('/', 'me');
        Route::put('/', 'update');
        Route::post('/avatar', 'uploadAvatar');
        Route::post('/change-password', 'changePassword');
    });
});
