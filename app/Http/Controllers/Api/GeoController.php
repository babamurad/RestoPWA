<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Geo\Services\GeoService;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GeoController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly GeoService $geoService) {}

    /**
     * POST /api/v1/geo/zone-check
     * Check if a coordinate is within the vendor's delivery zone.
     */
    public function zoneCheck(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id' => 'required|uuid|exists:restaurants,id',
            'lat'       => 'required|numeric|between:-90,90',
            'lon'       => 'required|numeric|between:-180,180',
        ]);

        try {
            $result = $this->geoService->checkDeliveryZone(
                (float) $validated['lat'],
                (float) $validated['lon'],
                $validated['vendor_id'],
            );

            return $this->success([
                'in_zone' => $result->isAllowed(),
                'status'  => $result->status,
                'message' => $result->messageForUser(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[GeoController] zone-check error: ' . $e->getMessage());

            // Graceful fallback: allow checkout, don't block user
            return $this->success([
                'in_zone' => true,
                'status'  => 'error',
                'message' => 'Не удалось проверить зону доставки.',
            ]);
        }
    }

    /**
     * POST /api/v1/geo/reverse
     * Reverse geocode coordinates to a human-readable address with confidence score.
     */
    public function reverse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
        ]);

        try {
            $result = $this->geoService->reverseGeocode(
                (float) $validated['lat'],
                (float) $validated['lon'],
            );

            if (! $result) {
                return $this->success([
                    'address'    => null,
                    'confidence' => 'low',
                    'provider'   => null,
                ]);
            }

            // Simple confidence heuristic:
            // - local provider = high (verified local place)
            // - address has street+house number pattern = medium
            // - anything else = low
            $confidence = match (true) {
                ($result['provider'] ?? '') === 'local'           => 'high',
                preg_match('/\d/', $result['address'] ?? '') === 1 => 'medium',
                default                                            => 'low',
            };

            return $this->success([
                'address'    => $result['address'],
                'confidence' => $confidence,
                'provider'   => $result['provider'] ?? null,
                'lat'        => $result['lat'],
                'lon'        => $result['lon'],
            ]);
        } catch (\Throwable $e) {
            Log::error('[GeoController] reverse error: ' . $e->getMessage());

            return $this->success([
                'address'    => null,
                'confidence' => 'low',
                'provider'   => null,
            ]);
        }
    }

    /**
     * POST /api/v1/telemetry
     * Collect frontend analytics events. Silently accepts any payload.
     */
    public function telemetry(Request $request): JsonResponse
    {
        try {
            Log::channel('stack')->info('[Telemetry] ' . ($request->input('event', 'unknown')), [
                'session_id'  => $request->input('session_id'),
                'event'       => $request->input('event'),
                'payload'     => $request->except(['session_id', 'event']),
                'ip'          => $request->ip(),
                'user_agent'  => $request->userAgent(),
            ]);
        } catch (\Throwable) {
            // silently ignore
        }

        return response()->json(['ok' => true]);
    }
}
