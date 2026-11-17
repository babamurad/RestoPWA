<?php

declare(strict_types=1);

namespace App\Domains\Promo\Services;

use App\Casts\MoneyCast;
use App\Domains\Order\Models\Order;
use App\Domains\Promo\Exceptions\PromoUnavailable;
use App\Domains\Promo\Models\PromoCode;
use App\Domains\Promo\Models\PromoCodeUsage;
use App\Domains\Promo\PromoRejectReason;
use Illuminate\Support\Facades\DB;

/**
 * Promo discounts. The discount is taken from the food total only (never from the delivery fee)
 * and is always calculated here, on the server; amounts in and out are minor units (tyiyn).
 *
 * The discount lowers what the restaurant receives for the food, and the platform commission
 * is calculated from the discounted amount (Order::foodTotal()).
 */
class PromoCodeService
{
    /**
     * Check a code for a cart without redeeming it.
     *
     * @return array{promo: PromoCode|null, discount: int, reason: PromoRejectReason|null}
     */
    public function evaluate(string $code, ?string $userId, string $restaurantId, int $itemsTotalMinor, ?PromoCode $locked = null): array
    {
        $promo = $locked ?? PromoCode::where('code', PromoCode::normalize($code))->first();

        $reject = fn (PromoRejectReason $reason) => ['promo' => $promo, 'discount' => 0, 'reason' => $reason];

        if (! $promo) {
            return $reject(PromoRejectReason::NOT_FOUND);
        }

        if (! $promo->is_active) {
            return $reject(PromoRejectReason::INACTIVE);
        }

        if ($promo->starts_at && $promo->starts_at->isFuture()) {
            return $reject(PromoRejectReason::NOT_STARTED);
        }

        if ($promo->expires_at && $promo->expires_at->isPast()) {
            return $reject(PromoRejectReason::EXPIRED);
        }

        if ($promo->restaurant_id !== null && $promo->restaurant_id !== $restaurantId) {
            return $reject(PromoRejectReason::WRONG_RESTAURANT);
        }

        if ($itemsTotalMinor < MoneyCast::toMinor($promo->min_order)) {
            return $reject(PromoRejectReason::MIN_ORDER);
        }

        if ($promo->usage_limit !== null && $promo->used_count >= $promo->usage_limit) {
            return $reject(PromoRejectReason::USAGE_LIMIT);
        }

        if ($userId !== null) {
            $used = PromoCodeUsage::where('promo_code_id', $promo->id)->where('user_id', $userId)->count();

            if ($used >= $promo->per_user_limit) {
                return $reject(PromoRejectReason::USER_LIMIT);
            }
        }

        return ['promo' => $promo, 'discount' => $this->discountFor($promo, $itemsTotalMinor), 'reason' => null];
    }

    /** Discount in minor units, never more than the food total */
    public function discountFor(PromoCode $promo, int $itemsTotalMinor): int
    {
        if ($promo->percent !== null) {
            $discount = (int) round($itemsTotalMinor * $promo->percent / 100);

            if ($promo->max_discount !== null && MoneyCast::toMinor($promo->max_discount) > 0) {
                $discount = min($discount, MoneyCast::toMinor($promo->max_discount));
            }
        } else {
            $discount = MoneyCast::toMinor($promo->amount);
        }

        return max(0, min($discount, $itemsTotalMinor));
    }

    /**
     * Take one use of the code for an order. Runs inside the order transaction: the code row
     * is locked, so two simultaneous orders cannot both take the last use.
     *
     * @param  int  $expectedDiscount  what the customer was shown and the order total is built on
     *
     * @throws PromoUnavailable
     */
    public function redeem(Order $order, string $code, int $itemsTotalMinor, int $expectedDiscount): PromoCode
    {
        $locked = PromoCode::where('code', PromoCode::normalize($code))->lockForUpdate()->first();

        $result = $this->evaluate($code, $order->user_id, $order->vendor_id, $itemsTotalMinor, $locked);

        if ($result['reason'] !== null) {
            throw new PromoUnavailable($result['reason']);
        }

        $promo = $result['promo'];

        if ($result['discount'] !== $expectedDiscount) {
            throw new PromoUnavailable(PromoRejectReason::CHANGED);
        }

        PromoCodeUsage::create([
            'promo_code_id' => $promo->id,
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'discount_amount' => MoneyCast::fromMinor($result['discount']),
        ]);
        $promo->increment('used_count');

        return $promo;
    }

    /** A cancelled order gives its use of the code back */
    public function release(Order $order): void
    {
        $usage = PromoCodeUsage::where('order_id', $order->id)->first();

        if (! $usage) {
            return;
        }

        DB::transaction(function () use ($usage) {
            PromoCode::whereKey($usage->promo_code_id)->where('used_count', '>', 0)->decrement('used_count');
            $usage->delete();
        });
    }
}
