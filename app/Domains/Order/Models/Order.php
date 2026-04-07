<?php

declare(strict_types=1);

namespace App\Domains\Order\Models;

use App\Casts\MoneyCast;
use App\Models\User;
use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property \Carbon\Carbon $created_at
 */
class Order extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_DELIVERING = 'delivering';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING => ['label' => 'Новый', 'color' => 'yellow', 'icon' => 'clock'],
        self::STATUS_CONFIRMED => ['label' => 'Подтверждён', 'color' => 'blue', 'icon' => 'check'],
        self::STATUS_PREPARING => ['label' => 'Готовится', 'color' => 'orange', 'icon' => 'fire'],
        self::STATUS_DELIVERING => ['label' => 'Доставляется', 'color' => 'purple', 'icon' => 'truck'],
        self::STATUS_DELIVERED => ['label' => 'Доставлен', 'color' => 'green', 'icon' => 'check-circle'],
        self::STATUS_CANCELLED => ['label' => 'Отменён', 'color' => 'red', 'icon' => 'x'],
    ];

    public const FILTER_NEW = 'new';
    public const FILTER_IN_PROGRESS = 'in_progress';
    public const FILTER_DELIVERING = 'delivering';
    public const FILTER_COMPLETED = 'completed';
    public const FILTER_CANCELLED = 'cancelled';

    public const FILTERS = [
        self::FILTER_NEW => ['statuses' => [self::STATUS_PENDING], 'label' => 'Новые'],
        self::FILTER_IN_PROGRESS => ['statuses' => [self::STATUS_CONFIRMED, self::STATUS_PREPARING], 'label' => 'В работе'],
        self::FILTER_DELIVERING => ['statuses' => [self::STATUS_DELIVERING], 'label' => 'Доставляются'],
        self::FILTER_COMPLETED => ['statuses' => [self::STATUS_DELIVERED], 'label' => 'Завершённые'],
        self::FILTER_CANCELLED => ['statuses' => [self::STATUS_CANCELLED], 'label' => 'Отменённые'],
    ];

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

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'vendor_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderStatusHistory, static>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'gray';
    }

    public function getNextStatuses(): array
    {
        $transitions = [
            self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
            self::STATUS_CONFIRMED => [self::STATUS_PREPARING, self::STATUS_CANCELLED],
            self::STATUS_PREPARING => [self::STATUS_DELIVERING, self::STATUS_CANCELLED],
            self::STATUS_DELIVERING => [self::STATUS_DELIVERED],
        ];

        return $transitions[$this->status] ?? [];
    }

    public function getCanTransitionToAttribute(): bool
    {
        return !empty($this->getNextStatuses());
    }

    public function getItemsCountAttribute(): int
    {
        if (!$this->items) {
            return 0;
        }
        return array_sum(array_column($this->items, 'quantity'));
    }

    public function getCustomerNameAttribute(): ?string
    {
        return $this->address['name'] ?? null;
    }

    public function getCustomerPhoneAttribute(): ?string
    {
        return $this->address['phone'] ?? null;
    }

    public function getCustomerAddressAttribute(): ?string
    {
        if (!isset($this->address['address'])) {
            return null;
        }
        $parts = array_filter([
            $this->address['address'] ?? null,
            $this->address['house'] ?? null,
            $this->address['apartment'] ? 'кв. ' . $this->address['apartment'] : null,
        ]);
        return implode(', ', $parts);
    }

    public function getOrderNumberAttribute(): string
    {
        return '#' . strtoupper(substr($this->id, 0, 8));
    }

    public function getClientNameAttribute(): ?string
    {
        return $this->customer_name;
    }

    public function getClientPhoneAttribute(): ?string
    {
        return $this->customer_phone;
    }

    public function getFullAddressAttribute(): ?string
    {
        return $this->customer_address;
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
