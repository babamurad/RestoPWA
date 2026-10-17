<?php

declare(strict_types=1);

namespace App\Domains\Review\Models;

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's rating (1-5) of one delivered order, with an optional
 * restaurant reply. Hidden reviews are excluded from the public list and
 * from the restaurant's rating.
 */
class Review extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id',
        'user_id',
        'restaurant_id',
        'rating',
        'comment',
        'vendor_reply',
        'replied_at',
        'is_hidden',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_hidden' => 'boolean',
        'replied_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }
}
