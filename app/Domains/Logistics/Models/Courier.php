<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Courier profile. A courier may be bound to several restaurants
 * (courier_restaurant); a courier without bindings is the general pool and
 * serves every restaurant. Automatic dispatch prefers bound couriers.
 */
class Courier extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_OFFLINE = 'offline';

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_BUSY = 'busy';

    public const STATUSES = [
        self::STATUS_OFFLINE => 'Офлайн',
        self::STATUS_AVAILABLE => 'На смене',
        self::STATUS_BUSY => 'Занят',
    ];

    protected $table = 'courier_profiles';

    protected $fillable = [
        'user_id',
        'vehicle_type',
        'status',
        'current_lat',
        'current_lon',
        'last_assigned_at',
    ];

    protected $casts = [
        'current_lat' => 'float',
        'current_lon' => 'float',
        'last_assigned_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurants(): BelongsToMany
    {
        return $this->belongsToMany(Restaurant::class, 'courier_restaurant', 'courier_id', 'restaurant_id')
            ->withTimestamps();
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(CourierEarning::class, 'courier_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(CourierOffer::class);
    }

    public function isGeneralPool(): bool
    {
        return ! $this->restaurants()->exists();
    }

    public function isBoundTo(string $restaurantId): bool
    {
        return $this->restaurants()->whereKey($restaurantId)->exists();
    }

    /** Bound to this restaurant, or general pool. */
    public function canServe(string $restaurantId): bool
    {
        return $this->isGeneralPool() || $this->isBoundTo($restaurantId);
    }

    /** IDs of restaurants the courier is bound to (empty = general pool). */
    public function restaurantIds(): array
    {
        return $this->restaurants()->pluck('restaurants.id')->all();
    }

    public function scopeServing(Builder $query, string $restaurantId): Builder
    {
        return $query->where(function (Builder $q) use ($restaurantId) {
            $q->whereDoesntHave('restaurants')
                ->orWhereHas('restaurants', fn (Builder $r) => $r->whereKey($restaurantId));
        });
    }

    public function activeOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'courier_id')
            ->whereIn('status', [Order::STATUS_READY_FOR_PICKUP, Order::STATUS_DELIVERING]);
    }

    /** Has an order on the way (picked up or being delivered). */
    public function hasActiveDelivery(): bool
    {
        return Order::where('courier_id', $this->id)
            ->whereIn('status', [Order::STATUS_READY_FOR_PICKUP, Order::STATUS_DELIVERING])
            ->exists();
    }
}
