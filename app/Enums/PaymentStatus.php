<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Payment state of an order (orders.payment_status).
 */
enum PaymentStatus: string
{
    /** Nothing collected yet (cash: paid to the courier on delivery). */
    case UNPAID = 'unpaid';

    /** Online payment started, waiting for the provider's confirmation. */
    case AWAITING_PAYMENT = 'awaiting_payment';

    case PAID = 'paid';

    case REFUNDED = 'refunded';

    case PAYMENT_FAILED = 'payment_failed';

    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'Не оплачен',
            self::AWAITING_PAYMENT => 'Ожидает оплаты',
            self::PAID => 'Оплачен',
            self::REFUNDED => 'Возврат',
            self::PAYMENT_FAILED => 'Ошибка оплаты',
        };
    }

    /** @return array<string, string> value => label, for selects */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
