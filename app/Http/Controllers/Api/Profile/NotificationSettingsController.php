<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Support\NotificationPreferences;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    use ApiResponses;

    /** GET /api/v1/profile/notifications */
    public function show(Request $request): JsonResponse
    {
        return $this->success(NotificationPreferences::for($request->user()));
    }

    /** PUT /api/v1/profile/notifications — { marketing: { push?, email?, sms? } } */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'marketing' => 'required|array',
            'marketing.push' => 'sometimes|boolean',
            'marketing.email' => 'sometimes|boolean',
            'marketing.sms' => 'sometimes|boolean',
            'order_status' => 'sometimes|array',
        ]);

        // Order notifications cannot be switched off: say so instead of silently ignoring it
        foreach ((array) $request->input('order_status', []) as $channel => $enabled) {
            if (is_scalar($enabled) && ! filter_var($enabled, FILTER_VALIDATE_BOOLEAN) && $channel !== 'locked') {
                return $this->error(__('api.notifications.status_locked'), 422);
            }
        }

        // Only the known channels; the validated sub-keys are all that passed
        NotificationPreferences::updateMarketing($request->user(), $validated['marketing'] ?? []);

        return $this->success(NotificationPreferences::for($request->user()->fresh()));
    }
}
