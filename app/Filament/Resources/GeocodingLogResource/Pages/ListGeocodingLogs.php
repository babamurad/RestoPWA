<?php

declare(strict_types=1);

namespace App\Filament\Resources\GeocodingLogResource\Pages;

use App\Filament\Resources\GeocodingLogResource;
use Filament\Resources\Pages\ListRecords;

class ListGeocodingLogs extends ListRecords
{
    protected static string $resource = GeocodingLogResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            GeocodingLogResource\Widgets\GeocodingStatsWidget::class,
        ];
    }
}
