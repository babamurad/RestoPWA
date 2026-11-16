<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * What a customer is told about, and how.
 *
 * - Order notifications (accepted, cooking, on the way, delivered, cancelled, the receipt)
 *   are part of the service and cannot be switched off: the customer is waiting for the food.
 * - Marketing messages (offers, promo codes) are opt-in per channel and off until the customer
 *   agrees. There is no marketing yet; whatever sends it later must ask allowsMarketing() first.
 */
final class NotificationPreferences
{
    public const MARKETING_CHANNELS = ['push', 'email', 'sms'];

    /**
     * @return array{
     *     order_status: array{push: true, sms: true, email: true, locked: true},
     *     marketing: array{push: bool, email: bool, sms: bool}
     * }
     */
    public static function for(User $user): array
    {
        $saved = is_array($user->notification_preferences) ? ($user->notification_preferences['marketing'] ?? []) : [];

        $marketing = [];
        foreach (self::MARKETING_CHANNELS as $channel) {
            $marketing[$channel] = (bool) ($saved[$channel] ?? false);
        }

        return [
            'order_status' => ['push' => true, 'sms' => true, 'email' => true, 'locked' => true],
            'marketing' => $marketing,
        ];
    }

    /** May this customer be sent marketing through the channel? */
    public static function allowsMarketing(User $user, string $channel): bool
    {
        return in_array($channel, self::MARKETING_CHANNELS, true)
            && self::for($user)['marketing'][$channel];
    }

    /**
     * Save the marketing choices; channels that are not named keep their value.
     *
     * @param  array<string, mixed>  $marketing
     */
    public static function updateMarketing(User $user, array $marketing): void
    {
        $current = self::for($user)['marketing'];

        foreach (self::MARKETING_CHANNELS as $channel) {
            if (array_key_exists($channel, $marketing)) {
                $current[$channel] = (bool) $marketing[$channel];
            }
        }

        $user->forceFill(['notification_preferences' => ['marketing' => $current]])->save();
    }
}
