<?php

declare(strict_types=1);

namespace App\Domains\Vendor\Models;

use App\Casts\MoneyCast;
use App\Domains\Geo\Models\District;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Delivery price a restaurant charges for one city district. A district
 * without a price is a district the restaurant does not deliver to.
 */
class RestaurantDeliveryPrice extends Model
{
    protected $fillable = ['restaurant_id', 'district_id', 'price'];

    protected $casts = [
        'price' => MoneyCast::class,
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
