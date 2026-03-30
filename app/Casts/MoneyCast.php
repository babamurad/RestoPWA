<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): float
    {
        if ($value === null) {
            return 0.0;
        }

        return round((int) $value / 100, 2);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        if ($value === null) {
            return 0;
        }

        if (is_float($value)) {
            return (int) round($value * 100);
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value)) {
            return (int) round((float) $value * 100);
        }

        return (int) round($value * 100);
    }
}
