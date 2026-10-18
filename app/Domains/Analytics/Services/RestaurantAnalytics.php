<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Data\AnalyticsPeriod;
use App\Domains\Order\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Sales figures for one restaurant over a period.
 *
 * Money rules (same as the rest of the app):
 * - the delivery fee goes to the courier, so the restaurant's revenue is
 *   the order total minus the delivery fee;
 * - payout = revenue − platform commission.
 * Only finished orders count as sales; cancellations are reported separately.
 */
class RestaurantAnalytics
{
    public const COMPLETED_STATUSES = [Order::STATUS_DELIVERED, 'completed'];

    public const CANCELLED_STATUSES = [Order::STATUS_CANCELLED, 'canceled'];

    /** Statuses missing from Order::STATUSES (pickup handed over, legacy spelling) */
    private const STATUS_LABELS = ['completed' => 'Выдан', 'canceled' => 'Отменён'];

    /** @var array<string, array<string, mixed>> */
    private array $cache = [];

    /**
     * @return array{
     *     orders: int, completed: int, cancelled: int, in_progress: int, cancel_rate: float,
     *     gross: float, revenue: float, delivery_fees: float, commission: float, payout: float,
     *     average_check: float, delivery_orders: int, pickup_orders: int
     * }
     */
    public function summary(string $restaurantId, AnalyticsPeriod $period): array
    {
        $orders = $this->orders($restaurantId, $period);
        $completed = $orders->filter(fn (Order $o) => $this->isCompleted($o));
        $cancelled = $orders->filter(fn (Order $o) => in_array($o->status, self::CANCELLED_STATUSES, true))->count();

        $gross = round($completed->sum(fn (Order $o) => $o->total), 2);
        $deliveryFees = round($completed->sum(fn (Order $o) => $o->delivery_fee), 2);
        $revenue = round($gross - $deliveryFees, 2);
        $commission = round($completed->sum(fn (Order $o) => $o->commission_amount), 2);

        return [
            'orders' => $orders->count(),
            'completed' => $completed->count(),
            'cancelled' => $cancelled,
            'in_progress' => $orders->count() - $completed->count() - $cancelled,
            'cancel_rate' => $orders->isEmpty() ? 0.0 : round($cancelled / $orders->count() * 100, 1),
            'gross' => $gross,
            'revenue' => $revenue,
            'delivery_fees' => $deliveryFees,
            'commission' => $commission,
            'payout' => round($revenue - $commission, 2),
            'average_check' => $completed->isEmpty() ? 0.0 : round($revenue / $completed->count(), 2),
            'delivery_orders' => $completed->filter(fn (Order $o) => ! $o->isPickup())->count(),
            'pickup_orders' => $completed->filter(fn (Order $o) => $o->isPickup())->count(),
        ];
    }

    /**
     * Completed orders and revenue for every local day of the period (days without orders included).
     *
     * @return list<array{date: string, orders: int, revenue: float}>
     */
    public function daily(string $restaurantId, AnalyticsPeriod $period): array
    {
        $byDay = $this->orders($restaurantId, $period)
            ->filter(fn (Order $o) => $this->isCompleted($o))
            ->groupBy(fn (Order $o) => $this->localTime($o, $period)->toDateString());

        $days = [];
        for ($day = $period->from->startOfDay(); $day->lessThanOrEqualTo($period->to); $day = $day->addDay()) {
            $orders = $byDay->get($day->toDateString(), collect());
            $days[] = [
                'date' => $day->toDateString(),
                'orders' => $orders->count(),
                'revenue' => round($orders->sum(fn (Order $o) => $o->total - $o->delivery_fee), 2),
            ];
        }

        return $days;
    }

    /**
     * Orders placed per local hour (0–23), cancelled ones excluded: when the kitchen is busiest.
     *
     * @return array<int, int>
     */
    public function hourly(string $restaurantId, AnalyticsPeriod $period): array
    {
        $hours = array_fill(0, 24, 0);

        $this->orders($restaurantId, $period)
            ->reject(fn (Order $o) => in_array($o->status, self::CANCELLED_STATUSES, true))
            ->each(function (Order $o) use (&$hours, $period) {
                $hours[(int) $this->localTime($o, $period)->format('G')]++;
            });

        return $hours;
    }

    /**
     * Best-selling dishes in completed orders. Item prices are stored in minor units.
     *
     * @return list<array{product_id: ?string, name: string, quantity: int, revenue: float}>
     */
    public function topProducts(string $restaurantId, AnalyticsPeriod $period, int $limit = 10): array
    {
        $products = [];

        $this->orders($restaurantId, $period)
            ->filter(fn (Order $o) => $this->isCompleted($o))
            ->each(function (Order $o) use (&$products) {
                foreach ((array) $o->items as $item) {
                    $name = (string) ($item['product_name'] ?? $item['name'] ?? '—');
                    $key = (string) ($item['product_id'] ?? $name);
                    $quantity = (int) ($item['quantity'] ?? 1);

                    $products[$key] ??= ['product_id' => $item['product_id'] ?? null, 'name' => $name, 'quantity' => 0, 'revenue' => 0];
                    $products[$key]['quantity'] += $quantity;
                    $products[$key]['revenue'] += (int) ($item['total_price'] ?? ((int) ($item['unit_price'] ?? 0) * $quantity));
                }
            });

        return collect($products)
            ->sortByDesc('quantity')
            ->take($limit)
            ->map(fn (array $p) => ['revenue' => round($p['revenue'] / 100, 2)] + $p)
            ->values()
            ->all();
    }

    /**
     * One row per order of the period for the CSV export (local time, TMT).
     *
     * @return list<array<string, string|float>>
     */
    public function exportRows(string $restaurantId, AnalyticsPeriod $period): array
    {
        return $this->orders($restaurantId, $period)
            ->sortBy('created_at')
            ->map(function (Order $o) use ($period) {
                $completed = $this->isCompleted($o);
                $revenue = $completed ? round($o->total - $o->delivery_fee, 2) : 0.0;

                return [
                    'Дата' => $this->localTime($o, $period)->format('d.m.Y H:i'),
                    'Заказ' => '#'.$o->order_number,
                    'Статус' => self::STATUS_LABELS[$o->status] ?? Order::STATUSES[$o->status]['label'] ?? $o->status,
                    'Получение' => $o->isPickup() ? 'Самовывоз' : 'Доставка',
                    'Сумма заказа' => $o->total,
                    'Доставка' => $o->delivery_fee,
                    'Выручка' => $revenue,
                    'Комиссия' => $completed ? $o->commission_amount : 0.0,
                    'К выплате' => $completed ? round($revenue - $o->commission_amount, 2) : 0.0,
                ];
            })
            ->values()
            ->all();
    }

    /** Percentage change, null when there is nothing to compare with */
    public static function change(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    private function isCompleted(Order $order): bool
    {
        return in_array($order->status, self::COMPLETED_STATUSES, true);
    }

    private function localTime(Order $order, AnalyticsPeriod $period): CarbonImmutable
    {
        return CarbonImmutable::parse($order->created_at)->setTimezone($period->timezone);
    }

    /** @return Collection<int, Order> */
    private function orders(string $restaurantId, AnalyticsPeriod $period): Collection
    {
        $key = $restaurantId.'|'.$period->from->toIso8601String().'|'.$period->to->toIso8601String();

        return $this->cache[$key] ??= Order::query()
            ->where('vendor_id', $restaurantId)
            ->whereBetween('created_at', $period->utcBounds())
            ->get(['id', 'number', 'status', 'delivery_type', 'total', 'delivery_fee', 'commission_amount', 'items', 'created_at']);
    }
}
