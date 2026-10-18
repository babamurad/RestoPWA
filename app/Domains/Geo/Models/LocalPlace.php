<?php

declare(strict_types=1);

namespace App\Domains\Geo\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LocalPlace extends Model
{
    use HasUuids;

    protected $fillable = [
        'city',
        'name',
        'aliases',
        'lat',
        'lon',
        'type',
        'popularity',
        'is_verified',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
            'lat' => 'float',
            'lon' => 'float',
            'popularity' => 'integer',
            'is_verified' => 'boolean',
        ];
    }

    /** Place type => label shown in the admin */
    public const TYPE_LABELS = [
        'district' => 'Район',
        'street' => 'Улица',
        'landmark' => 'Ориентир',
        'market' => 'Рынок',
        'school' => 'Школа',
        'hospital' => 'Больница',
        'restaurant' => 'Ресторан',
        'building' => 'Здание',
        'other' => 'Другое',
    ];

    public const TYPES = [
        'district',
        'street',
        'landmark',
        'market',
        'school',
        'hospital',
        'restaurant',
        'building',
        'other',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? self::TYPE_LABELS['other'];
    }
}
