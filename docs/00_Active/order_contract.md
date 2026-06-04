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
      "modifiers": "array, optional",
      "image": "string, optional"
    }
  ],
  "total": "integer, required (в центах)",
  "delivery_fee": "integer, required (в центах)",
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
| `invalid_coordinates`      | 422  | Не удалось определить координаты адреса. |
| `validation`               | 400  | Ошибки валидации (неверный телефон, имя и т.д.). Детали в `errors`. |
| `unauthorized`             | 401  | Пользователь не авторизован. |
| `network`                  | 0    | Оффлайн или проблема с сетью (обрабатывается на UI). |
| `server_error`             | 500  | Внутренняя ошибка сервера. |

*(Полный список см. в `app/Enums/OrderRejectReason.php`)*
