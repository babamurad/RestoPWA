<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Analytics\Widgets;

use Filament\Widgets\Widget;

class TopProducts extends Widget
{
    use ResolvesAnalyticsPeriod;

    protected string $view = 'filament.vendor.analytics.top-products';

    protected function getViewData(): array
    {
        return [
            'products' => $this->analytics()->topProducts($this->restaurant()->id, $this->period()),
        ];
    }
}
