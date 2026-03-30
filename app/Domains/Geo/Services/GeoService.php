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
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google_maps.key', '');
    }

    /**
     * Geocode an address to lat/lon using Google Maps API.
     * Caches result for 1 hour.
     * 
     * @return array{lat: float, lon: float, address: string}|null
     */
    public function geocodeAddress(string $address): ?array
    {
        $cacheKey = 'geocoding_' . md5($address);

        return Cache::remember($cacheKey, now()->addHour(), function () use ($address) {
            try {
                $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $address,
                    'key' => $this->apiKey,
                    'language' => 'ru', // Preferred for Turkmenistan
                ]);

                if ($response->successful() && $response->json('status') === 'OK') {
                    $result = $response->json('results.0');
                    
                    return [
                        'lat' => (float) $result['geometry']['location']['lat'],
                        'lon' => (float) $result['geometry']['location']['lng'],
                        'address' => (string) $result['formatted_address'],
                    ];
                }

                Log::warning('Geocoding failed for address: ' . $address, [
                    'status' => $response->json('status'),
                    'error_message' => $response->json('error_message'),
                ]);

            } catch (\Exception $e) {
                Log::error('Geocoding exception: ' . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Check if a point is within the delivery zone of a specific vendor.
     */
    public function isPointInDeliveryZone(float $lat, float $lon, string $vendorId): bool
    {
        $result = DB::selectOne("
            SELECT ST_Contains(delivery_zones, ST_SetSRID(ST_MakePoint(?, ?), 4326)) as is_inside
            FROM restaurants 
            WHERE id = ?
        ", [$lon, $lat, $vendorId]);

        return (bool) ($result?->is_inside ?? false);
    }

    /**
     * Get active restaurants that cover the given point, sorted by distance.
     * 
     * @return Collection<int, Restaurant>
     */
    public function getRestaurantsByPoint(float $lat, float $lon): Collection
    {
        // ST_Distance calculation for sorting
        return Restaurant::query()
            ->select('*')
            ->selectRaw("ST_Distance(delivery_zones, ST_SetSRID(ST_MakePoint(?, ?), 4326)) as distance", [$lon, $lat])
            ->whereRaw("ST_Intersects(delivery_zones, ST_SetSRID(ST_MakePoint(?, ?), 4326))", [$lon, $lat])
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

        if (!$restaurant) {
            return (float) env('DELIVERY_FEE_DEFAULT', 5.0);
        }

        if (!$this->isPointInDeliveryZone($lat, $lon, $vendorId)) {
            return (float) ($restaurant->settings['delivery_fee_outside'] ?? env('DELIVERY_FEE_DEFAULT', 5.0));
        }

        // Potential logic for multiple zones with different fees in settings
        // Example schema: settings['delivery_zones_fees'] = [['zone_id' => 1, 'fee' => 3.0], ...]
        return (float) ($restaurant->settings['delivery_fee'] ?? env('DELIVERY_FEE_DEFAULT', 5.0));
    }
}
