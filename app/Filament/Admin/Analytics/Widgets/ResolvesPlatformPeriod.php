<?php

declare(strict_types=1);

namespace App\Filament\Admin\Analytics\Widgets;

use App\Domains\Analytics\Data\AnalyticsPeriod;
use App\Domains\Analytics\Services\PlatformAnalytics;
use App\Filament\Pages\Analytics;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/** The period picked in the admin analytics filters, in the city's timezone */
trait ResolvesPlatformPeriod
{
    use InteractsWithPageFilters;

    protected function period(): AnalyticsPeriod
    {
        return AnalyticsPeriod::fromFilters($this->pageFilters ?? [], Analytics::TIMEZONE);
    }

    protected function analytics(): PlatformAnalytics
    {
        return app(PlatformAnalytics::class);
    }

    protected static function money(float $amount): string
    {
        return number_format($amount, $amount == floor($amount) ? 0 : 2, ',', ' ').' TMT';
    }
}
