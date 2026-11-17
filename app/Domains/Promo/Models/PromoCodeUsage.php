<?php

declare(strict_types=1);

namespace App\Domains\Promo\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCodeUsage extends Model
{
    use HasUuids;

    protected $fillable = ['promo_code_id', 'user_id', 'order_id', 'discount_amount'];

    protected function casts(): array
    {
        return ['discount_amount' => MoneyCast::class];
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }
}
