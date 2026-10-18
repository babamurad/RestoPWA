<?php

declare(strict_types=1);

namespace App\Filament\Admin\Analytics\Widgets;

use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class TurnoverChart extends ChartWidget
{
    use ResolvesPlatformPeriod;

    protected ?string $heading = 'Оборот и заказы по дням';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $daily = collect($this->analytics()->daily($this->period()));

        return [
            'datasets' => [
                [
                    'label' => 'Оборот, TMT',
                    'data' => $daily->pluck('gmv')->all(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Заказы',
                    'data' => $daily->pluck('orders')->all(),
                    'borderColor' => '#64748b',
                    'backgroundColor' => '#64748b',
                    'tension' => 0.3,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $daily->map(fn (array $d) => CarbonImmutable::parse($d['date'])->format('d.m'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'position' => 'left'],
                'y1' => ['beginAtZero' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false], 'ticks' => ['precision' => 0]],
            ],
        ];
    }
}
