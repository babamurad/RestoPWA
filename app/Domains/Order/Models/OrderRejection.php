<?php

declare(strict_types=1);

namespace App\Domains\Order\Models;

use App\Enums\OrderRejectReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** An order the server refused to create, kept for the admin's rejection statistics */
class OrderRejection extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['reason', 'vendor_id', 'user_id'];

    protected $casts = ['reason' => OrderRejectReason::class];

    /** Never lets bookkeeping break the response to the customer */
    public static function record(OrderRejectReason $reason, mixed $vendorId, mixed $userId): void
    {
        try {
            static::create([
                'reason' => $reason,
                'vendor_id' => is_string($vendorId) && Str::isUuid($vendorId) ? $vendorId : null,
                'user_id' => is_string($userId) && Str::isUuid($userId) ? $userId : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[OrderRejection] Not recorded: '.$e->getMessage());
        }
    }
}
