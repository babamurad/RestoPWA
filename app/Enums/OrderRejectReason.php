<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderRejectReason: string
{
    case EMPTY_CART = 'empty_cart';
    case NO_VENDOR = 'no_vendor';
    case MULTI_VENDOR = 'multi_vendor';
    case INVALID_VENDOR = 'invalid_vendor';
    case VENDOR_NOT_FOUND = 'vendor_not_found';
    case INVALID_ITEMS = 'invalid_items';
    case ITEM_NOT_FOUND = 'item_not_found';
    case ITEM_UNAVAILABLE = 'item_unavailable';
    case INVALID_PRICE = 'invalid_price';
    case INVALID_TOTAL = 'invalid_total';
    case MISSING_ADDRESS = 'missing_address';
    case INVALID_COORDINATES = 'invalid_coordinates';
    case OUTSIDE_DELIVERY_ZONE = 'outside_delivery_zone';
    case INVALID_PHONE = 'invalid_phone';
    case MISSING_NAME = 'missing_name';
    case INVALID_PAYMENT_METHOD = 'invalid_payment_method';
    case UNAUTHORIZED = 'unauthorized';
    case NETWORK = 'network';
    case VALIDATION = 'validation';
    case SERVER_ERROR = 'server_error';
    case BELOW_MIN_ORDER = 'below_min_order';

    public function userMessage(): string
    {
        return match ($this) {
            self::EMPTY_CART => 'Корзина пуста. Добавьте товары для оформления заказа.',
            self::NO_VENDOR => 'Ресторан не выбран. Вернитесь в меню и выберите ресторан.',
            self::MULTI_VENDOR => 'В корзине товары из разных ресторанов. Выберите один ресторан.',
            self::INVALID_VENDOR => 'Выбранный ресторан недоступен.',
            self::VENDOR_NOT_FOUND => 'Ресторан не найден.',
            self::INVALID_ITEMS => 'Состав заказа некорректен.',
            self::ITEM_NOT_FOUND => 'Некоторые товары не найдены.',
            self::ITEM_UNAVAILABLE => 'Некоторые товары временно недоступны.',
            self::INVALID_PRICE => 'Указана неверная цена товара.',
            self::INVALID_TOTAL => 'Неверно рассчитана общая сумма заказа.',
            self::MISSING_ADDRESS => 'Не указан адрес доставки.',
            self::INVALID_COORDINATES => 'Не удалось определить координаты адреса.',
            self::OUTSIDE_DELIVERY_ZONE => 'Адрес находится за пределами зоны доставки.',
            self::INVALID_PHONE => 'Укажите корректный номер телефона.',
            self::MISSING_NAME => 'Укажите ваше имя.',
            self::INVALID_PAYMENT_METHOD => 'Выберите способ оплаты.',
            self::UNAUTHORIZED => 'Войдите в профиль для оформления заказа.',
            self::NETWORK => 'Проблема с подключением. Проверьте интернет и повторите.',
            self::VALIDATION => 'Проверьте правильность заполнения полей.',
            self::SERVER_ERROR => 'Произошла ошибка на сервере. Попробуйте позже.',
            self::BELOW_MIN_ORDER => 'Сумма заказа ниже минимальной.',
        };
    }

    public function httpStatus(): int
    {
        return match ($this) {
            self::EMPTY_CART,
            self::NO_VENDOR,
            self::MULTI_VENDOR,
            self::INVALID_VENDOR,
            self::VENDOR_NOT_FOUND,
            self::INVALID_ITEMS,
            self::ITEM_NOT_FOUND,
            self::ITEM_UNAVAILABLE,
            self::INVALID_PRICE,
            self::INVALID_TOTAL,
            self::MISSING_ADDRESS,
            self::INVALID_COORDINATES,
            self::OUTSIDE_DELIVERY_ZONE,
            self::INVALID_PHONE,
            self::MISSING_NAME,
            self::INVALID_PAYMENT_METHOD,
            self::BELOW_MIN_ORDER => 422,

            self::UNAUTHORIZED => 401,
            self::VALIDATION => 400,
            self::NETWORK => 0,
            self::SERVER_ERROR => 500,
        };
    }
}
