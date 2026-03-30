<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Push\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotificationService
{
    private WebPush $webPush;

    public function __construct()
    {
        $auth = [
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => config('services.push.public_key'),
                'privateKey' => config('services.push.private_key'),
            ],
        ];

        $this->webPush = new WebPush($auth);
    }

    public function sendToUser(int|string $userId, string $title, string $body, array $data = []): int
    {
        $subscriptions = PushSubscription::forUser($userId)
            ->active()
            ->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $sentCount = 0;

        foreach ($subscriptions as $subscription) {
            $this->sendNotification($subscription, $title, $body, $data);
            $sentCount++;
        }

        $this->webPush->flush();

        return $sentCount;
    }

    public function sendNotification(PushSubscription $subscription, string $title, string $body, array $data = []): bool
    {
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'icon' => '/icon-192x192.png',
            'badge' => '/icon-192x192.png',
        ]);

        $wsSubscription = Subscription::create([
            'endpoint' => $subscription->endpoint,
            'publicKey' => $subscription->p256dh,
            'authToken' => $subscription->auth,
        ]);

        $this->webPush->queueNotification($wsSubscription, $payload);

        return true;
    }
}
