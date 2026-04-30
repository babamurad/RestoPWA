<?php

declare(strict_types=1);

namespace App\Domains\Geo\Services;

use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoService
{
    private string $googleKey;

    private string $yandexKey;

    private string $driver;

    public function __construct()
    {
        $this->googleKey = (string) config('services.google_maps.key', '');
        $this->yandexKey = (string) config('services.yandex_maps.key', '');
        $this->driver = (string) config('services.geo_driver', 'google');
    }

    /**
     * Geocode an address to lat/lon using the configured driver.
     * Caches result for 1 hour.
     *
     * @return array{lat: float, lon: float, address: string}|null
     */
    public function geocodeAddress(string $address): ?array
    {
        $cacheKey = 'geocoding_'.$this->driver.'_'.md5($address);

        return Cache::remember($cacheKey, now()->addHour(), function () use ($address) {
            return match ($this->driver) {
                'yandex' => $this->geocodeViaYandex(['geocode' => $address]),
                'nominatim' => $this->geocodeViaNominatim(['q' => $address]),
                default => $this->geocodeViaGoogle(['address' => $address]),
            };
        });
    }

    /**
     * Reverse geocode coordinates to an address.
     * Caches result for 1 hour.
     *
     * @return array{lat: float, lon: float, address: string}|null
     */
    public function reverseGeocode(float $lat, float $lon): ?array
    {
        $cacheKey = 'reverse_geocoding_'.$this->driver.'_'.$lat.'_'.$lon;

        return Cache::remember($cacheKey, now()->addHour(), function () use ($lat, $lon) {
            return match ($this->driver) {
                'yandex' => $this->geocodeViaYandex(['geocode' => "{$lon},{$lat}"]),
                'nominatim' => $this->reverseGeocodeViaNominatim(['lat' => $lat, 'lon' => $lon]),
                default => $this->geocodeViaGoogle(['latlng' => "{$lat},{$lon}"]),
            };
        });
    }

    /**
     * Get address suggestions based on a partial query.
     *
     * @return array<int, array{address: string, lat: float, lon: float, kind: string}>
     */
    public function suggestAddresses(string $query): array
    {
        if (empty($query)) {
            return [];
        }

        return match ($this->driver) {
            'yandex' => $this->suggestAddressesViaYandex($query),
            'nominatim' => $this->suggestAddressesViaNominatim($query),
            default => $this->suggestAddressesViaGoogle($query),
        };
    }

    /**
     * Google Maps Geocoding implementation.
     */
    private function geocodeViaGoogle(array $params): ?array
    {
        try {
            $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', array_merge([
                'key' => $this->googleKey,
                'language' => 'ru',
            ], $params));

            if ($response->successful() && $response->json('status') === 'OK') {
                $result = $response->json('results.0');

                return [
                    'lat' => (float) $result['geometry']['location']['lat'],
                    'lon' => (float) $result['geometry']['location']['lng'],
                    'address' => (string) $result['formatted_address'],
                ];
            }

            Log::warning('Google Geocoding failed: '.($response->json('error_message') ?? $response->json('status')));
        } catch (\Exception $e) {
            Log::error('Google Geocoding exception: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Get address suggestions via Google Geocoding API.
     */
    private function suggestAddressesViaGoogle(string $query): array
    {
        if (empty($this->googleKey)) {
            return [];
        }

        try {
            $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $query,
                'key' => $this->googleKey,
                'language' => 'ru',
            ]);

            if ($response->successful() && $response->json('status') === 'OK') {
                $results = $response->json('results', []);

                return array_map(function ($item) {
                    return [
                        'address' => (string) $item['formatted_address'],
                        'lat' => (float) $item['geometry']['location']['lat'],
                        'lon' => (float) $item['geometry']['location']['lng'],
                        'kind' => implode(', ', $item['types'] ?? []),
                    ];
                }, array_slice($results, 0, 5));
            }
        } catch (\Exception $e) {
            Log::error('Google Address suggestion exception: '.$e->getMessage());
        }

        return [];
    }

    /**
     * Yandex Maps Geocoding implementation.
     */
    private function geocodeViaYandex(array $params): ?array
    {
        try {
            $response = Http::timeout(5)->get('https://geocode-maps.yandex.ru/1.x/', array_merge([
                'apikey' => $this->yandexKey,
                'format' => 'json',
                'lang' => 'ru_RU',
            ], $params));

            if ($response->successful()) {
                $feature = $response->json('response.GeoObjectCollection.featureMember.0.GeoObject');

                if ($feature) {
                    $pos = explode(' ', $feature['Point']['pos']); // Yandex returns "lon lat"

                    return [
                        'lon' => (float) ($pos[0] ?? 0),
                        'lat' => (float) ($pos[1] ?? 0),
                        'address' => (string) $feature['metaDataProperty']['GeocoderMetaData']['text'],
                    ];
                }
            }

            Log::warning('Yandex Geocoding failed for: '.json_encode($params));
        } catch (\Exception $e) {
            Log::error('Yandex Geocoding exception: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Get address suggestions via Yandex Geocoding API.
     */
    private function suggestAddressesViaYandex(string $query): array
    {
        if (empty($this->yandexKey)) {
            return [];
        }

        try {
            $response = Http::timeout(5)->get('https://geocode-maps.yandex.ru/1.x/', [
                'apikey' => $this->yandexKey,
                'geocode' => $query,
                'format' => 'json',
                'lang' => 'ru_RU',
                'results' => 5,
            ]);

            if ($response->successful()) {
                $features = $response->json('response.GeoObjectCollection.featureMember', []);

                return array_map(function ($item) {
                    $geo = $item['GeoObject'];
                    $pos = explode(' ', $geo['Point']['pos']);

                    return [
                        'address' => $geo['metaDataProperty']['GeocoderMetaData']['text'],
                        'lat' => (float) ($pos[1] ?? 0),
                        'lon' => (float) ($pos[0] ?? 0),
                        'kind' => $geo['metaDataProperty']['GeocoderMetaData']['kind'] ?? '',
                    ];
                }, $features);
            }
        } catch (\Exception $e) {
            Log::error('Yandex Address suggestion exception: '.$e->getMessage());
        }

        return [];
    }

    /**
     * Nominatim Geocoding implementation.
     */
    private function geocodeViaNominatim(array $params): ?array
    {
        try {
            $userAgent = 'RestoPWA/1.0 (' . config('app.url') . ')';
            $response = Http::withHeaders(['User-Agent' => $userAgent])
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/search', array_merge([
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 1,
                    'accept-language' => 'ru',
                ], $params));

            if ($response->successful() && !empty($response->json())) {
                $result = $response->json()[0];

                return [
                    'lat' => (float) $result['lat'],
                    'lon' => (float) $result['lon'],
                    'address' => (string) $result['display_name'],
                ];
            }

            Log::warning('Nominatim Geocoding failed for: ' . json_encode($params));
        } catch (\Exception $e) {
            Log::error('Nominatim Geocoding exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Nominatim Reverse Geocoding implementation.
     */
    private function reverseGeocodeViaNominatim(array $params): ?array
    {
        try {
            $userAgent = 'RestoPWA/1.0 (' . config('app.url') . ')';
            $response = Http::withHeaders(['User-Agent' => $userAgent])
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/reverse', array_merge([
                    'format' => 'json',
                    'addressdetails' => 1,
                    'accept-language' => 'ru',
                ], $params));

            if ($response->successful() && $response->json('display_name')) {
                $result = $response->json();

                return [
                    'lat' => (float) $result['lat'],
                    'lon' => (float) $result['lon'],
                    'address' => (string) $result['display_name'],
                ];
            }

            Log::warning('Nominatim Reverse Geocoding failed for: ' . json_encode($params));
        } catch (\Exception $e) {
            Log::error('Nominatim Reverse Geocoding exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get address suggestions via Nominatim Geocoding API.
     */
    private function suggestAddressesViaNominatim(string $query): array
    {
        try {
            $userAgent = 'RestoPWA/1.0 (' . config('app.url') . ')';
            $response = Http::withHeaders(['User-Agent' => $userAgent])
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 5,
                    'addressdetails' => 1,
                    'accept-language' => 'ru',
                ]);

            if ($response->successful()) {
                $results = $response->json();

                return array_map(function ($item) {
                    return [
                        'address' => (string) $item['display_name'],
                        'lat' => (float) $item['lat'],
                        'lon' => (float) $item['lon'],
                        'kind' => $item['type'] ?? $item['class'] ?? '',
                    ];
                }, $results);
            }
        } catch (\Exception $e) {
            Log::error('Nominatim Address suggestion exception: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Check if a point is within the delivery zone of a specific vendor.
     */
    public function isPointInDeliveryZone(float $lat, float $lon, string $vendorId): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return true;
        }

        try {
            $result = DB::selectOne('
                SELECT ST_Contains(delivery_zones, ST_SetSRID(ST_MakePoint(?, ?), 4326)) as is_inside
                FROM restaurants 
                WHERE id = ?
            ', [$lon, $lat, $vendorId]);

            return (bool) ($result?->is_inside ?? false);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains(strtolower($e->getMessage()), 'st_makepoint')) {
                Log::warning('PostGIS is not enabled. Bypassing delivery zone check.');
                return true;
            }
            throw $e;
        }
    }

    /**
     * Get active restaurants that cover the given point, sorted by distance.
     *
     * @return Collection<int, Restaurant>
     */
    public function getRestaurantsByPoint(float $lat, float $lon): Collection
    {
        $query = Restaurant::query();

        if (DB::getDriverName() === 'sqlite') {
            return $query->where('is_active', true)->get();
        }

        try {
            return $query
                ->select('*')
                ->selectRaw('ST_Distance(delivery_zones, ST_SetSRID(ST_MakePoint(?, ?), 4326)) as distance', [$lon, $lat])
                ->whereRaw('ST_Intersects(delivery_zones, ST_SetSRID(ST_MakePoint(?, ?), 4326))', [$lon, $lat])
                ->where('is_active', true)
                ->orderBy('distance')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains(strtolower($e->getMessage()), 'st_makepoint')) {
                Log::warning('PostGIS is not enabled. Returning all active restaurants.');
                return $query->where('is_active', true)->get();
            }
            throw $e;
        }
    }

    /**
     * Calculate delivery fee for a specific vendor at a given point.
     */
    public function calculateDeliveryFee(float $lat, float $lon, string $vendorId): float
    {
        $restaurant = Restaurant::find($vendorId);

        if (! $restaurant) {
            return (float) env('DELIVERY_FEE_DEFAULT', 5.0);
        }

        if (! $this->isPointInDeliveryZone($lat, $lon, $vendorId)) {
            return (float) ($restaurant->settings['delivery_fee_outside'] ?? env('DELIVERY_FEE_DEFAULT', 5.0));
        }

        return (float) ($restaurant->settings['delivery_fee'] ?? env('DELIVERY_FEE_DEFAULT', 5.0));
    }
}
