<?php

declare(strict_types=1);

namespace App\Domains\Order\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $status
 * @property array $address
 * @property array $items
 * @property mixed $total
 */
class Order extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'status',
        'address',
        'items',
        'total',
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
                    'metadata' => ['changed_by' => auth()->id() ?? 'system'],
                ]);
            }
        });
    }
}
