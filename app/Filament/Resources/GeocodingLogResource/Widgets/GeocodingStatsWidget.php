<?php

namespace App\Filament\Resources\GeocodingLogResource\Widgets;

use App\Domains\Geo\Models\GeocodingLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GeocodingStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $zoneMissingCount = GeocodingLog::where('status', 'zone_missing')->count();
        $outsideCount = GeocodingLog::where('status', 'outside')->count();
        $invalidGeometryCount = GeocodingLog::where('status', 'invalid_geometry')->count();
        $postgisErrorCount = GeocodingLog::where('status', 'postgis_error')->count();
        $insideCount = GeocodingLog::where('status', 'inside')->count();

        return [
            Stat::make('Внутри зоны (inside)', $insideCount)
                ->description('Количество успешных доставок')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Вне зоны (outside)', $outsideCount)
                ->description('Попытки доставить вне зоны')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
            Stat::make('Зона не настроена (zone_missing)', $zoneMissingCount)
                ->description('Запросы без настроенной зоны')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),
            Stat::make('Ошибка PostGIS (postgis_error)', $postgisErrorCount)
                ->description('Технические сбои гео-запросов')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('danger'),
            Stat::make('Ошибка геометрии (invalid_geometry)', $invalidGeometryCount)
                ->description('Некорректный полигон зоны')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
        ];
    }
}
