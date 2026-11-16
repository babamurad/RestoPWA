<?php

declare(strict_types=1);

namespace App\Domains\Order\Exceptions;

use App\Domains\Order\Models\Order;
use DomainException;

/** The order cannot go from its current status to the requested one. */
class InvalidStatusTransition extends DomainException
{
    public function __construct(public readonly Order $order, public readonly string $to)
    {
        parent::__construct("Order {$order->id} cannot go from \"{$order->status}\" to \"{$to}\".");
    }
}
