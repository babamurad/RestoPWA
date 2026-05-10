<?php

declare(strict_types=1);

namespace App\Domains\Geo\Models;

use Illuminate\Database\Eloquent\Model;

class GeocodingLog extends Model
{
    protected $fillable = [
        'trace_id',
        'user_id',
        'vendor_id',
        'provider',
        'query',
        'lat',
        'lon',
        'status',
        'error_code',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lon' => 'float',
        ];
    }
}
