<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Logistics\Services\CourierDispatcher;
use Illuminate\Console\Command;

/** Scheduler backup for ExpireCourierOffer jobs (queue worker down or late). */
class ExpireCourierOffers extends Command
{
    protected $signature = 'couriers:expire-offers';

    protected $description = 'Передать следующему курьеру просроченные предложения заказов';

    public function handle(CourierDispatcher $dispatcher): int
    {
        $count = $dispatcher->expireOverdue();
        $this->info("Просрочено предложений: {$count}");

        return self::SUCCESS;
    }
}
