<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\ThrottlesRequests;
use Illuminate\Support\Facades\Auth;

class BaseApiController extends Controller
{
    use ApiResponse, ThrottlesRequests;

    /**
     * Get the authenticated user from API guard.
     *
     * @return User
     */
    protected function getAuthUser(): User
    {
        return Auth::guard('api')->user();
    }
}
