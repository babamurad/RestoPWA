<?php

declare(strict_types=1);

namespace App\Domains\Promo;

enum PromoRejectReason: string
{
    case NOT_FOUND = 'not_found';
    case INACTIVE = 'inactive';
    case NOT_STARTED = 'not_started';
    case EXPIRED = 'expired';
    case WRONG_RESTAURANT = 'wrong_restaurant';
    case MIN_ORDER = 'min_order';
    case USAGE_LIMIT = 'usage_limit';
    case USER_LIMIT = 'user_limit';
    case LOGIN_REQUIRED = 'login_required';
    case CHANGED = 'changed';

    public function message(): string
    {
        return match ($this) {
            self::NOT_FOUND => 'Промокод не найден.',
            self::INACTIVE => 'Промокод отключён.',
            self::NOT_STARTED => 'Промокод ещё не действует.',
            self::EXPIRED => 'Срок действия промокода истёк.',
            self::WRONG_RESTAURANT => 'Промокод не действует в этом ресторане.',
            self::MIN_ORDER => 'Сумма заказа ниже минимальной для этого промокода.',
            self::USAGE_LIMIT => 'Промокод уже использован максимальное число раз.',
            self::USER_LIMIT => 'Вы уже использовали этот промокод.',
            self::LOGIN_REQUIRED => 'Войдите в профиль, чтобы применить промокод.',
            self::CHANGED => 'Условия промокода изменились. Примените его заново.',
        };
    }
}
