<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorSettlement extends Model
{
    use HasUuids;

    protected $fillable = [
        'restaurant_id',
        'period_from',
        'period_to',
        'gross_amount',
        'commission_amount',
        'net_payable',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'gross_amount' => MoneyCast::class,
        'commission_amount' => MoneyCast::class,
        'net_payable' => MoneyCast::class,
        'paid_at' => 'datetime',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
