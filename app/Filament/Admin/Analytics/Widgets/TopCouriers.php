<?php

declare(strict_types=1);

namespace App\Filament\Admin\Analytics\Widgets;

use Filament\Widgets\Widget;

class TopCouriers extends Widget
{
    use ResolvesPlatformPeriod;

    protected string $view = 'filament.admin.analytics.top-couriers';

    protected function getViewData(): array
    {
        return ['couriers' => $this->analytics()->couriers($this->period())];
    }
}
