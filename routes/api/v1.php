<?php

use App\Enums\RoleType;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Product\ProductTagController;
use App\Http\Controllers\Api\V1\RegionController;
use App\Http\Controllers\Api\V1\UserAddressController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health
|--------------------------------------------------------------------------
*/

Route::prefix('health')->name('health.')->group(function () {
    Route::get('/basic', [HealthController::class, 'basic'])->name('basic');

    Route::middleware(['auth:api', 'verified', 'active', 'role:' . RoleType::SUPER_ADMIN->value]) // Nanti Ganti dengan permission: view-full-health
        ->get('/full', [HealthController::class, 'full'])
        ->name('full');
});

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

    // OAuth (Public)
    Route::get('/social/{provider}/redirect', [AuthController::class, 'redirectToProvider'])
        ->name('social.redirect');
    Route::get('/social/{provider}/callback', [AuthController::class, 'handleProviderCallback'])
        ->name('social.callback');

    // Authenticated
    Route::middleware(['auth:api', 'active'])->group(function () {
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('token.refresh');
        Route::post('/revoke', [AuthController::class, 'revokeToken'])->name('token.revoke');

        Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
            ->name('email.verification.resend');

        // Social Account Management
        Route::post('/social/link', [AuthController::class, 'linkSocialAccount'])
            ->name('social.link');
        Route::delete('/social/unlink/{provider}', [AuthController::class, 'unlinkSocialAccount'])
            ->name('social.unlink');
        Route::get('/social/accounts', [AuthController::class, 'getLinkedAccounts'])
            ->name('social.accounts');
    });

    // Email verification (signed URL)
    Route::middleware('signed')
        ->get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('email.verification.verify');
});


/*
|--------------------------------------------------------------------------
| Regions (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('regions')->name('regions.')->group(function () {
    Route::get('/', [RegionController::class, 'index'])->name('index');
    Route::get('/{code}/cities', [RegionController::class, 'cities'])->name('cities');
});

/*
|--------------------------------------------------------------------------
| User Profile
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'active'])
    ->prefix('profile')
    ->as('user.')
    ->group(function () {

        Route::get('/', [UserController::class, 'me'])->name('me');

        Route::middleware('verified')->group(function () {

            // Profile
            Route::put('/', [UserController::class, 'update'])->name('update');
            Route::post('/avatar', [UserController::class, 'uploadAvatar'])->name('avatar.store');
            Route::put('/change-password', [UserController::class, 'changePassword'])->name('password.update');

            // Addresses
            Route::apiResource('addresses', UserAddressController::class)
                ->except(['show', 'create', 'edit']);
        });
    });

/*
|--------------------------------------------------------------------------
| Categories (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('categories')->as('categories.')->group(function () {

    Route::get('/tree', [CategoryController::class, 'tree'])->name('tree');

    Route::middleware(['auth:api', 'verified', 'active', 'role:' . RoleType::SUPER_ADMIN->value])->group(function () { // Nanti Ganti dengan permission: manage-categories
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::patch('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');

        Route::patch('/{category}/parent', [CategoryController::class, 'updateParent'])->name('parent.update');
        Route::patch('/{category}/status', [CategoryController::class, 'updateStatus'])->name('status.update');
        Route::post('/{category}/image', [CategoryController::class, 'updateImage'])->name('image.update');
    });
});

/*
|--------------------------------------------------------------------------
| Brands (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('brands')->as('brands.')->group(function () {

    Route::get('/', [BrandController::class, 'index'])->name('index');
    Route::get('/{brand}', [BrandController::class, 'show'])->name('show');
    Route::get('/{brand}/products', [BrandController::class, 'products'])->name('products');

    Route::middleware(['auth:api', 'verified', 'active', 'role:' . RoleType::SUPER_ADMIN->value])->group(function () { // Nanti Ganti dengan permission: manage-brands
        Route::post('/', [BrandController::class, 'store'])->name('store');
        Route::patch('/{brand}', [BrandController::class, 'update'])->name('update');
        Route::delete('/{brand}', [BrandController::class, 'destroy'])->name('destroy');

        Route::patch('/{brand}/status', [BrandController::class, 'updateStatus'])->name('status.update');
        Route::post('/{brand}/logo', [BrandController::class, 'updateLogo'])->name('logo.update');
    });
});

/*
|--------------------------------------------------------------------------
| Product Tags (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('tags')->as('tags.')->group(function () {
    // Public endpoints
    Route::get('/', [ProductTagController::class, 'index'])->name('index');
    Route::get('/{productTag}', [ProductTagController::class, 'show'])->name('show');
    Route::get('/{productTag}/products', [ProductTagController::class, 'products'])->name('products');

    // Admin endpoints
    Route::middleware(['auth:api', 'verified', 'active', 'role:' . RoleType::SUPER_ADMIN->value])->group(function () { // Nanti Ganti dengan permission: manage-product-tags
        Route::post('/', [ProductTagController::class, 'store'])->name('store');
        Route::patch('/{productTag}', [ProductTagController::class, 'update'])->name('update');
        Route::delete('/{productTag}', [ProductTagController::class, 'destroy'])->name('destroy');
    });
});
