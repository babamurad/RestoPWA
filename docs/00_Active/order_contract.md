# Единый контракт заказа (Order Contract)

Этот документ фиксирует единый контракт данных между Frontend и Backend для процесса оформления заказа (Checkout).
Контракт является "single source of truth" (единым источником истины) и любые изменения в нём должны быть обратно совместимы или версионированы.

## 1. DTO (Data Transfer Object) оформления заказа

### Payload (Тело запроса на POST /api/v1/orders)

```json
{
  "vendor_id": "string, required",
  "items": [
    {
      "product_id": "string, required",
      "product_name": "string, required",
      "quantity": "integer, required, min:1",
      "unit_price": "integer, required (в центах)",
      "total_price": "integer, required (в центах)",
      "modifiers": "array, optional: выбранные добавки [{option_id, quantity?}] (см. «Добавки» ниже)",
      "image": "string, optional"
    }
  ],
  "total": "integer, required (в центах)",
  "delivery_fee": "integer, required (в центах; сервер всегда пересчитывает)",
  "promo_code": "string, optional (скидку сервер считает сам; присланная `discount_amount` игнорируется)",
  "delivery_type": "string, optional (delivery | pickup; по умолчанию delivery)",
  "address": {
    "lat": "numeric, required",
    "lon": "numeric, required",
    "address": "string, optional",
    "house": "string, optional",
    "apartment": "string, optional",
    "entrance": "string, optional",
    "floor": "string, optional",
    "manual_address": "string, optional",
    "landmark": "string, optional",
    "courier_comment": "string, optional",
    "address_source": "string, optional",
    "geolocate_attempted": "boolean, optional",
    "geolocate_status": "string, optional",
    "geolocate_accuracy_m": "numeric, optional"
  },
  "customer_name": "string, required",
  "customer_phone": "string, required",
  "payment_method": "string, required (cash | card | sbp)",
  "delivery_time": "string, optional (asap)",
  "comment": "string, optional",
  "trace_id": "string, required, uuid (для логов)",
  "idempotency_key": "string, required, uuid (для оффлайн синхронизации)"
}
```

### Правила маппинга
- **Цены**: Поля `unit_price`, `total_price` (на уровне товара), `delivery_fee` и `total` передаются в **целочисленном формате (в центах / минимальных единицах)**.
- **Деньги в коде**: API заказа и база данных считают в минимальных единицах (тенге, 1/100 маната), приложение — в манатах. Денежные поля моделей (`Order::total`, `delivery_fee`, `commission_amount`, `Product::price`, `Restaurant::min_order` и др.) читаются и записываются **только в манатах**, в любом числовом виде: `20`, `20.0` и `"20"` означают 20 манатов. Сумму в минимальных единицах перед записью в модель переводят через `MoneyCast::fromMinor()`, обратно — `MoneyCast::toMinor()`.
- **Доставка**: `delivery_fee` и `total` от клиента сервер не принимает на веру и пересчитывает: `delivery_fee` = цена ресторана для района, в который попадает `address.lat/lon` (`restaurant_delivery_prices`; район не оценён рестораном или точка вне районов → `outside_delivery_zone`). Если у ресторана нет цен по районам — плоский `Restaurant.delivery_fee`. Для `delivery_type = pickup` — 0. Вся `delivery_fee` начисляется курьеру (`courier_earnings`); `total` = сумма `total_price` + `delivery_fee`.
- **Самовывоз**: `delivery_type = pickup` сохраняется на заказе (`orders.delivery_type`). Для самовывоза не проверяются адрес и зона доставки; такие заказы не показываются курьерам и не могут быть приняты или назначены курьеру (422). `address` по-прежнему обязателен по схеме — PWA отправляет координаты-заглушку.
- **Trace ID**: Генерируется на Frontend в момент начала оформления заказа и передается в `trace_id` body и заголовке `X-Trace-Id` во всех API-запросах для сквозного логирования.
- **Idempotency Key**: Уникальный ключ транзакции. Передается в `idempotency_key` body и заголовке `X-Idempotency-Key`. Если Frontend отправляет запрос с существующим ключом, Backend возвращает успешный ответ (если заказ уже был создан) без дублирования.

## 2. Структура ответа (Response)

### Успешный ответ (201 Created)
```json
{
  "success": true,
  "order_id": "integer",
  "status": "string (pending)",
  "total_price": "numeric"
}
```

### Ошибка валидации или бизнес-логики (400, 422)
```json
{
  "success": false,
  "reject_reason": "string (enum)",
  "message": "string (человекочитаемое сообщение)",
  "errors": {
    "field_name": ["массив детальных ошибок"]
  }
}
```

## 3. Enum причин ошибок (OrderRejectReason)

Код ошибки в поле `reject_reason` берется из единого `app/Enums/OrderRejectReason.php`. Фронтенд должен полагаться именно на этот Enum для отображения корректного UI.

| Код ошибки (reject_reason) | HTTP | Сценарий / Описание |
|----------------------------|------|-----------------------|
| `empty_cart`               | 422  | Корзина пуста. Добавьте товары. |
| `multi_vendor`             | 422  | В корзине товары из разных ресторанов. Выберите один. |
| `outside_delivery_zone`    | 422  | Адрес находится за пределами зоны доставки. |
| `invalid_total`            | 422  | Неверно рассчитана общая сумма заказа. |
| `invalid_promo`            | 422  | Промокод не подошёл; в `details` — `promo_reason` и `promo_message`. |
| `invalid_coordinates`      | 422  | Не удалось определить координаты адреса. |
| `validation`               | 400  | Ошибки валидации (неверный телефон, имя и т.д.). Детали в `errors`. |
| `unauthorized`             | 401  | Пользователь не авторизован. |
| `network`                  | 0    | Оффлайн или проблема с сетью (обрабатывается на UI). |
| `server_error`             | 500  | Внутренняя ошибка сервера. |

*(Полный список см. в `app/Enums/OrderRejectReason.php`)*

## Добавки (модификаторы блюд)

У блюда могут быть группы добавок. Группа: `id`, `name`, `type` (`radio` — один вариант, `checkbox` — несколько, `counter` — несколько порций каждого), `required`, `min`, `max` (для `radio` всегда 1; для `counter` считаются порции) и `options` (`id`, `name`, `price` в манатах, `is_default`). Меню (`GET /api/v1/menu/{vendor}`, `GET /api/v1/menu/product/{id}`) отдаёт группы уже с идентификаторами и правилами.

Клиент присылает в `items[].modifiers` только выбор: `[{ "option_id": "o1a2b3c4d5", "quantity": 1 }]` (`quantity` нужен только для `counter`). Названия и цены, которые прислал клиент, игнорируются: цену и название сервер берёт из блюда. Неизвестные варианты отбрасываются; старая форма `{id, name}` пока принимается.

Нарушение правил группы (не выбрана обязательная, выбрано больше `max`) отклоняет заказ с причиной `invalid_modifiers` (422). Тот же выбор принимает `POST /api/v1/cart/sync`; строки корзины там различаются полем `key`, которое сервер возвращает обратно, а строка, чей выбор больше не подходит блюду, попадает в `unavailable_items`.

В сохранённом заказе `items[].modifiers` — выбранные варианты с названием группы и варианта, ценой и количеством.

## Промокоды

Промокод (`promo_code` в заказе) даёт скидку **только на стоимость блюд**, не на доставку. Сервер считает её сам (процент с потолком `max_discount` либо фиксированная сумма, не больше стоимости блюд), `total` = блюда − скидка + `delivery_fee`. Скидка и снимок кода хранятся в заказе (`discount_amount`, `promo_code`, `promo_code_id`), клиентский API заказов отдаёт `discount_amount` и `promo_code`.

- `POST /api/v1/cart/apply-promo` (`code`, `vendor_id`, `items[]` с `product_id`, `quantity`, `modifiers`; 10 запросов в минуту с адреса) проверяет код на корзине, ничего не списывая. Ответ 200: `data.code`, `data.discount`, `data.subtotal` в минимальных единицах. Отказ 422: `reason` (`not_found`, `inactive`, `not_started`, `expired`, `wrong_restaurant`, `min_order`, `usage_limit`, `user_limit`, `items_changed`) и `message`.
- Использование кода занимается при оформлении заказа в той же транзакции, что и сам заказ, с блокировкой строки кода: двое клиентов не возьмут последнее использование. Отмена заказа возвращает использование.
- Минимальная сумма заказа ресторана (`min_order`) проверяется по стоимости блюд **до** скидки.

