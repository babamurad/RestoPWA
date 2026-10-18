<?php

declare(strict_types=1);

namespace App\Filament\Admin\Analytics\Widgets;

use App\Domains\Analytics\Services\RestaurantAnalytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformOverview extends StatsOverviewWidget
{
    use ResolvesPlatformPeriod;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return 'Итоги за период: '.$this->period()->label();
    }

    protected function getStats(): array
    {
        $period = $this->period();
        $now = $this->analytics()->summary($period);
        $before = $this->analytics()->summary($period->previous());
        $live = $this->analytics()->rightNow();
        $daily = collect($this->analytics()->daily($period));

        return [
            $this->compare('Оборот', self::money($now['gmv']), $now['gmv'], $before['gmv'])
                ->chart($daily->pluck('gmv')->all()),
            $this->compare('Доход платформы (комиссия)', self::money($now['commission']), $now['commission'], $before['commission']),
            $this->compare('Выполнено заказов', (string) $now['finished'], $now['finished'], $before['finished'])
                ->chart($daily->pluck('orders')->all()),
            $this->compare('Средний чек', self::money($now['average_check']), $now['average_check'], $before['average_check']),
            Stat::make('Рестораны с заказами', (string) $now['active_restaurants'])
                ->description("Сейчас открыто {$live['restaurants_open']} из {$live['restaurants_total']}"),
            Stat::make('Курьеры с доставками', (string) $now['active_couriers'])
                ->description("Сейчас на смене {$live['couriers_online']} из {$live['couriers_total']}")
                ->color($live['couriers_online'] === 0 ? 'danger' : 'gray'),
            Stat::make('Отмены', $now['cancelled'].' из '.$now['orders'])
                ->description($now['cancel_rate'].'% заказов отменено')
                ->color($now['cancel_rate'] >= 10 ? 'danger' : 'gray'),
            Stat::make('Отказы при оформлении', (string) $now['rejections'])
                ->description('Заказ не принят сервером')
                ->color($now['rejections'] > $now['orders'] ? 'danger' : 'gray'),
            Stat::make('Новые клиенты', (string) $now['new_customers'])
                ->description('Доставка курьерам: '.self::money($now['delivery_fees'])),
        ];
    }

    private function compare(string $label, string $value, float|int $current, float|int $previous): Stat
    {
        $change = RestaurantAnalytics::change($current, $previous);

        if ($change === null) {
            return Stat::make($label, $value)->description('Нет данных за прошлый период')->color('gray');
        }

        return Stat::make($label, $value)
            ->description(($change > 0 ? '+' : '').$change.'% к прошлому периоду')
            ->descriptionIcon($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($change >= 0 ? 'success' : 'danger');
    }
}
