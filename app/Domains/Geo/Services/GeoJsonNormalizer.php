<?php

declare(strict_types=1);

namespace App\Domains\Geo\Services;

final class GeoJsonNormalizer
{
    /**
     * Normalize various GeoJSON formats (Polygon, MultiPolygon, Feature, FeatureCollection)
     * into a standard GeoJSON MultiPolygon array structure:
     * [
     *     'type' => 'MultiPolygon',
     *     'coordinates' => [
     *         [ // Polygon 1
     *             [ // Exterior ring
     *                 [lon, lat], [lon, lat], ...
     *             ]
     *         ]
     *     ]
     * ]
     */
    public static function toMultiPolygon(mixed $input): ?array
    {
        if (empty($input)) {
            return null;
        }

        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (!is_array($decoded)) {
                return null;
            }
            $input = $decoded;
        }

        if (!is_array($input)) {
            return null;
        }

        // Handle FeatureCollection
        if (isset($input['type']) && $input['type'] === 'FeatureCollection') {
            $polygons = [];
            foreach ($input['features'] ?? [] as $feature) {
                $geom = $feature['geometry'] ?? null;
                if ($geom) {
                    $normalized = self::extractPolygons($geom);
                    if ($normalized) {
                        $polygons = array_merge($polygons, $normalized);
                    }
                }
            }
            return self::buildMultiPolygon($polygons);
        }

        // Handle Feature
        if (isset($input['type']) && $input['type'] === 'Feature') {
            $geom = $input['geometry'] ?? null;
            return $geom ? self::toMultiPolygon($geom) : null;
        }

        // Handle directly geometries
        $polygons = self::extractPolygons($input);
        return self::buildMultiPolygon($polygons);
    }

    /**
     * Helper to extract polygons (arrays of coordinate rings) from a geometry.
     *
     * @return array<int, array<mixed>>
     */
    private static function extractPolygons(array $geometry): array
    {
        $type = $geometry['type'] ?? '';
        $coords = $geometry['coordinates'] ?? [];

        if ($type === 'Polygon') {
            return [$coords];
        }

        if ($type === 'MultiPolygon') {
            return $coords;
        }

        if ($type === 'GeometryCollection') {
            $polygons = [];
            foreach ($geometry['geometries'] ?? [] as $geom) {
                $polygons = array_merge($polygons, self::extractPolygons($geom));
            }
            return $polygons;
        }

        return [];
    }

    /**
     * Build standard MultiPolygon array from a list of Polygon coordinate rings.
     */
    private static function buildMultiPolygon(array $polygons): ?array
    {
        if (empty($polygons)) {
            return null;
        }

        // Ensure all rings are closed and coordinate pairs are valid [lon, lat]
        $validPolygons = [];
        foreach ($polygons as $polygon) {
            $validRings = [];
            foreach ($polygon as $ring) {
                if (!is_array($ring) || count($ring) < 3) {
                    continue;
                }
                
                $validRing = [];
                foreach ($ring as $point) {
                    if (is_array($point) && count($point) >= 2) {
                        $validRing[] = [(float)$point[0], (float)$point[1]];
                    }
                }

                if (count($validRing) >= 3) {
                    // Close the ring if not closed
                    $first = $validRing[0];
                    $last = $validRing[count($validRing) - 1];
                    if ($first[0] !== $last[0] || $first[1] !== $last[1]) {
                        $validRing[] = [$first[0], $first[1]];
                    }
                    $validRings[] = $validRing;
                }
            }
            if (!empty($validRings)) {
                $validPolygons[] = $validRings;
            }
        }

        if (empty($validPolygons)) {
            return null;
        }

        return [
            'type' => 'MultiPolygon',
            'coordinates' => $validPolygons,
        ];
    }
}
