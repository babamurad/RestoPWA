<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Pages;

use App\Domains\Analytics\Data\AnalyticsPeriod;
use App\Domains\Analytics\Services\RestaurantAnalytics;
use App\Domains\Vendor\Models\Restaurant;
use App\Filament\Vendor\Analytics\Widgets\HourlyOrdersChart;
use App\Filament\Vendor\Analytics\Widgets\RevenueChart;
use App\Filament\Vendor\Analytics\Widgets\SalesOverview;
use App\Filament\Vendor\Analytics\Widgets\TopProducts;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sales analytics of the current restaurant: revenue, orders, average check,
 * best-selling dishes and busy hours for a chosen period.
 */
class Analytics extends Dashboard
{
    use HasFiltersForm;

    protected static string $routePath = 'analytics';

    protected static ?string $title = 'Аналитика';

    protected static ?string $navigationLabel = 'Аналитика';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Продажи';

    protected static ?int $navigationSort = 0;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Экспорт CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    /** Orders of the selected period; semicolon-separated with BOM so Excel opens Cyrillic correctly */
    public function exportCsv(): StreamedResponse
    {
        /** @var Restaurant $restaurant */
        $restaurant = Filament::getTenant();
        $period = AnalyticsPeriod::fromFilters($this->filters ?? [], $restaurant->timezone ?: 'Asia/Ashgabat');
        $rows = app(RestaurantAnalytics::class)->exportRows($restaurant->id, $period);

        $filename = 'orders_'.$period->from->format('Y-m-d').'_'.$period->to->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_keys($rows[0] ?? ['Заказов за период нет' => '']), ';');
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => is_float($v) ? number_format($v, 2, ',', '') : $v, $row), ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function filtersForm(Schema $schema): Schema
    {
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
                ->maxDate(now())
                ->visible(fn (Get $get) => $get('period') === 'custom'),
            DatePicker::make('to')
                ->label('По')
                ->native(false)
                ->displayFormat('d.m.Y')
                ->maxDate(now())
                ->visible(fn (Get $get) => $get('period') === 'custom'),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            SalesOverview::class,
            RevenueChart::class,
            TopProducts::class,
            HourlyOrdersChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['md' => 2];
    }
}
