<?php

declare(strict_types=1);

namespace App\Filament\Admin\Analytics\Widgets;

use Filament\Widgets\Widget;

class RejectionReasons extends Widget
{
    use ResolvesPlatformPeriod;

    protected string $view = 'filament.admin.analytics.rejection-reasons';

    protected function getViewData(): array
    {
        return ['reasons' => $this->analytics()->rejectionReasons($this->period())];
    }
}
