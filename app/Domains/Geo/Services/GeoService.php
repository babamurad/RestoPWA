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
                'yandex' => $this->geocodeViaYandex($address),
                default => $this->geocodeViaGoogle($address),
            };
        });
    }

    /**
     * Google Maps Geocoding implementation.
     */
    private function geocodeViaGoogle(string $address): ?array
    {
        try {
            $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $this->googleKey,
                'language' => 'ru',
            ]);

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
     * Yandex Maps Geocoding implementation.
     */
    private function geocodeViaYandex(string $address): ?array
    {
        try {
            $response = Http::timeout(5)->get('https://geocode-maps.yandex.ru/1.x/', [
                'apikey' => $this->yandexKey,
                'geocode' => $address,
                'format' => 'json',
                'lang' => 'ru_RU',
            ]);

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

            Log::warning('Yandex Geocoding failed for: '.$address);
        } catch (\Exception $e) {
            Log::error('Yandex Geocoding exception: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Check if a point is within the delivery zone of a specific vendor.
     */
    public function isPointInDeliveryZone(float $lat, float $lon, string $vendorId): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return true;
        }

        $result = DB::selectOne('
            SELECT ST_Contains(delivery_zones, ST_SetSRID(ST_MakePoint(?, ?), 4326)) as is_inside
            FROM restaurants 
            WHERE id = ?
        ', [$lon, $lat, $vendorId]);

        return (bool) ($result?->is_inside ?? false);
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

        return $query
            ->select('*')
            ->selectRaw('ST_Distance(delivery_zones, ST_SetSRID(ST_MakePoint(?, ?), 4326)) as distance', [$lon, $lat])
            ->whereRaw('ST_Intersects(delivery_zones, ST_SetSRID(ST_MakePoint(?, ?), 4326))', [$lon, $lat])
            ->where('is_active', true)
            ->orderBy('distance')
            ->get();
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
