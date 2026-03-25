<?php

namespace App\Casts;

use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MoneyCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::make(
            (float) $value,
            $model->currency ?? 'IDR'
        );
    }

    public function set($model, string $key, $value, array $attributes): array
    {
        if ($value instanceof Money) {
            return [$key => $value->raw()];
        }

        return [$key => $value];
    }
}
