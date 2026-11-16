<?php

declare(strict_types=1);

namespace App\Filament\Admin\Analytics\Widgets;

use Filament\Widgets\Widget;

class CancellationReasons extends Widget
{
    use ResolvesPlatformPeriod;

    protected string $view = 'filament.analytics.cancellation-reasons';

    protected function getViewData(): array
    {
        return ['summary' => $this->analytics()->cancellationReasons($this->period())];
    }
}
