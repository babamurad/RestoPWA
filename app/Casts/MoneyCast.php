<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Money attribute: the database keeps whole minor units (tyiyn, 1/100 TMT),
 * the application always works in currency units (TMT).
 *
 * Reading gives a float (1999 in the column → 19.99).
 * Writing takes currency units in any numeric form — 19.99, 20, "19.99" — and
 * never minor units: `$order->total = 20` means 20 TMT, exactly like 20.0 does.
 * An amount that is in minor units (the order API works in them) is converted
 * with MoneyCast::fromMinor() before it is assigned.
 */
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
        if ($value === null || $value === '') {
            return 0;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Money attribute [{$key}] must be a number, got ".get_debug_type($value).'.');
        }

        return (int) round((float) $value * 100);
    }

    /** Minor units (tyiyn) → currency units (TMT): 1999 → 19.99 */
    public static function fromMinor(int|float|string $minorUnits): float
    {
        return round((float) $minorUnits / 100, 2);
    }

    /** Currency units (TMT) → whole minor units (tyiyn): 19.99 → 1999 */
    public static function toMinor(int|float|string $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
