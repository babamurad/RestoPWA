<?php

declare(strict_types=1);

namespace App\Domains\Menu\Models;

use App\Casts\MoneyCast; // Replace with an actual class or brick/money cast
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string|null $category_id
 * @property string $name
 * @property mixed $price
 * @property Collection $modifiers
 * @property bool $is_available
 * @property-read mixed $final_price
 */
class Product extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'price',
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
            // Custom money cast (brick/money or app cast)
            'price' => class_exists(MoneyCast::class) ? MoneyCast::class : 'array', // placeholder
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
}
