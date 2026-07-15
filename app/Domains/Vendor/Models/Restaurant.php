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
use App\Domains\Geo\Services\GeoJsonNormalizer;

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
 * @property bool $is_paused
 * @property string|null $pause_reason
 * @property string $timezone
 * @property-read bool $is_open_now
 * @property-read \Illuminate\Database\Eloquent\Collection|RestaurantSchedule[] $schedules
 */
class Restaurant extends Model
{

    use HasFactory;
    use HasUuids;

    protected static function newFactory(): Factory
    {
        return RestaurantFactory::new();
    }

    public ?array $tempDeliveryZones = null;

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (Restaurant $restaurant) {
            if ($restaurant->isDirty('delivery_zones')) {
                if (DB::getDriverName() !== 'sqlite' && static::checkPostGis()) {
                    $val = $restaurant->attributes['delivery_zones'] ?? null;
                    if (!($val instanceof \Illuminate\Database\Query\Expression)) {
                        $restaurant->tempDeliveryZones = is_string($val) ? json_decode($val, true) : $val;
                        unset($restaurant->attributes['delivery_zones']);
                    }
                }
            }
        });

        static::saved(function (Restaurant $restaurant) {
            if ($restaurant->tempDeliveryZones !== null) {
                $value = $restaurant->tempDeliveryZones;
                $restaurant->tempDeliveryZones = null;
                $restaurant->updateDeliveryZone($value);
            }
        });
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
        'is_paused',
        'pause_reason',
        'timezone',
        'courier_fixed_fee',
        'courier_percent_fee',
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
            'is_paused' => 'boolean',
            'courier_fixed_fee' => 'float',
            'courier_percent_fee' => 'float',
        ];
    }

    protected static ?bool $hasPostgis = null;

    protected static function checkPostGis(): bool
    {
        if (self::$hasPostgis !== null) {
            return self::$hasPostgis;
        }
        
        try {
            $result = DB::select("SELECT proname FROM pg_proc WHERE proname = 'addgeometrycolumn' LIMIT 1");
            self::$hasPostgis = !empty($result);
        } catch (\Throwable $e) {
            self::$hasPostgis = false;
        }
        
        return self::$hasPostgis;
    }

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
                    if (DB::getDriverName() !== 'sqlite' && self::checkPostGis()) {
                        $geojson = DB::selectOne("SELECT ST_AsGeoJSON(?) as geo", [$value])?->geo;
                        return $geojson ? json_decode($geojson, true) : null;
                    }
                } catch (\Exception $e) {
                    // Ignore and fallback
                }
                
                return is_string($value) ? json_decode($value, true) : null;
            },
            set: function ($value) {
                if (empty($value)) {
                    return null;
                }

                $normalized = GeoJsonNormalizer::toMultiPolygon($value);
                if (empty($normalized)) {
                    return null;
                }

                return json_encode($normalized);
            }
        );
    }

    /**
     * Update delivery zone with parameter binding.
     */
    public function updateDeliveryZone(mixed $value): void
    {
        if (empty($value)) {
            $this->delivery_zones = null;
            if (DB::getDriverName() === 'sqlite' || !self::checkPostGis()) {
                $this->save();
            } else {
                DB::update('UPDATE restaurants SET delivery_zones = NULL WHERE id = ?', [$this->id]);
                $this->refresh();
            }
            return;
        }

        $normalized = GeoJsonNormalizer::toMultiPolygon($value);
        if (empty($normalized)) {
            $this->delivery_zones = null;
            if (DB::getDriverName() === 'sqlite' || !self::checkPostGis()) {
                $this->save();
            } else {
                DB::update('UPDATE restaurants SET delivery_zones = NULL WHERE id = ?', [$this->id]);
                $this->refresh();
            }
            return;
        }

        $json = json_encode($normalized);

        if (DB::getDriverName() === 'sqlite' || !self::checkPostGis()) {
            $this->delivery_zones = $json;
            $this->save();
            return;
        }

        // For PostgreSQL/PostGIS with parameter binding
        DB::update(
            'UPDATE restaurants SET delivery_zones = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326), updated_at = ? WHERE id = ?',
            [$json, now(), $this->id]
        );

        $this->refresh();
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

    /**
     * Get the owner of the restaurant.
     */
    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    /**
     * Get the schedules for the restaurant.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(RestaurantSchedule::class, 'restaurant_id');
    }

    /**
     * Determine if the restaurant is currently open.
     */
    public function getIsOpenNowAttribute(): bool
    {
        if ($this->is_paused) {
            return false;
        }

        if (!$this->relationLoaded('schedules')) {
            $this->load('schedules');
        }

        if ($this->schedules->isEmpty()) {
            return true; // Open by default if no schedule defined
        }

        $now = \Carbon\Carbon::now($this->timezone ?: 'Asia/Ashgabat');
        $weekday = $now->dayOfWeekIso - 1; // 0 = Monday, 6 = Sunday

        $schedule = $this->schedules->firstWhere('weekday', $weekday);

        if (!$schedule || $schedule->is_closed) {
            return false;
        }

        if (!$schedule->opens_at || !$schedule->closes_at) {
            return true;
        }

        $opensAt = \Carbon\Carbon::parse($schedule->opens_at, $this->timezone ?: 'Asia/Ashgabat');
        $closesAt = \Carbon\Carbon::parse($schedule->closes_at, $this->timezone ?: 'Asia/Ashgabat');
        $currentTime = \Carbon\Carbon::createFromFormat('H:i:s', $now->format('H:i:s'), $this->timezone ?: 'Asia/Ashgabat');

        if ($closesAt->lt($opensAt)) {
            // Overnight shift (e.g. 22:00 - 02:00)
            return $currentTime->gte($opensAt) || $currentTime->lt($closesAt);
        }

        return $currentTime->between($opensAt, $closesAt);
    }
}
