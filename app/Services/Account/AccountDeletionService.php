<?php

declare(strict_types=1);

namespace App\Services\Account;

use App\Domains\Order\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Customer-initiated account deletion.
 *
 * The user row is not removed: orders cascade on it, and restaurants need their
 * order history, payments and courier earnings. Instead every piece of personal
 * data is wiped, so the account can't be signed into or traced back to a person.
 * Reviews stay (restaurant ratings must not jump) under an anonymous name.
 */
class AccountDeletionService
{
    public const ANONYMOUS_NAME = 'Удалённый пользователь';

    /** Orders in these statuses are finished; anything else blocks deletion. */
    public const FINAL_ORDER_STATUSES = [Order::STATUS_DELIVERED, 'completed', Order::STATUS_CANCELLED, 'canceled'];

    public function hasActiveOrders(User $user): bool
    {
        return Order::where('user_id', $user->id)
            ->whereNotIn('status', self::FINAL_ORDER_STATUSES)
            ->exists();
    }

    /**
     * @throws RuntimeException 403 for staff accounts, 409 while an order is in progress
     */
    public function delete(User $user): void
    {
        if (! $user->isClient()) {
            throw new RuntimeException('Staff accounts are deleted by an administrator', 403);
        }

        DB::transaction(function () use ($user) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($this->hasActiveOrders($user)) {
                throw new RuntimeException('Account has an order in progress', 409);
            }

            DB::table('user_addresses')->where('user_id', $user->id)->delete();
            DB::table('user_payment_methods')->where('user_id', $user->id)->delete();
            DB::table('support_tickets')->where('user_id', $user->id)->delete();
            DB::table('push_subscriptions')->where('user_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            if ($user->phone) {
                DB::table('sms_logs')->where('phone', $user->phone)->delete();
            }
            DB::table('geocoding_logs')->where('user_id', $user->id)->update(['user_id' => null, 'query' => null]);

            // Contacts, address and comment the customer typed into past orders
            Order::where('user_id', $user->id)->get()->each(function (Order $order) {
                $order->forceFill([
                    'address' => array_map(fn () => null, (array) $order->address),
                    'comment' => null,
                ])->saveQuietly();
            });

            $user->forceFill([
                'name' => self::ANONYMOUS_NAME,
                'email' => 'deleted-'.$user->id.'@deleted.invalid',
                'phone' => null,
                'password' => null,
                'google_id' => null,
                'avatar' => null,
                'remember_token' => Str::random(60),
                'deleted_at' => now(),
            ])->saveQuietly();
        });
    }
}
