<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Jobs;

use App\Domains\Logistics\Models\CourierOffer;
use App\Domains\Logistics\Services\CourierDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Fires when an offer's time is up; passes the order on if still unanswered. */
class ExpireCourierOffer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public string $offerId) {}

    public function handle(CourierDispatcher $dispatcher): void
    {
        $offer = CourierOffer::find($this->offerId);
        if ($offer) {
            $dispatcher->expire($offer);
        }
    }
}
