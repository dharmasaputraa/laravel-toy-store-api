<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => $this->resource->success,
            'message' => $this->resource->message,
            'data' => $this->resource->data,
        ];
    }
}
