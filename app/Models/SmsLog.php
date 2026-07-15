<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'phone',
        'message',
        'status',
        'provider',
        'error_message',
    ];
}
