<?php

declare(strict_types=1);

namespace App\Domains\Vendor\Models;

use App\Domains\Vendor\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $vendor_id
 * @property string $name
 * @property array|null $settings
 * @property mixed $delivery_zones
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Restaurant extends Model
{
    use HasUuids;
    use BelongsToVendor;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'name',
        'settings',
        'delivery_zones',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Возвращает geometry как array координат.
     * 
     * @return array<mixed>
     */
    public function deliveryZones(): array
    {
        // Если поле `delivery_zones` содержит сырой GeoJSON (или spatial data)
        $zones = $this->getAttribute('delivery_zones');

        if (is_string($zones)) {
            $decoded = json_decode($zones, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($zones) ? $zones : [];
    }

    /**
     * @param Builder<static> $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
