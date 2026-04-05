<?php

declare(strict_types=1);

namespace App\Domains\Order\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

/**
 * @property string $id
 * @property string $vendor_id
 * @property string $user_id
 * @property string $status
 * @property string $payment_status
 * @property array $address
 * @property array $items
 * @property mixed $total
 * @property float $delivery_fee
 * @property string|null $delivery_time
 * @property string|null $payment_method
 * @property string|null $comment
 * @property bool $is_offline
 */
class Order extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'user_id',
        'status',
        'payment_status',
        'address',
        'items',
        'total',
        'delivery_fee',
        'delivery_time',
        'payment_method',
        'comment',
        'is_offline',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'address' => 'array',
            'items' => 'array',
            'total' => MoneyCast::class,
            'delivery_fee' => 'float',
            'is_offline' => 'boolean',
        ];
    }

    /**
     * @return HasMany<OrderStatusHistory, static>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }

    /**
     * Boot the model. Автоматическое логирование изменений статуса.
     */
    protected static function booted(): void
    {
        static::updated(static function (self $order): void {
            if ($order->isDirty('status')) {
                $order->statusHistory()->create([
                    'from_status' => $order->getOriginal('status'),
                    'to_status' => $order->status,
                    'metadata' => ['changed_by' => Auth::id() ?? 'system'],
                ]);
            }
        });
    }
}
