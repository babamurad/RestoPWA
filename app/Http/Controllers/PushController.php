<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Push\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|url',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $endpoint = $request->input('endpoint');
        $p256dh = $request->input('keys.p256dh');
        $auth = $request->input('keys.auth');

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint' => $endpoint],
            [
                'user_id' => $user->id,
                'p256dh' => $p256dh,
                'auth' => $auth,
            ]
        );

        return response()->json(['id' => $subscription->id]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|url',
        ]);

        PushSubscription::where('endpoint', $request->input('endpoint'))
            ->delete();

        return response()->json(['success' => true]);
    }
}
