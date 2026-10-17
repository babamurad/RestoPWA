<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Domains\Order\Models\Order;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One money movement for an order: a payment or a refund, through a provider
 * (cash today, an online provider later). Amount is in minor units (тыйын).
 */
class PaymentTransaction extends Model
{
    use HasUuids;

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_REFUND = 'refund';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'provider',
        'provider_transaction_id',
        'type',
        'status',
        'amount',
        'currency',
        'raw_payload',
        'idempotency_key',
    ];

    protected $casts = [
        'amount' => 'integer',
        'raw_payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
