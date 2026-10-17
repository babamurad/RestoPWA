<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use App\Domains\Order\Models\Order;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An order automatically offered to one courier, who has until expires_at
 * to accept; otherwise the next courier gets it.
 */
class CourierOffer extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_EXPIRED = 'expired';

    /** The order went to someone else (manual assignment, another courier). */
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['order_id', 'courier_id', 'status', 'expires_at', 'responded_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }
}
