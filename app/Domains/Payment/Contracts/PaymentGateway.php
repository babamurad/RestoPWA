<?php

declare(strict_types=1);

namespace App\Domains\Payment\Contracts;

use App\Domains\Order\Models\Order;
use App\Domains\Payment\Models\PaymentTransaction;

/**
 * One payment provider. Cash is the only implementation today; an online
 * provider (bank acquiring) is added as another implementation once its API
 * is known, without changing checkout.
 */
interface PaymentGateway
{
    /** Code stored in payment_transactions.provider and matched to orders.payment_method. */
    public function code(): string;

    /** Called right after the order is created: opens the payment transaction. */
    public function initiate(Order $order): PaymentTransaction;
}
