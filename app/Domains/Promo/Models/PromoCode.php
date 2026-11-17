<?php

declare(strict_types=1);

namespace App\Domains\Promo\Models;

use App\Casts\MoneyCast;
use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    use HasUuids;

    protected $fillable = [
        'code', 'description', 'percent', 'amount', 'max_discount', 'min_order', 'restaurant_id',
        'starts_at', 'expires_at', 'usage_limit', 'per_user_limit', 'used_count', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'percent' => 'float',
            'amount' => MoneyCast::class,
            'max_discount' => MoneyCast::class,
            'min_order' => MoneyCast::class,
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** Codes are case-insensitive for the customer and stored upper-case */
    public static function normalize(string $code): string
    {
        return mb_strtoupper(trim($code));
    }

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = self::normalize($value);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }
}
