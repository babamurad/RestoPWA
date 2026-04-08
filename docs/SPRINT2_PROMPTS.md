# RestoPWA — Sprint 2: AI Prompt Pack

**Привязка к ТЗ:** `docs/TZ_FULL_PROJECT_2026-04-07.md`, Этап P1  
**Принцип:** каждый промт — одна атомарная задача. Давать AI по одному промту, дожидаться выполнения, проверять, затем следующий.

---

## Порядок выполнения

```
S2-1 → S2-2 → S2-3 → S2-4 → S2-5 → S2-6
```

Каждый промт содержит:
- **Контекст** — что уже есть в проекте
- **Задача** — что сделать
- **Файлы** — какие файлы создать/изменить
- **Критерий готовности** — как проверить

---

## S2-1: Quality Gate — полный прогон тестов

> **Давай этот промт первым.** Нужно убедиться, что всё работает перед началом изменений.

```
Контекст проекта RestoPWA:
- Laravel 13 + PHP 8.3 + Filament 5 + Livewire 4
- Тесты: PHPUnit 12.5 (backend), Vitest 3 (frontend)
- Команды: `php artisan test`, `./vendor/bin/pint --test`, `npm run test`

Задача:
1. Запусти `php artisan test` и покажи весь вывод — сколько тестов прошло, сколько упало.
2. Если тесты падают — исправь найденные ошибки в тестах (только тесты, не production код).
3. Запусти `./vendor/bin/pint --test` — если есть нарушения стиля, примени `./vendor/bin/pint` и покажи изменённые файлы.
4. Запусти `npm run test` (Vitest) — покажи результат.

Файлы для проверки: `tests/Feature/*.php`, `tests/Unit/*.php`, `tests/Feature/Api/*.php`

Критерий готовности: все три команды завершаются без ошибок (или ошибки описаны и задокументированы).
```

---

## S2-2: Feature-тесты для защищённых маршрутов

> **Привязка к ТЗ:** SEC-1, SEC-2, SEC-3 — нужны тесты, подтверждающие что policy работает.

```
Контекст проекта RestoPWA (Laravel 13, PHPUnit 12.5):

Уже существуют тесты в:
- tests/Feature/BelongsToVendorScopeTest.php
- tests/Feature/OrderLifecycleTest.php
- tests/Feature/SetTenantContextMiddlewareTest.php

Маршруты, которые нужно протестировать (из routes/web.php):
- GET /order/{orderId}/track — middleware 'auth', ownership check в OrderTrackingController
- GET /api/order/{orderId}/track — middleware 'auth'
- GET /vendor/orders — middleware ['ensure.tenant', 'auth']
- GET /vendor/products — middleware ['ensure.tenant', 'auth']
- GET /admin (Filament) — только FilamentUser

Задача: Создай файл tests/Feature/AccessControlTest.php со следующими тест-кейсами:

1. test_guest_cannot_access_order_tracking() — неавторизованный пользователь получает redirect на /login
2. test_authenticated_user_cannot_access_another_users_order() — авторизованный пользователь получает 403/404 при попытке открыть чужой заказ
3. test_authenticated_user_can_access_own_order_tracking() — владелец заказа успешно открывает трекинг
4. test_guest_cannot_access_vendor_panel() — неавторизованный получает redirect при доступе к /vendor/orders
5. test_vendor_cannot_see_other_vendors_orders() — vendor видит только свои заказы

Для каждого теста используй фабрики моделей (User, Order, Restaurant).
Используй RefreshDatabase trait.
Используй PHP 8.3 синтаксис.

Критерий готовности: все 5 тестов проходят `php artisan test --filter=AccessControlTest`
```

---

## S2-3: Idempotency Key для order submit

> **Привязка к ТЗ:** P1 п.2 — «Идемпотентность order submit + защита от дублей pending queue»

```
Контекст проекта RestoPWA:

Файл: app/Domains/Order/Http/Controllers/Api/OrderController.php
Маршрут: POST /api/v1/orders (routes/api.php, строка 48)
Таблица orders: уже существует, имеет поля id (uuid), vendor_id, user_id, status, items (jsonb), total

Проблема: при нестабильной сети клиент может повторно отправить один и тот же заказ, создав дубликат.

Задача:

1. Создай миграцию: добавь столбец `idempotency_key` (string, nullable, unique) в таблицу `orders`.
   Файл: database/migrations/YYYY_MM_DD_add_idempotency_key_to_orders_table.php

2. В OrderController::store() добавь логику:
   - Считывай заголовок `X-Idempotency-Key` из запроса
   - Если ключ передан: проверь, нет ли уже заказа с таким ключом для текущего user_id
   - Если заказ существует: верни 200 с существующим order (не создавай новый)
   - Если ключа нет: создавай заказ в обычном режиме (без idempotency check)
   - Сохраняй idempotency_key при создании заказа

3. Обнови модель app/Domains/Order/Models/Order.php:
   - Добавь 'idempotency_key' в $fillable

4. Обнови клиентскую часть resources/js/services/CartService.js:
   - В методе queueOrder() или при отправке заказа генерируй UUID v4 как idempotency_key
   - Сохраняй его в pendingOrders вместе с payload
   - При retry отправки используй тот же ключ (не генерируй новый)

Критерий готовности:
- Миграция выполняется без ошибок
- Повторный POST с тем же X-Idempotency-Key возвращает 200 с тем же order_id (не 201 с новым)
- test: создай тест tests/Feature/OrderIdempotencyTest.php с проверкой дублей
```

---

## S2-4: Нормализация API ошибок (единый формат ответов)

> **Привязка к ТЗ:** P1 п.3 — «Нормализовать ошибки API и единый формат ответов»

```
Контекст проекта RestoPWA (Laravel 13):

Текущая проблема: разные API endpoints возвращают ошибки в разном формате:
- некоторые: {"error": "..."}
- некоторые: {"message": "..."}
- некоторые: стандартный Laravel validation response

Задача: Создай единый формат ошибок для всех API маршрутов (/api/v1/*).

1. Создай трейт app/Http/Traits/ApiResponses.php со методами:
   - success($data, $message = null, $code = 200): JsonResponse
   - error($message, $code = 400, $errors = []): JsonResponse
   
   Формат success: {"success": true, "data": {...}, "message": null}
   Формат error: {"success": false, "message": "...", "errors": {...}, "code": 400}

2. В app/Exceptions/Handler.php (или bootstrap/app.php для Laravel 13) добавь обработку:
   - ValidationException → 422 с формат error + errors из $e->errors()
   - ModelNotFoundException → 404 с "Ресурс не найден"
   - AuthenticationException для API routes → 401 JSON (не redirect)
   - Все прочие Exception для API routes → 500 JSON (без стека в production)

3. Обнови следующие контроллеры, чтобы использовали ApiResponses трейт:
   - app/Http/Controllers/Api/CartController.php
   - app/Domains/Order/Http/Controllers/Api/OrderController.php
   - app/Http/Controllers/Api/OrderController.php

4. Убедись что /api/ping возвращает: {"success": true, "data": {"status": "ok"}}

Критерий готовности:
- GET /api/v1/menu/nonexistent → {"success": false, "message": "Ресурс не найден", "code": 404}
- POST /api/v1/cart/sync без заголовка X-Vendor-ID → {"success": false, "message": "...", "code": 400}
- Все API endpoints возвращают consistent JSON структуру
```

---

## S2-5: Checkout Conflict Resolution UX

> **Привязка к ТЗ:** P1 п.1 — «Улучшить checkout conflict resolution UX»

```
Контекст проекта RestoPWA:

Уже существует:
- POST /api/v1/cart/sync в app/Http/Controllers/Api/CartController.php
- Livewire компонент checkout-wizard в resources/views/livewire/order/checkout-wizard.blade.php
- CartService.js с методом syncWithServer()

Текущая проблема: если cart/sync обнаруживает расхождение цен или недоступные товары,
пользователю не показывается понятное объяснение — что именно изменилось.

Задача:

1. Убедись что CartController::sync() возвращает структуру:
   {
     "success": true,
     "data": {
       "validated_items": [...],    // товары с актуальными ценами
       "price_changes": [...],      // [{product_id, old_price, new_price, name}]
       "unavailable_items": [...],  // [{product_id, name, reason}]
       "total": 1500.00
     }
   }

2. В checkout-wizard.blade.php (или соответствующем Livewire компоненте) добавь:
   - Шаг "Проверка корзины" перед шагом подтверждения
   - Если price_changes не пустой: отобразить предупреждение с таблицей изменений цен
   - Если unavailable_items не пустой: отобразить список недоступных товаров
   - Кнопки: "Принять изменения и продолжить" / "Вернуться в корзину"
   - Без подтверждения пользователя кнопка "Оформить заказ" заблокирована (disabled)

3. Добавь Alpine.js переменную `conflictsConfirmed = false` которая снимает блокировку после нажатия "Принять".

4. В CartService.js метод syncWithServer() должен возвращать данные о конфликтах для использования в UI.

Критерий готовности:
- Если изменить цену товара в БД и открыть checkout → пользователь видит предупреждение
- Без нажатия "Принять изменения" кнопка "Оформить" неактивна
- После принятия → заказ создаётся с актуальными ценами сервера
```

---

## S2-6: Обновление CHANGELOG и DoD

> **Давай последним.** Документальная фиксация выполненного спринта.

```
Контекст: проект RestoPWA, Sprint 2 завершён.

Выполнены следующие задачи:
- S2-1: quality gate прогон (php artisan test, pint, vitest)
- S2-2: AccessControlTest.php с 5 тест-кейсами на защищённые маршруты
- S2-3: idempotency_key для order submit + клиентская часть
- S2-4: единый формат API ошибок через ApiResponses трейт
- S2-5: checkout conflict resolution UX с подтверждением изменений

Задача:

1. Добавь в docs/CHANGELOG.md новую секцию [1.2.0] - 2026-04-07 со списком всех изменений Sprint 2.

2. Обнови docs/DoD.md — проставь [x] для пунктов, которые теперь выполнены:
   - "All data input validated via Request::validate()" — если выполнено
   - "PHP Code Quality: Laravel Pint passes" — если quality gate прошёл
   - "Backend Tests: PHPUnit feature tests cover core happy-path and error-path scenarios"
   - "Manual Verification: Full end-to-end flow tested"

3. Обнови docs/TZ_FULL_PROJECT_2026-04-07.md:
   - В разделе "Sprint 2" добавь пометку "ЗАВЕРШЁН ✅"
   - В таблице "Текущий статус" обнови оценки если улучшились

Критерий готовности: все три файла обновлены, sprint 2 задокументирован.
```

---

## Чеклист Sprint 2

- [ ] S2-1: `php artisan test` — все тесты зелёные
- [ ] S2-1: `./vendor/bin/pint --test` — нет нарушений стиля
- [ ] S2-1: `npm run test` — Vitest зелёный
- [ ] S2-2: `AccessControlTest.php` создан и проходит
- [ ] S2-3: `idempotency_key` миграция + контроллер + клиент
- [ ] S2-4: единый формат API ошибок
- [ ] S2-5: checkout conflict UX с блокировкой submit
- [ ] S2-6: CHANGELOG + DoD + ТЗ обновлены
