<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Review\Models\Review;
use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Console\Command;

/**
 * Rebuilds Restaurant::rating / review_count from visible reviews.
 * Run once after deploying reviews to replace seeded/hand-entered numbers.
 */
class RecalculateRestaurantRatings extends Command
{
    protected $signature = 'reviews:recalculate-ratings';

    protected $description = 'Пересчитать рейтинг и число отзывов ресторанов по видимым отзывам';

    public function handle(): int
    {
        $stats = Review::visible()
            ->selectRaw('restaurant_id, COUNT(*) as review_count, AVG(rating) as avg_rating')
            ->groupBy('restaurant_id')
            ->get()
            ->keyBy('restaurant_id');

        $updated = 0;
        Restaurant::query()->each(function (Restaurant $restaurant) use ($stats, &$updated) {
            $row = $stats->get($restaurant->id);
            $restaurant->forceFill([
                'review_count' => $row ? (int) $row->review_count : 0,
                'rating' => $row ? round((float) $row->avg_rating, 1) : null,
            ])->saveQuietly();
            $updated++;
        });

        $this->info("Пересчитано ресторанов: {$updated}");

        return self::SUCCESS;
    }
}
