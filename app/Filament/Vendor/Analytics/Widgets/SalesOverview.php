<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Analytics\Widgets;

use App\Domains\Analytics\Services\RestaurantAnalytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverview extends StatsOverviewWidget
{
    use ResolvesAnalyticsPeriod;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return 'Итоги за период: '.$this->period()->label();
    }

    protected function getStats(): array
    {
        $period = $this->period();
        $restaurantId = $this->restaurant()->id;
        $now = $this->analytics()->summary($restaurantId, $period);
        $before = $this->analytics()->summary($restaurantId, $period->previous());
        $daily = collect($this->analytics()->daily($restaurantId, $period));

        return [
            $this->stat('Выручка', $this->money($now['revenue']), $now['revenue'], $before['revenue'])
                ->chart($daily->pluck('revenue')->all()),
            $this->stat('Выполнено заказов', (string) $now['completed'], $now['completed'], $before['completed'])
                ->chart($daily->pluck('orders')->all()),
            $this->stat('Средний чек', $this->money($now['average_check']), $now['average_check'], $before['average_check']),
            Stat::make('К выплате ресторану', $this->money($now['payout']))
                ->description('Комиссия платформы: '.$this->money($now['commission']))
                ->color('success'),
            Stat::make('Отмены', $now['cancelled'].' из '.$now['orders'])
                ->description($now['cancel_rate'].'% заказов отменено')
                ->color($now['cancel_rate'] >= 10 ? 'danger' : 'gray'),
            Stat::make('Доставка / самовывоз', $now['delivery_orders'].' / '.$now['pickup_orders'])
                ->description('Доставка оплачена курьерам: '.$this->money($now['delivery_fees'])),
        ];
    }

    /** Value with "+12% к прошлому периоду" under it; "higher is better" for all these figures */
    private function stat(string $label, string $value, float|int $current, float|int $previous): Stat
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

    private function money(float $amount): string
    {
        return number_format($amount, $amount == floor($amount) ? 0 : 2, ',', ' ').' TMT';
    }
}
