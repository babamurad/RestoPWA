<?php

declare(strict_types=1);

namespace App\Domains\Menu\Models;

use App\Domains\Vendor\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string|null $vendor_id
 * @property string $name
 * @property int $sort_order
 * @property bool $is_active
 */
class Category extends Model
{
    use BelongsToVendor;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'parent_id',
        'name',
        'sort_order',
        'is_active',
    ];

    /**
     * @return BelongsTo<Category, static>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, static>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Product, static>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * @return BelongsTo<\App\Domains\Vendor\Models\Restaurant, static>
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Vendor\Models\Restaurant::class, 'vendor_id');
    }

    protected static function booted(): void
    {
        parent::booted();
        static::bootBelongsToVendor();
    }
}
