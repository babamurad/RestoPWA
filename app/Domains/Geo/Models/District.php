<?php

declare(strict_types=1);

namespace App\Domains\Geo\Models;

use App\Domains\Geo\Services\GeoJsonNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * A city district (shared for all restaurants). Each restaurant sets its own
 * delivery price per district in RestaurantDeliveryPrice.
 */
class District extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'city', 'boundary', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Stored as a normalized GeoJSON MultiPolygon whatever the map editor sends
     * (Polygon, Feature, FeatureCollection, JSON string).
     */
    public function setBoundaryAttribute(mixed $value): void
    {
        $normalized = GeoJsonNormalizer::toMultiPolygon($value);
        $this->attributes['boundary'] = $normalized ? json_encode($normalized) : null;
    }

    public function getBoundaryAttribute(?string $value): ?array
    {
        return $value ? json_decode($value, true) : null;
    }

    /**
     * Point-in-polygon (ray casting) over the MultiPolygon, holes respected.
     * Done in PHP so it works the same on MySQL, PostgreSQL and SQLite.
     */
    public function contains(float $lat, float $lon): bool
    {
        foreach ($this->boundary['coordinates'] ?? [] as $polygon) {
            $rings = array_values($polygon);
            if ($rings === [] || ! self::ringContains($rings[0], $lon, $lat)) {
                continue;
            }

            $inHole = false;
            foreach (array_slice($rings, 1) as $hole) {
                if (self::ringContains($hole, $lon, $lat)) {
                    $inHole = true;
                    break;
                }
            }

            if (! $inHole) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int, array{0: float|int, 1: float|int}> $ring [lon, lat] points */
    private static function ringContains(array $ring, float $x, float $y): bool
    {
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            [$xi, $yi] = [(float) $ring[$i][0], (float) $ring[$i][1]];
            [$xj, $yj] = [(float) $ring[$j][0], (float) $ring[$j][1]];

            if ((($yi > $y) !== ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi)) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
