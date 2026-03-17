<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Traits\ThrottlesRequests;

class BaseApiController extends Controller
{
    use ApiResponse, ThrottlesRequests;
}
