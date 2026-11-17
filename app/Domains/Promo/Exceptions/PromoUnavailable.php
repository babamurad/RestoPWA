<?php

declare(strict_types=1);

namespace App\Domains\Promo\Exceptions;

use App\Domains\Promo\PromoRejectReason;
use RuntimeException;

/** The promo code stopped being usable between the check and the order (limit reached, expired) */
class PromoUnavailable extends RuntimeException
{
    public function __construct(public readonly PromoRejectReason $reason)
    {
        parent::__construct($reason->message());
    }
}
