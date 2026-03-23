<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait HasFlexibleRouteBinding
{
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->when(\Illuminate\Support\Str::isUuid($value), function ($query) use ($value) {
            $query->where('id', $value);
        }, function ($query) use ($value) {
            $query->where('slug', $value);
        })
            ->firstOrFail();
    }
}
