<?php

declare(strict_types=1);

namespace App\Enums;

/** Why an order was cancelled; the customer sees the translated text. */
enum OrderCancelReason: string
{
    // By the customer
    case CHANGED_MIND = 'changed_mind';

    // By the restaurant
    case OUT_OF_STOCK = 'out_of_stock';
    case TOO_BUSY = 'too_busy';
    case CLOSING = 'closing';
    case CANNOT_DELIVER = 'cannot_deliver';

    case OTHER = 'other';

    /** Name in the restaurant's and the admin's panels */
    public function label(): string
    {
        return match ($this) {
            self::CHANGED_MIND => 'Клиент передумал',
            self::OUT_OF_STOCK => 'Нет блюда или продуктов',
            self::TOO_BUSY => 'Не успеваем приготовить',
            self::CLOSING => 'Ресторан закрывается',
            self::CANNOT_DELIVER => 'Не можем доставить по адресу',
            self::OTHER => 'Другая причина',
        };
    }

    /** Text for the customer, in their language */
    public function customerText(?string $locale = null): string
    {
        return __("notifications.cancel_reasons.{$this->value}", [], $locale);
    }

    /** Reasons a restaurant picks from when it declines an order */
    public static function restaurantOptions(): array
    {
        $options = [];
        foreach ([self::OUT_OF_STOCK, self::TOO_BUSY, self::CLOSING, self::CANNOT_DELIVER, self::OTHER] as $reason) {
            $options[$reason->value] = $reason->label();
        }

        return $options;
    }
}
