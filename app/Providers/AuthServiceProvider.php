<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::policy(\App\Models\User::class, \App\Policies\User\UserPolicy::class);
        Gate::policy(\App\Models\UserAddress::class, \App\Policies\User\UserAddressPolicy::class);
        Gate::policy(\App\Models\Category::class, \App\Policies\Category\CategoryPolicy::class);
        Gate::policy(\App\Models\Brand::class, \App\Policies\Brand\BrandPolicy::class);
    }
}
