<?php

declare(strict_types=1);

namespace App\Domains\Menu\Models;

use App\Domains\Vendor\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string|null $parent_id
 * @property string|null $vendor_id
 * @property string $name
 */
class Category extends Model
{
    use HasUuids;
    use BelongsToVendor;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'parent_id',
        'name',
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
}
