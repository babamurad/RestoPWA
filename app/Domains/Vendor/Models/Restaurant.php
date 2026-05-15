<?php

declare(strict_types=1);

namespace App\Domains\Vendor\Models;

use App\Domains\Menu\Models\Category;
use App\Domains\Menu\Models\Product;

use Carbon\Carbon;
use Database\Factories\RestaurantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

/**
 * @property string $id
 * @property string|null $vendor_id
 * @property string $name
 * @property string|null $image
 * @property string|null $cover_image
 * @property float|null $rating
 * @property int $review_count
 * @property string|null $delivery_time
 * @property float $delivery_fee
 * @property int $min_order
 * @property array|null $settings
 * @property mixed $delivery_zones
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Restaurant extends Model
{

    use HasFactory;
    use HasUuids;

    protected static function newFactory(): Factory
    {
        return RestaurantFactory::new();
    }

    protected static function booted(): void
    {
        parent::booted();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'vendor_id',
        'slug',
        'name',
        'image',
        'cover_image',
        'rating',
        'review_count',
        'delivery_time',
        'delivery_fee',
        'min_order',
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
            'rating' => 'float',
            'review_count' => 'integer',
            'delivery_fee' => 'float',
            'min_order' => 'integer',
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return Attribute<array<mixed>|null, string|array<mixed>|null>
     */
    protected function deliveryZones(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (empty($value)) {
                    return null;
                }

                if (is_array($value)) {
                    return $value;
                }

                // If it's already GeoJSON string
                if (is_string($value) && (str_contains($value, '"type"') || str_contains($value, '"coordinates"'))) {
                    return json_decode($value, true);
                }

                // Fallback for PostGIS binary if not automatically converted by DB driver
                try {
                    $geojson = DB::selectOne("SELECT ST_AsGeoJSON(?) as geo", [$value])?->geo;
                    return $geojson ? json_decode($geojson, true) : null;
                } catch (\Exception $e) {
                    return is_string($value) ? json_decode($value, true) : null;
                }
            },
            set: function ($value) {
                if (empty($value)) {
                    return null;
                }

                $json = is_array($value) ? json_encode($value) : $value;

                if (DB::getDriverName() === 'sqlite') {
                    return $json;
                }

                // Wrap in ST_Multi if it's a MultiPolygon to be safe
                return DB::raw("ST_GeomFromGeoJSON('$json')");
            }
        );
    }

    /**
     * Helper to get zones as array.
     */
    public function getZonesArray(): array
    {
        $zones = $this->delivery_zones;

        if (is_string($zones)) {
            $decoded = json_decode($zones, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($zones) ? $zones : [];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the restaurant's image URL.
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image && str_starts_with($this->image, 'http')) {
            return $this->image;
        }

        return $this->image
            ? asset('storage/'.$this->image)
            : 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&q=80&w=400';
    }

    /**
     * Get the categories for the restaurant.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'vendor_id');
    }

    /**
     * Get the products for the restaurant.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }
}
