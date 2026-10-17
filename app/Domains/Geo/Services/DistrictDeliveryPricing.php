<?php

declare(strict_types=1);

namespace App\Domains\Geo\Services;

use App\Domains\Vendor\Models\Restaurant;
use App\Domains\Vendor\Models\RestaurantDeliveryPrice;

/**
 * Delivery price by city district. A restaurant that has priced at least one
 * district uses this mode: it delivers only to its priced, active districts,
 * at that district's price. Restaurants without prices keep the legacy flat
 * Restaurant::delivery_fee and their own delivery zone polygon.
 */
class DistrictDeliveryPricing
{
    public function usesDistrictPricing(Restaurant $restaurant): bool
    {
        return RestaurantDeliveryPrice::where('restaurant_id', $restaurant->id)->exists();
    }

    /**
     * Price of the restaurant's district containing the point, or null when the
     * point is in no district the restaurant delivers to.
     */
    public function quote(Restaurant $restaurant, float $lat, float $lon): ?RestaurantDeliveryPrice
    {
        $prices = RestaurantDeliveryPrice::with('district')
            ->where('restaurant_id', $restaurant->id)
            ->whereHas('district', fn ($q) => $q->active())
            ->get()
            ->sortBy(fn (RestaurantDeliveryPrice $p) => $p->district->sort_order);

        return $prices->first(fn (RestaurantDeliveryPrice $p) => $p->district->contains($lat, $lon));
    }

    /** Cheapest district price, for "delivery from X TMT" before an address is chosen. */
    public function minimumPrice(Restaurant $restaurant): ?float
    {
        $min = RestaurantDeliveryPrice::where('restaurant_id', $restaurant->id)
            ->whereHas('district', fn ($q) => $q->active())
            ->min('price');

        return $min === null ? null : round((int) $min / 100, 2);
    }
}
