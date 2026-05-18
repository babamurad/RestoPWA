<?php

declare(strict_types=1);

namespace App\Domains\Geo\Services;

use App\Domains\Geo\Models\GeocodingLog;
use App\Domains\Geo\Models\LocalPlace;
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

    private array $providerOrder;

    public function __construct()
    {
        $this->googleKey = (string) config('services.google_maps.key', '');
        $this->yandexKey = (string) config('services.yandex_maps.key', '');
        $this->driver = (string) config('services.geo_driver', 'google');
        $this->providerOrder = (array) config('services.geo_providers', ['local', 'yandex', 'nominatim', 'google']);
    }

    /**
     * Geocode an address to lat/lon using the configured driver.
     * Caches result for 1 hour.
     *
     * @return array{lat: float, lon: float, address: string, source: string, provider: string|null}|null
     */
    public function geocodeAddress(string $query, string $city = 'Туркменабат'): ?array
    {
        $cacheKey = 'geocoding_'.$this->driver.'_'.md5($query);

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = match ($this->driver) {
            'yandex' => $this->geocodeViaYandex(['geocode' => $query]),
            'nominatim' => $this->geocodeViaNominatim(['q' => $query]),
            default => $this->geocodeViaGoogle(['address' => $query]),
        };

        if ($result !== null) {
            $result['source'] = 'manual_geocoded';
            $result['provider'] = $this->driver;

            $shortCache = $this->checkQuality($result) ? now()->addHour() : now()->addMinutes(5);
            Cache::put($cacheKey, $result, $shortCache);

            $this->logGeocodingEvent('success', $this->driver, $query, $result['lat'], $result['lon']);

            return $result;
        }

        Cache::put($cacheKey, null, now()->addMinutes(5));
        $this->logGeocodingEvent('failed', $this->driver, $query, null, null, 'NO_RESULT', 'Geocoding returned null');

        return null;
    }

    /**
     * Geocode using fallback chain: local -> yandex -> nominatim -> google.
     *
     * @return array{lat: float, lon: float, address: string, source: string, provider: string|null}|null
     */
    public function geocodeWithFallback(string $query, string $city = 'Туркменабат'): ?array
    {
        $cacheKey = 'geocoding_fallback_'.md5($query);

        return Cache::remember($cacheKey, now()->addHour(), function () use ($query, $city) {
            foreach ($this->providerOrder as $provider) {
                $result = match ($provider) {
                    'local' => $this->geocodeViaLocal($query, $city),
                    'yandex' => $this->geocodeViaYandex(['geocode' => $this->qualifyQuery($query, $city)]),
                    'nominatim' => $this->geocodeViaNominatim(['q' => $this->qualifyQuery($query, $city)]),
                    'google' => $this->geocodeViaGoogle(['address' => $this->qualifyQuery($query, $city)]),
                    default => null,
                };

                if ($result !== null && $this->checkQuality($result)) {
                    $result['source'] = 'manual_geocoded';
                    $result['provider'] = $provider;

                    $this->logGeocodingEvent('success', $provider, $query, $result['lat'], $result['lon']);

                    return $result;
                }

                $this->logGeocodingEvent('failed', $provider, $query);
            }

            $this->logGeocodingEvent('failed_all', null, $query);

            return null;
        });
    }

    /**
     * Reverse geocode coordinates to an address using fallback chain.
     * Returns null if no provider succeeds — non-blocking for the user.
     *
     * @return array{lat: float, lon: float, address: string, source: string, provider: string|null}|null
     */
    public function reverseGeocode(float $lat, float $lon): ?array
    {
        $cacheKey = 'reverse_geocoding_'.md5("{$lat}_{$lon}");

        return Cache::remember($cacheKey, now()->addHour(), function () use ($lat, $lon) {
            foreach ($this->providerOrder as $provider) {
                $result = match ($provider) {
                    'local' => $this->reverseGeocodeViaLocal($lat, $lon),
                    'yandex' => $this->reverseGeocodeViaYandex($lat, $lon),
                    'nominatim' => $this->reverseGeocodeViaNominatim(['lat' => $lat, 'lon' => $lon]),
                    'google' => $this->reverseGeocodeViaGoogle($lat, $lon),
                    default => null,
                };

                if ($result !== null && $this->checkQuality($result)) {
                    $result['source'] = 'map_pin';
                    $result['provider'] = $provider;

                    $this->logGeocodingEvent('success', $provider, null, $lat, $lon);

                    return $result;
                }

                $this->logGeocodingEvent('failed', $provider, null, $lat, $lon);
            }

            $this->logGeocodingEvent('failed_all', null, null, $lat, $lon);

            return null;
        });
    }

    /**
     * Get address suggestions based on a partial query.
     * Tries local places first, then falls back to external providers.
     *
     * @return array<int, array{address: string, lat: float, lon: float, kind: string, source: string}>
     */
    public function suggestAddresses(string $query): array
    {
        if (empty($query)) {
            return [];
        }

        // 1. Local places first
        $localResults = $this->suggestAddressesViaLocal($query);
        if (count($localResults) >= 5) {
            $this->logGeocodingEvent('suggest_local', 'local', $query);
            return $localResults;
        }

        // 2. Try primary driver
        $results = match ($this->driver) {
            'yandex' => $this->suggestAddressesViaYandex($query),
            'nominatim' => $this->suggestAddressesViaNominatim($query),
            'google' => $this->suggestAddressesViaGoogle($query),
            default => [],
        };

        // 3. Fallback to other providers if primary returns too few results
        if (count($results) < 3) {
            foreach ($this->providerOrder as $provider) {
                if ($provider === 'local' || $provider === $this->driver) {
                    continue;
                }

                $fallbackResults = match ($provider) {
                    'yandex' => $this->suggestAddressesViaYandex($query),
                    'nominatim' => $this->suggestAddressesViaNominatim($query),
                    'google' => $this->suggestAddressesViaGoogle($query),
                    default => [],
                };

                if (! empty($fallbackResults)) {
                    $results = array_merge($results, $fallbackResults);
                    if (count($results) >= 5) {
                        break;
                    }
                }
            }
        }

        // 4. Merge with local results and deduplicate by address
        $merged = array_merge($localResults, $results);
        $unique = [];
        foreach ($merged as $item) {
            $key = md5(mb_strtolower($item['address']));
            if (! isset($unique[$key])) {
                $unique[$key] = $item;
            }
        }

        $final = array_values(array_slice($unique, 0, 8));

        if (empty($final)) {
            $this->logGeocodingEvent('suggest_empty', $this->driver, $query);
        } else {
            $this->logGeocodingEvent('suggest_results', $this->driver, $query);
        }

        return $final;
    }

    /**
     * Local address suggestions from local_places table.
     *
     * @return array<int, array{address: string, lat: float, lon: float, kind: string, source: string}>
     */
    private function suggestAddressesViaLocal(string $query): array
    {
        try {
            $normalized = mb_strtolower(trim($query));
            $words = array_filter(explode(' ', $normalized));

            $places = LocalPlace::where(function ($q) use ($normalized, $words) {
                $q->whereRaw('LOWER(name) LIKE ?', ["{$normalized}%"]);
                foreach ($words as $word) {
                    $q->orWhereRaw('LOWER(name) LIKE ?', ["%{$word}%"]);
                }
            })
                ->orderBy('is_verified', 'desc')
                ->orderBy('popularity', 'desc')
                ->limit(5)
                ->get();

            return $places->map(function ($place) {
                return [
                    'address' => $place->name,
                    'lat' => (float) $place->lat,
                    'lon' => (float) $place->lon,
                    'kind' => $place->type_label,
                    'source' => 'local',
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::warning('Local suggestions error: '.$e->getMessage());
            return [];
        }
    }

    /**
     * Log a geocoding event to the database for analytics.
     */
    private function logGeocodingEvent(
        string $status,
        ?string $provider = null,
        ?string $query = null,
        ?float $lat = null,
        ?float $lon = null,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        ?string $vendorId = null,
    ): void {
        try {
            GeocodingLog::create([
                'trace_id' => request()->header('X-Trace-Id') ?? str()->uuid(),
                'user_id' => auth()->id(),
                'vendor_id' => $vendorId,
                'provider' => $provider,
                'query' => $query,
                'lat' => $lat,
                'lon' => $lon,
                'status' => $status,
                'error_code' => $errorCode,
                'error_message' => $errorMessage ? mb_substr($errorMessage, 0, 500) : null,
            ]);
        } catch (\Exception $e) {
            // Don't let logging failures break the main flow
            Log::warning('Failed to log geocoding event: '.$e->getMessage());
        }
    }

    /**
     * Check if a geocoding result has acceptable quality.
     */
    private function checkQuality(array $result): bool
    {
        if (empty($result['lat']) || empty($result['lon'])) {
            return false;
        }

        if (empty($result['address'])) {
            return false;
        }

        return true;
    }

    /**
     * Qualify a query with city name if not already present.
     */
    private function qualifyQuery(string $query, string $city): string
    {
        return str_contains(mb_strtolower($query), mb_strtolower($city)) ? $query : "{$city}, {$query}";
    }

    /**
     * Local database reverse geocode — find nearest known place within ~200m.
     */
    private function reverseGeocodeViaLocal(float $lat, float $lon): ?array
    {
        try {
            $place = LocalPlace::query()
                ->selectRaw("*, (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lon) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) as distance", [$lat, $lon, $lat])
                ->having('distance', '<', 0.2)
                ->orderBy('popularity', 'desc')
                ->orderBy('distance')
                ->first();

            if ($place) {
                return [
                    'lat' => (float) $place->lat,
                    'lon' => (float) $place->lon,
                    'address' => $place->name,
                    'source' => 'map_pin',
                    'provider' => 'local',
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Local reverse geocode error: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Local database geocode — match by name or aliases.
     */
    private function geocodeViaLocal(string $query, string $city): ?array
    {
        try {
            $normalized = mb_strtolower(trim($query));
            $words = array_filter(explode(' ', $normalized));

            $place = LocalPlace::where('city', $city)
                ->where(function ($q) use ($normalized, $words) {
                    $q->whereRaw('LOWER(name) = ?', [$normalized]);
                    foreach ($words as $word) {
                        $q->orWhereRaw('LOWER(name) LIKE ?', ["%{$word}%"]);
                    }
                })
                ->orderBy('is_verified', 'desc')
                ->orderBy('popularity', 'desc')
                ->first();

            if ($place) {
                return [
                    'lat' => (float) $place->lat,
                    'lon' => (float) $place->lon,
                    'address' => $place->name,
                    'source' => 'manual_geocoded',
                    'provider' => 'local',
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Local geocode error: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Google reverse geocoding.
     */
    private function reverseGeocodeViaGoogle(float $lat, float $lon): ?array
    {
        return $this->geocodeViaGoogle(['latlng' => "{$lat},{$lon}"]);
    }

    /**
     * Yandex reverse geocoding.
     */
    private function reverseGeocodeViaYandex(float $lat, float $lon): ?array
    {
        return $this->geocodeViaYandex(['geocode' => "{$lon},{$lat}"]);
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
                    'source' => 'manual_geocoded',
                    'provider' => 'google',
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
                        'source' => 'google',
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
                'll' => '63.5593,39.0886', // Центр Туркменабата
                'spn' => '0.2,0.2',      // Область поиска
                'rspn' => 0,              // Искать везде, но отдавать приоритет этой области
            ], $params));

            if ($response->successful()) {
                $feature = $response->json('response.GeoObjectCollection.featureMember.0.GeoObject');

                if ($feature) {
                    $pos = explode(' ', $feature['Point']['pos']); // Yandex returns "lon lat"

                    return [
                        'lon' => (float) ($pos[0] ?? 0),
                        'lat' => (float) ($pos[1] ?? 0),
                        'address' => (string) $feature['metaDataProperty']['GeocoderMetaData']['text'],
                        'source' => 'manual_geocoded',
                        'provider' => 'yandex',
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
                'geocode' => str_contains($query, 'Туркменабат') ? $query : 'Туркменабат, ' . $query,
                'format' => 'json',
                'lang' => 'ru_RU',
                'll' => '63.5593,39.0886',
                'spn' => '0.15,0.15',
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
                        'source' => 'yandex',
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
                        'source' => 'manual_geocoded',
                        'provider' => 'nominatim',
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
                        'source' => 'map_pin',
                        'provider' => 'nominatim',
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
                        'source' => 'nominatim',
                    ];
                }, $results);
            }
        } catch (\Exception $e) {
            Log::error('Nominatim Address suggestion exception: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Helper to check if PostGIS is installed/enabled.
     */
    private function checkPostGis(): bool
    {
        try {
            $result = DB::select("SELECT proname FROM pg_proc WHERE proname = 'addgeometrycolumn' LIMIT 1");
            return ! empty($result);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Check delivery zone with detailed diagnostics.
     */
    public function checkDeliveryZone(float $lat, float $lon, string $vendorId): DeliveryZoneCheckResult
    {
        $restaurant = Restaurant::find($vendorId);

        if (!$restaurant) {
            return new DeliveryZoneCheckResult(
                status: 'zone_missing',
                allowed: false,
                message: 'Ресторан не найден.',
                debugContext: ['vendor_id' => $vendorId, 'lat' => $lat, 'lon' => $lon]
            );
        }

        $zones = $restaurant->delivery_zones;

        if (empty($zones)) {
            return new DeliveryZoneCheckResult(
                status: 'zone_missing',
                allowed: false,
                message: 'Зона доставки ресторана не настроена.',
                debugContext: ['vendor_id' => $vendorId, 'lat' => $lat, 'lon' => $lon]
            );
        }

        if (DB::getDriverName() === 'sqlite') {
            return new DeliveryZoneCheckResult(
                status: 'inside',
                allowed: true,
                message: 'Доставка разрешена (SQLite fallback).',
                debugContext: ['vendor_id' => $vendorId, 'lat' => $lat, 'lon' => $lon]
            );
        }

        try {
            if (!$this->checkPostGis()) {
                return new DeliveryZoneCheckResult(
                    status: 'postgis_error',
                    allowed: true, // Bypass check if PostGIS is unavailable
                    message: 'Доставка разрешена (PostGIS недоступен).',
                    debugContext: ['vendor_id' => $vendorId, 'lat' => $lat, 'lon' => $lon]
                );
            }

            // Perform ST_Covers check. ST_Covers is more robust than ST_Contains (includes borders)
            $result = DB::selectOne('
                SELECT 
                    ST_Covers(delivery_zones, ST_SetSRID(ST_MakePoint(?, ?), 4326)) as is_inside,
                    ST_IsValid(delivery_zones) as is_valid
                FROM restaurants 
                WHERE id = ?
            ', [$lon, $lat, $vendorId]);

            if ($result === null) {
                return new DeliveryZoneCheckResult(
                    status: 'zone_missing',
                    allowed: false,
                    message: 'Ресторан не найден при проверке геометрии.',
                    debugContext: ['vendor_id' => $vendorId, 'lat' => $lat, 'lon' => $lon]
                );
            }

            if (!$result->is_valid) {
                return new DeliveryZoneCheckResult(
                    status: 'invalid_geometry',
                    allowed: false,
                    message: 'Некорректная геометрия зоны доставки в базе данных.',
                    debugContext: ['vendor_id' => $vendorId, 'lat' => $lat, 'lon' => $lon, 'is_valid' => false]
                );
            }

            $isInside = (bool) $result->is_inside;

            Log::info("[GeoService] Diagnostic delivery zone check", [
                'vendor_id' => $vendorId,
                'lat' => $lat,
                'lon' => $lon,
                'is_inside' => $isInside,
            ]);

            $this->logGeocodingEvent(
                $isInside ? 'in_zone' : 'outside_zone',
                'postgis',
                null, $lat, $lon,
                null, null,
                $vendorId
            );

            if ($isInside) {
                return new DeliveryZoneCheckResult(
                    status: 'inside',
                    allowed: true,
                    message: 'Адрес находится в зоне доставки.',
                    debugContext: ['vendor_id' => $vendorId, 'lat' => $lat, 'lon' => $lon]
                );
            }

            return new DeliveryZoneCheckResult(
                status: 'outside',
                allowed: false,
                message: 'Адрес находится за пределами зоны доставки.',
                debugContext: ['vendor_id' => $vendorId, 'lat' => $lat, 'lon' => $lon]
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error("[GeoService] Delivery zone check failed: " . $e->getMessage());

            if (str_contains(strtolower($e->getMessage()), 'st_makepoint')) {
                return new DeliveryZoneCheckResult(
                    status: 'postgis_error',
                    allowed: true, // Bypass check
                    message: 'Доставка разрешена (ошибка PostGIS функций).',
                    debugContext: ['vendor_id' => $vendorId, 'lat' => $lat, 'lon' => $lon, 'error' => $e->getMessage()]
                );
            }

            return new DeliveryZoneCheckResult(
                status: 'postgis_error',
                allowed: false,
                message: 'Произошла ошибка при проверке адреса на сервере.',
                debugContext: ['vendor_id' => $vendorId, 'lat' => $lat, 'lon' => $lon, 'error' => $e->getMessage()]
            );
        }
    }

    /**
     * Check if a point is within the delivery zone of a specific vendor.
     */
    public function isPointInDeliveryZone(float $lat, float $lon, string $vendorId): bool
    {
        return $this->checkDeliveryZone($lat, $lon, $vendorId)->isAllowed();
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
