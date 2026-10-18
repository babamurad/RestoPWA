<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Analytics\Widgets;

use App\Domains\Analytics\Data\AnalyticsPeriod;
use App\Domains\Analytics\Services\RestaurantAnalytics;
use App\Domains\Vendor\Models\Restaurant;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Shared by the analytics widgets: the current restaurant (tenant) and the
 * period picked in the page filters, in the restaurant's timezone.
 */
trait ResolvesAnalyticsPeriod
{
    use InteractsWithPageFilters;

    protected function restaurant(): Restaurant
    {
        /** @var Restaurant $restaurant */
        $restaurant = Filament::getTenant();

        return $restaurant;
    }

    protected function period(): AnalyticsPeriod
    {
        return AnalyticsPeriod::fromFilters($this->pageFilters ?? [], $this->restaurant()->timezone ?: 'Asia/Ashgabat');
    }

    protected function analytics(): RestaurantAnalytics
    {
        return app(RestaurantAnalytics::class);
    }
}
