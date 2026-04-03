<?php

declare(strict_types=1);

namespace App\Domains\Menu\Models;

use App\Casts\MoneyCast;
use App\Domains\Vendor\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string|null $category_id
 * @property string|null $vendor_id
 * @property string $name
 * @property mixed $price
 * @property Collection $modifiers
 * @property string|null $image
 * @property int|null $weight_g
 * @property bool $is_available
 * @property-read mixed $final_price
 */
class Product extends Model
{
    use HasUuids;
    use BelongsToVendor;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'category_id',
        'name',
        'description',
        'price',
        'image',
        'weight_g',
        'modifiers',
        'is_available',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'modifiers' => AsCollection::class,
            'price' => MoneyCast::class,
            'is_available' => 'boolean',
        ];
    }

    /**
     * Accessor для расчета финальной цены с учетом модификаторов.
     * Возможен также вариант `protected function getFinalPriceAttribute()` (legacy).
     * 
     * @return Attribute<mixed, never>
     */
    protected function finalPrice(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes): mixed {
                $basePrice = $this->price;
                $modifiers = $this->modifiers;

                // Пример: если у нас number/float
                if (is_numeric($basePrice)) {
                    $modifiersSum = $modifiers instanceof Collection 
                        ? $modifiers->sum('price') 
                        : 0;
                        
                    return $basePrice + $modifiersSum;
                }

                // If brick/money is used, it should be something like:
                // $baseMoney = $this->price; // Money object
                // ...
                return $basePrice; 
            }
        );
    }

    /**
     * @param Builder<static> $query
     * @return Builder<static>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    protected static function booted(): void
    {
        parent::booted();
        static::bootBelongsToVendor();
    }

    /**
     * Get the product's image URL.
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image && str_starts_with($this->image, 'http')) {
            return $this->image;
        }

        return $this->image
            ? asset('storage/' . $this->image)
            : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=80&w=200';
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the restaurant that owns the product.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Vendor\Models\Restaurant::class, 'vendor_id');
    }
}
