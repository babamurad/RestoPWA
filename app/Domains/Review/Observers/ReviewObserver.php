<?php

declare(strict_types=1);

namespace App\Domains\Review\Observers;

use App\Domains\Review\Models\Review;
use App\Domains\Vendor\Models\Restaurant;

/**
 * Keeps Restaurant::rating / review_count in sync with its visible reviews,
 * so the numbers are never edited by hand.
 */
class ReviewObserver
{
    public function saved(Review $review): void
    {
        $this->recalculate($review->restaurant_id);
    }

    public function deleted(Review $review): void
    {
        $this->recalculate($review->restaurant_id);
    }

    private function recalculate(string $restaurantId): void
    {
        $visible = Review::visible()->where('restaurant_id', $restaurantId);
        $count = (clone $visible)->count();

        Restaurant::whereKey($restaurantId)->update([
            'review_count' => $count,
            'rating' => $count > 0 ? round((float) (clone $visible)->avg('rating'), 1) : null,
        ]);
    }
}
