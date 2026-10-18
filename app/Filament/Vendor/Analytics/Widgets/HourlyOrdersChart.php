<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Analytics\Widgets;

use Filament\Widgets\ChartWidget;

class HourlyOrdersChart extends ChartWidget
{
    use ResolvesAnalyticsPeriod;

    protected ?string $heading = 'Заказы по часам';

    protected ?string $description = 'Когда кухня загружена сильнее всего (время ресторана)';

    protected ?string $maxHeight = '260px';

    protected ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $hours = $this->analytics()->hourly($this->restaurant()->id, $this->period());

        return [
            'datasets' => [
                [
                    'label' => 'Заказы',
                    'data' => array_values($hours),
                    'backgroundColor' => '#f97316',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => array_map(fn (int $h) => sprintf('%02d', $h), array_keys($hours)),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
