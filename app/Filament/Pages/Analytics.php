<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domains\Analytics\Data\AnalyticsPeriod;
use App\Filament\Admin\Analytics\Widgets\PlatformOverview;
use App\Filament\Admin\Analytics\Widgets\RejectionReasons;
use App\Filament\Admin\Analytics\Widgets\RestaurantsTable;
use App\Filament\Admin\Analytics\Widgets\TopCouriers;
use App\Filament\Admin\Analytics\Widgets\TurnoverChart;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Platform analytics for the admin: turnover across restaurants, platform income,
 * active restaurants and couriers, why orders were refused.
 */
class Analytics extends Dashboard
{
    use HasFiltersForm;

    /** Days are counted in the city's time */
    public const TIMEZONE = 'Asia/Ashgabat';

    protected static string $routePath = 'analytics';

    protected static ?string $title = 'Аналитика';

    protected static ?string $navigationLabel = 'Аналитика';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = -1;

    public function filtersForm(Schema $schema): Schema
    {
        $today = now(self::TIMEZONE)->toDateString();

        return $schema->components([
            Select::make('period')
                ->label('Период')
                ->options(AnalyticsPeriod::PRESETS)
                ->default(AnalyticsPeriod::DEFAULT_PRESET)
                ->selectablePlaceholder(false)
                ->live(),
            DatePicker::make('from')
                ->label('С')
                ->native(false)
                ->displayFormat('d.m.Y')
                ->maxDate($today)
                ->visible(fn (Get $get) => $get('period') === 'custom'),
            DatePicker::make('to')
                ->label('По')
                ->native(false)
                ->displayFormat('d.m.Y')
                ->maxDate($today)
                ->visible(fn (Get $get) => $get('period') === 'custom'),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            PlatformOverview::class,
            TurnoverChart::class,
            RestaurantsTable::class,
            RejectionReasons::class,
            TopCouriers::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['md' => 2];
    }
}
