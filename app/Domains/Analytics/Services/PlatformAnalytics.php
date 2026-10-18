<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Data\AnalyticsPeriod;
use App\Domains\Logistics\Models\Courier;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderRejection;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\OrderRejectReason;
use App\Enums\UserRole;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Admin figures across all restaurants. Same money rules as RestaurantAnalytics:
 * turnover (GMV) = what customers paid for finished orders; delivery fees go to
 * couriers; the platform earns the commission.
 */
class PlatformAnalytics
{
    /** @var array<string, Collection<int, Order>> */
    private array $cache = [];

    /**
     * @return array{
     *     orders: int, finished: int, cancelled: int, cancel_rate: float, gmv: float, food: float,
     *     delivery_fees: float, commission: float, average_check: float, active_restaurants: int,
     *     active_couriers: int, new_customers: int, rejections: int
     * }
     */
    public function summary(AnalyticsPeriod $period): array
    {
        $orders = $this->orders($period);
        $finished = $orders->filter(fn (Order $o) => $o->isFinished());
        $cancelled = $orders->filter(fn (Order $o) => $this->isCancelled($o))->count();

        $gmv = round($finished->sum(fn (Order $o) => (float) $o->total), 2);
        $food = round($finished->sum(fn (Order $o) => $o->foodTotal()), 2);

        return [
            'orders' => $orders->count(),
            'finished' => $finished->count(),
            'cancelled' => $cancelled,
            'cancel_rate' => $orders->isEmpty() ? 0.0 : round($cancelled / $orders->count() * 100, 1),
            'gmv' => $gmv,
            'food' => $food,
            'delivery_fees' => round($gmv - $food, 2),
            'commission' => round($finished->sum(fn (Order $o) => (float) $o->commission_amount), 2),
            'average_check' => $finished->isEmpty() ? 0.0 : round($gmv / $finished->count(), 2),
            'active_restaurants' => $orders->pluck('vendor_id')->unique()->count(),
            'active_couriers' => $finished->whereNotNull('courier_id')->pluck('courier_id')->unique()->count(),
            'new_customers' => User::where('role', UserRole::CLIENT)->whereBetween('created_at', $period->utcBounds())->count(),
            'rejections' => OrderRejection::whereBetween('created_at', $period->utcBounds())->count(),
        ];
    }

    /** @return array{restaurants_open: int, restaurants_total: int, couriers_online: int, couriers_total: int} */
    public function rightNow(): array
    {
        $restaurants = Restaurant::with('schedules')->where('is_active', true)->get();

        return [
            'restaurants_open' => $restaurants->filter(fn (Restaurant $r) => $r->is_open_now)->count(),
            'restaurants_total' => $restaurants->count(),
            'couriers_online' => Courier::whereIn('status', [Courier::STATUS_AVAILABLE, Courier::STATUS_BUSY])->count(),
            'couriers_total' => Courier::count(),
        ];
    }

    /** @return list<array{date: string, orders: int, gmv: float}> finished orders per local day */
    public function daily(AnalyticsPeriod $period): array
    {
        $byDay = $this->orders($period)
            ->filter(fn (Order $o) => $o->isFinished())
            ->groupBy(fn (Order $o) => CarbonImmutable::parse($o->created_at)->setTimezone($period->timezone)->toDateString());

        $days = [];
        for ($day = $period->from->startOfDay(); $day->lessThanOrEqualTo($period->to); $day = $day->addDay()) {
            $orders = $byDay->get($day->toDateString(), collect());
            $days[] = [
                'date' => $day->toDateString(),
                'orders' => $orders->count(),
                'gmv' => round($orders->sum(fn (Order $o) => (float) $o->total), 2),
            ];
        }

        return $days;
    }

    /**
     * Restaurants with orders in the period, biggest turnover first.
     *
     * @return list<array{name: string, orders: int, finished: int, cancel_rate: float, gmv: float, commission: float}>
     */
    public function restaurants(AnalyticsPeriod $period): array
    {
        $names = Restaurant::pluck('name', 'id');

        return $this->orders($period)
            ->groupBy('vendor_id')
            ->map(function (Collection $orders, string $vendorId) use ($names) {
                $finished = $orders->filter(fn (Order $o) => $o->isFinished());
                $cancelled = $orders->filter(fn (Order $o) => $this->isCancelled($o))->count();

                return [
                    'name' => $names[$vendorId] ?? '—',
                    'orders' => $orders->count(),
                    'finished' => $finished->count(),
                    'cancel_rate' => round($cancelled / $orders->count() * 100, 1),
                    'gmv' => round($finished->sum(fn (Order $o) => (float) $o->total), 2),
                    'commission' => round($finished->sum(fn (Order $o) => (float) $o->commission_amount), 2),
                ];
            })
            ->sortByDesc('gmv')
            ->values()
            ->all();
    }

    /**
     * Couriers by deliveries in the period; they earn the whole delivery fee.
     *
     * @return list<array{name: string, deliveries: int, earned: float}>
     */
    public function couriers(AnalyticsPeriod $period, int $limit = 10): array
    {
        $names = Courier::with('user:id,name')->get()->mapWithKeys(fn (Courier $c) => [$c->id => $c->user?->name ?? '—']);

        return $this->orders($period)
            ->filter(fn (Order $o) => $o->isFinished() && $o->courier_id !== null)
            ->groupBy('courier_id')
            ->map(fn (Collection $orders, string $courierId) => [
                'name' => $names[$courierId] ?? '—',
                'deliveries' => $orders->count(),
                'earned' => round($orders->sum(fn (Order $o) => (float) $o->delivery_fee), 2),
            ])
            ->sortByDesc('deliveries')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Why customers could not place an order, most frequent first.
     *
     * @return list<array{reason: string, label: string, count: int, share: float}>
     */
    public function rejectionReasons(AnalyticsPeriod $period): array
    {
        $counts = OrderRejection::query()
            ->whereBetween('created_at', $period->utcBounds())
            ->selectRaw('reason, count(*) as total')
            ->groupBy('reason')
            ->pluck('total', 'reason');

        $all = max(1, $counts->sum());

        return $counts
            ->map(fn ($count, string $reason) => [
                'reason' => $reason,
                'label' => OrderRejectReason::tryFrom($reason)?->label() ?? $reason,
                'count' => (int) $count,
                'share' => round($count / $all * 100, 1),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    private function isCancelled(Order $order): bool
    {
        return in_array($order->status, [Order::STATUS_CANCELLED, 'canceled'], true);
    }

    /** @return Collection<int, Order> */
    private function orders(AnalyticsPeriod $period): Collection
    {
        $key = $period->from->toIso8601String().'|'.$period->to->toIso8601String();

        return $this->cache[$key] ??= Order::query()
            ->whereBetween('created_at', $period->utcBounds())
            ->get(['id', 'vendor_id', 'courier_id', 'status', 'delivery_type', 'total', 'delivery_fee', 'commission_amount', 'created_at']);
    }
}
