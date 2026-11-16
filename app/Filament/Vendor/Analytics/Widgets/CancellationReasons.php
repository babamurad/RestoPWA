<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Analytics\Widgets;

use Filament\Widgets\Widget;

class CancellationReasons extends Widget
{
    use ResolvesAnalyticsPeriod;

    protected string $view = 'filament.analytics.cancellation-reasons';

    protected function getViewData(): array
    {
        return ['summary' => $this->analytics()->cancellationReasons($this->restaurant()->id, $this->period())];
    }
}
