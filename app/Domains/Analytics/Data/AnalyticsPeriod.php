<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Data;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * A reporting period in the restaurant's local time ("today" in Ashgabat,
 * not in UTC). Bounds are inclusive local days.
 */
final class AnalyticsPeriod
{
    public const PRESETS = [
        'today' => 'Сегодня',
        'yesterday' => 'Вчера',
        '7_days' => 'Последние 7 дней',
        '30_days' => 'Последние 30 дней',
        'this_month' => 'Этот месяц',
        'last_month' => 'Прошлый месяц',
        'custom' => 'Свой период',
    ];

    public const DEFAULT_PRESET = '7_days';

    /** Longest custom range, keeps the report fast */
    public const MAX_DAYS = 366;

    private function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly string $timezone,
    ) {}

    public static function between(string $from, string $to, string $timezone): self
    {
        $start = CarbonImmutable::parse($from, $timezone)->startOfDay();
        $end = CarbonImmutable::parse($to, $timezone)->endOfDay();

        if ($end->lessThan($start)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        if ($start->diffInDays($end) >= self::MAX_DAYS) {
            $start = $end->subDays(self::MAX_DAYS - 1)->startOfDay();
        }

        return new self($start, $end, $timezone);
    }

    public static function preset(string $preset, string $timezone, ?CarbonImmutable $now = null): self
    {
        $today = ($now ?? CarbonImmutable::now())->setTimezone($timezone)->startOfDay();

        [$from, $to] = match ($preset) {
            'today' => [$today, $today],
            'yesterday' => [$today->subDay(), $today->subDay()],
            '7_days' => [$today->subDays(6), $today],
            '30_days' => [$today->subDays(29), $today],
            'this_month' => [$today->startOfMonth(), $today],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            default => throw new InvalidArgumentException("Unknown period preset [{$preset}]"),
        };

        return new self($from->startOfDay(), $to->endOfDay(), $timezone);
    }

    /**
     * Period from the analytics page filters: {period, from?, to?}. Unknown or
     * incomplete input falls back to the default preset.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function fromFilters(array $filters, string $timezone): self
    {
        $preset = $filters['period'] ?? self::DEFAULT_PRESET;

        if ($preset === 'custom' && ! empty($filters['from'])) {
            return self::between($filters['from'], $filters['to'] ?? $filters['from'], $timezone);
        }

        if (! array_key_exists($preset, self::PRESETS) || $preset === 'custom') {
            $preset = self::DEFAULT_PRESET;
        }

        return self::preset($preset, $timezone);
    }

    /** The same number of days right before this period, for "vs previous" deltas */
    public function previous(): self
    {
        $days = $this->days();

        return new self($this->from->subDays($days), $this->from->subDay()->endOfDay(), $this->timezone);
    }

    public function days(): int
    {
        return (int) $this->from->startOfDay()->diffInDays($this->to->startOfDay()) + 1;
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} bounds in UTC for querying created_at */
    public function utcBounds(): array
    {
        return [$this->from->utc(), $this->to->utc()];
    }

    public function label(): string
    {
        $from = $this->from->format('d.m.Y');
        $to = $this->to->format('d.m.Y');

        return $from === $to ? $from : "{$from} — {$to}";
    }
}
