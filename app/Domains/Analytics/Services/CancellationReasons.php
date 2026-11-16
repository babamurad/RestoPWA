<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Services;

use App\Domains\Order\Models\Order;
use Illuminate\Support\Collection;

/** Why orders were cancelled and who cancelled them, for the platform and the restaurant analytics. */
final class CancellationReasons
{
    /** Orders cancelled before the reasons were recorded */
    public const UNKNOWN = 'unknown';

    private const WHO = [
        Order::CANCELLED_BY_CUSTOMER => 'Клиенты',
        Order::CANCELLED_BY_RESTAURANT => 'Рестораны',
        Order::CANCELLED_BY_ADMIN => 'Администратор',
        Order::CANCELLED_BY_SYSTEM => 'Система',
        self::UNKNOWN => 'Не указано',
    ];

    /**
     * @param  Collection<int, Order>  $cancelled  cancelled orders (cancel_reason and cancelled_by loaded)
     * @return array{
     *     reasons: list<array{reason: string, label: string, count: int, share: float}>,
     *     by: list<array{who: string, label: string, count: int}>
     * }
     */
    public static function summarize(Collection $cancelled): array
    {
        $total = max(1, $cancelled->count());

        $reasons = $cancelled
            ->groupBy(fn (Order $o) => $o->cancel_reason?->value ?? self::UNKNOWN)
            ->map(fn (Collection $orders, string $reason) => [
                'reason' => $reason,
                'label' => $orders->first()->cancel_reason?->label() ?? 'Причина не указана',
                'count' => $orders->count(),
                'share' => round($orders->count() / $total * 100, 1),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        $by = $cancelled
            ->groupBy(fn (Order $o) => $o->cancelled_by ?? self::UNKNOWN)
            ->map(fn (Collection $orders, string $who) => [
                'who' => $who,
                'label' => self::WHO[$who] ?? $who,
                'count' => $orders->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        return ['reasons' => $reasons, 'by' => $by];
    }
}
