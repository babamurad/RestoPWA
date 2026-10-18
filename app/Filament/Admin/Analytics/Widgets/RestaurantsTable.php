<?php

declare(strict_types=1);

namespace App\Filament\Admin\Analytics\Widgets;

use Filament\Widgets\Widget;

class RestaurantsTable extends Widget
{
    use ResolvesPlatformPeriod;

    protected string $view = 'filament.admin.analytics.restaurants';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return ['restaurants' => $this->analytics()->restaurants($this->period())];
    }
}
