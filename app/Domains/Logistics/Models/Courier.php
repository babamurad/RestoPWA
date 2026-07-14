<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use App\Models\User;
use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Courier extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'courier_profiles';

    protected $fillable = [
        'user_id',
        'vendor_id',
        'vehicle_type',
        'status',
        'current_lat',
        'current_lon',
    ];

    protected $casts = [
        'current_lat' => 'float',
        'current_lon' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'vendor_id');
    }
}
