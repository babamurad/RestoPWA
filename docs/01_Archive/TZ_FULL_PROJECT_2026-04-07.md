# Техническое задание (ТЗ) на весь проект RestoPWA

**Версия:** 1.2  
**Дата:** 7 апреля 2026  
**Роль документа:** единый источник правды (Source of Truth) для разработки, тестирования и релизов.

> **История версий:**
> - v1.0 — 7 апр 2026, первоначальное ТЗ
> - v1.1 — 7 апр 2026, аудит безопасности и структуры
> - v1.2 — 7 апр 2026, актуализация по реальному состоянию кода (vendor auth ✅, tracking auth ✅, Filament admin ✅)

---

## 1) Цель проекта

Разработать production-ready платформу онлайн-заказа еды (PWA) с мультитенантностью, офлайн-корзиной, безопасным checkout-процессом, трекингом заказа и инструментами для ресторанов-партнёров.

**Ключевой результат:** пользователь может быстро заказать еду с мобильного устройства даже при нестабильной сети, а ресторан получает управляемый и безопасный поток заказов.

---

## 2) Scope (границы)

## Входит в scope
1. Клиентский PWA: каталог, карточка ресторана, корзина, checkout, успех заказа, трекинг, профиль, история заказов.
2. Backend API v1: меню, рестораны, категории, order create, cart sync, orders history/detail.
3. Offline-first слой: локальная корзина, очередь заказов, retry/restore.
4. Multitenancy: изоляция данных по vendor.
5. Безопасность: auth, контроль доступа к заказам, защита tenant-контекста.
6. Vendor-контур: управление заказами, статусами, товарами, настройками.
7. **Filament Admin Panel**: управление ресторанами, категориями, продуктами и заказами через веб-интерфейс.
8. Набор quality gate проверок и CI-пайплайн.

## Временно вне scope (до завершения P0/P1)
- Полноценные платежные интеграции (Stripe/Tinkoff/Yandex Pay) в прод-режиме.
- Полный marketplace discovery/recommendation engine.
- Сложная аналитика и BI.

---

## 3) Роли пользователей

1. **Гость/Клиент**
   - просмотр ресторанов и меню,
   - добавление в корзину,
   - оформление заказа,
   - просмотр статуса заказа,
   - история заказов (для авторизованных).

2. **Партнёр (Vendor Staff)**
   - вход в vendor-зону,
   - управление заказами (kanban/status),
   - управление товарами,
   - настройки ресторана.

3. **Администратор**
   - полный доступ к Filament Admin Panel (`/admin/*`),
   - управление ресторанами, категориями, продуктами, заказами,
   - управление пользователями.

4. **Система/Фоновые процессы**
   - синхронизация корзины,
   - повторная отправка pending orders,
   - кеширование и offline fallback.

---

## 4) Функциональные требования

## FR-1 Каталог и карточки ресторанов
- Показ активных ресторанов.
- Переход в конкретный ресторан по slug.
- Загрузка категорий и товаров через API v1.
- Пагинация/ленивая загрузка (по необходимости).

**Критерии приёмки:**
- список загружается < 2 сек на 4G,
- ошибки API отображаются user-friendly,
- пустые состояния оформлены.

**Статус:** ✅ Реализовано (`RestaurantController`, `MenuController`, `api/v1/*`)

## FR-2 Корзина и checkout
- Добавление/удаление/изменение количества товара.
- Корректный подсчёт total quantity/price.
- Перед отправкой заказа выполняется `cart/sync`.
- При конфликте цены/наличия — отображение расхождения и подтверждение пользователя.

**Критерии приёмки:**
- цена заказа на клиенте синхронизируется с серверной до final submit,
- в случае расхождений заказ без подтверждения не создаётся.

**Статус:** ✅ Реализовано (Dexie CartService + `CartController::sync`)

## FR-3 Офлайн-режим
- Локальное хранение корзины (IndexedDB/Dexie).
- Очередь `pendingOrders` при отсутствии сети.
- Retry отправки при восстановлении соединения.
- Offline UI индикатор.

**Критерии приёмки:**
- при offline пользователь не теряет корзину,
- pending заказ отправляется автоматически после online,
- дубликаты заказов исключены (идемпотентность).

**Статус:** ✅ Реализовано (Dexie v4, SW с cache-versioning v2, `pendingOrders` queue)

## FR-4 Заказы и трекинг
- Экран success после создания заказа.
- Трекинг статуса (polling, позже realtime).
- История заказов пользователя.

**Критерии приёмки:**
- доступ к заказу строго по owner/session policy,
- смена статусов отражается на клиенте без ручного обновления (минимум polling).

**Статус:** ✅ Частично реализовано
- `/order/success/{id}` — owner-проверка ✅
- `/order/{orderId}/track` — защищён middleware `auth` + ownership check ✅
- `/api/order/{orderId}/track` — защищён middleware `auth` ✅
- Realtime (P2) — в roadmap

## FR-5 Vendor-панель
- Просмотр заказов и канбан по статусам.
- Изменение статуса заказа (accept/cancel/update-status).
- Управление товарами.
- Настройки заведения.

**Критерии приёмки:**
- vendor не видит данные другого vendor,
- изменение статусов логируется в истории.

**Статус:** ✅ Реализовано (middleware `ensure.tenant` + `auth` на `/vendor/*`)

## FR-6 Filament Admin Panel *(добавлено в v1.2)*
- CRUD управление ресторанами (`RestaurantResource`).
- CRUD управление категориями (`CategoryResource`).
- CRUD управление продуктами (`ProductResource`).
- Просмотр и управление заказами (`OrderResource`).
- Filament v5 + Livewire v4 + Laravel 13.

**Критерии приёмки:**
- доступ только для пользователей, реализующих `FilamentUser` (`canAccessPanel`),
- все ресурсы работают корректно без ошибок namespace,
- tenant-изоляция в admin CRUD при необходимости.

**Статус:** ✅ Реализовано (4 ресурса + `AdminPanelProvider` с `FilamentUser`)

---

## 5) Нефункциональные требования

## NFR-1 Производительность
- LCP mobile <= 2.5s на ключевых экранах.
- API p95 <= 400ms для read endpoints (без внешних интеграций).

## NFR-2 Надёжность
- SLO доступности API: 99.5%+.
- Не более 1% failed checkout от всех checkout attempts (исключая внешние сбои платежей).

## NFR-3 Безопасность
- Все чувствительные маршруты с auth/authorization.
- Tenant context обязателен для vendor/API операций.
- Защита от ID enumeration на order-tracking.

## NFR-4 Поддерживаемость
- Единый кодстайл (Pint + frontend lint/test).
- Минимальный test coverage для критических модулей:
  - backend core flow >= 70%,
  - frontend service layer >= 80%.

## NFR-5 Стек технологий *(добавлено в v1.2)*
- **PHP:** ^8.3
- **Laravel:** ^13.0
- **Filament:** ^5.0
- **Livewire:** ^4.0
- **Vite:** ^8.0 + TailwindCSS ^4.0
- **Dexie:** ^4.4 (IndexedDB)
- **Vitest:** ^3.0 (frontend tests)
- **PHPUnit:** ^12.5

---

## 6) Архитектурные требования

1. **DDD-ориентация** сохраняется (доменные модели и сервисы в `app/Domains/*`).
   - Existing domains: `Menu`, `Order`, `Vendor`, `User`, `Geo`, `Payment`
2. **Тонкие контроллеры**: бизнес-правила в сервисах/доменных слоях.
3. **Tenant isolation**: middleware + scopes + тесты обязательны.
4. **API-first контракт**: все UI сценарии опираются на стабильные API-контракты.
5. **Backward compatibility**: изменения API v1 через версионирование/деградацию.
6. **Filament Admin**: использует Filament v5 API (`Filament\Schemas\Schema`, `Filament\Actions\*`) — не смешивать с Filament v2/v3 namespace.

---

## 7) Security спецификация (обязательно)

## SEC-1: Доступ к `/order/{id}/track`
Реализован один из вариантов:
- owner-only для авторизованных пользователей через middleware `auth` + ownership check в контроллере ✅

**Статус:** ✅ ЗАКРЫТО (web.php строка 79-80: `->middleware('auth')`)

**Дополнительно (backlog):**
- Рассмотреть signed URL с ограниченным TTL для guest checkout (P1).

**Приёмка:** попытка доступа к чужому заказу всегда возвращает 403/404 без утечки данных.

## SEC-2: Vendor зона `/vendor/*`
- Обязательные middleware: `ensure.tenant` + `auth` ✅

**Статус:** ✅ ЗАКРЫТО (web.php строка 92: `->middleware(['ensure.tenant', 'auth'])`)

**Приёмка:** feature-тесты на все защищённые маршруты.

## SEC-3: Tenant enforcement для API
- Для `api/v1/*` обязателен tenant-context через `SetTenantContext` ✅
- Невалидный/отсутствующий tenant => 400.

**Статус:** ✅ ЗАКРЫТО (api.php строка 13: `SetTenantContext::class`)

**Приёмка:** тесты позитив/негатив по заголовкам/поддомену.

## SEC-4: Filament Admin доступ *(добавлено в v1.2)*
- Только `User` с `canAccessPanel(): true` имеет доступ к `/admin`.
- `User` модель реализует интерфейс `FilamentUser`.

**Статус:** ✅ ЗАКРЫТО

---

## 8) Data/API контракт (минимальный)

1. `POST /api/v1/cart/sync`
   - вход: items/modifiers/qty/vendor,
   - выход: валидированные позиции, фактические цены, unavailable items.

2. `POST /api/v1/orders`
   - вход: validated cart snapshot + адрес/контакты,
   - выход: order id/status/summary.

3. `GET /api/v1/orders`, `GET /api/v1/orders/{id}`
   - только `auth:sanctum`.

4. `GET /api/order/{id}/track`
   - только `auth` + owner policy.

5. `GET /api/v1/menu/{vendor}` — публичный (tenant-scoped)
6. `GET /api/v1/restaurants` — публичный список активных
7. `GET /api/v1/categories` — публичный список категорий
8. `POST /api/push/subscribe`, `POST /api/push/unsubscribe` — `auth:sanctum` *(добавлено в v1.2)*

---

## 9) План реализации (этапы)

## Этап P0 (Security + Operating Baseline) — **ЗАВЕРШЁН** ✅

### Выполнено
1. ✅ Защита tracking endpoints: `auth` middleware + ownership check.
2. ✅ Vendor-route policy приведена к `ensure.tenant + auth`.
3. ✅ Tenant enforcement для API: `SetTenantContext` middleware.
4. ✅ Filament Admin Panel: 4 ресурса (`Restaurant`, `Category`, `Product`, `Order`).
5. ✅ Quality gate инфраструктура:
   - `composer install` — работает (PHP 8.3 + intl extension)
   - `./vendor/bin/pint --test` — настроен
   - `php artisan test` — настроен
   - `npm run test` (Vitest) — настроен
   - `npm run build` — работает

### Deliverables ✅
- Security фиксация и изоляция tenant выполнены,
- docs/tenant-audit.md актуализирован,
- Filament Admin Panel введён в эксплуатацию.

## Этап P1 (Core Product Hardening) — 2–3 недели

### Задачи
1. Улучшить checkout conflict resolution UX.
2. Идемпотентность order submit + защита от дублей pending queue.
3. Нормализовать ошибки API и единый формат ответов.
4. E2E smoke тест: каталог -> корзина -> checkout -> success -> tracking.
5. Signed URL для guest order tracking (альтернатива/дополнение к auth).
6. Feature-тесты на protected routes (tracking, vendor, admin).
7. Расширить Filament Admin: bulk actions, фильтры, экспорт.

### Deliverables
- стабильный пользовательский сценарий без ручных обходов,
- расширенное покрытие критического пути.

## Этап P2 (Q2 Features) — 3–6 недель

### E1 Real-time tracking
- WebSocket слой (Reverb/Pusher),
- UI прогресс статусов,
- fallback на polling.

### E2 Push notifications
- VAPID lifecycle *(инфраструктура уже есть: `PushController`, push routes)*,
- opt-in UI,
- push payload handling в SW.

### F1/F2 Payments & Discovery
- интеграция платежей *(домен `Payment` уже создан)*,
- купоны/лояльность,
- фильтрация/рейтинги/отзывы.

---

## 10) Тестовая стратегия

## Backend (PHPUnit ^12.5)
- Unit: сервисы домена, tenant контекст, value-objects/casts.
- Feature: auth, tenant policy, order lifecycle, forbidden access.
- Contract tests: JSON структуры ключевых API.

**Существующие тесты:**
- `BelongsToVendorScopeTest.php` ✅
- `OrderLifecycleTest.php` ✅
- `OrderStatusHistoryTest.php` ✅
- `ProductScopeTest.php` ✅
- `SetTenantContextMiddlewareTest.php` ✅
- `tests/Feature/Api/` ✅

## Frontend (Vitest ^3.0)
- Unit: CartService/offline queue/totals/sync conflicts.
- Integration: checkout orchestration with mock API.
- E2E smoke: happy-path на staging.

## Manual QA чеклист
- offline/online переход во время checkout,
- доступ к чужому заказу,
- доступ в vendor без auth,
- изменение цены между add-to-cart и checkout,
- **доступ к Filament Admin без авторизации** *(добавлено в v1.2)*.

---

## 11) Definition of Ready / Definition of Done

## DoR (перед началом задачи)
- описан API контракт/изменение,
- определены тест-кейсы,
- определены security impacts,
- оценены трудозатраты.

## DoD (для закрытия)
- код покрыт тестами,
- quality gate зелёный,
- changelog обновлён,
- документация актуализирована,
- есть демо/скринкаст для UI-изменений.

---

## 12) План работ по спринтам (обновлено в v1.2)

### Sprint 1 — **ЗАВЕРШЁН** ✅
- SEC-1 tracking hardening ✅
- SEC-2 vendor auth policy ✅
- Filament Admin Panel ✅

### Sprint 2 (текущий) — **ЗАВЕРШЁН ✅**
- quality gate stabilization (php artisan test — полный прогон),
- cart sync conflict UX,
- idempotent submit improvements,
- feature tests for protected routes.

### Sprint 3
- E2E smoke + bugfix wave,
- performance baseline refresh,
- signed URL для guest tracking (опционально).

### Sprint 4
- E1 realtime backend skeleton + frontend indicators.

### Sprint 5
- E2 push infra + opt-in UI.

### Sprint 6
- payments/discovery MVP slice.

---

## 13) Риски и меры

1. **Сетевые ограничения окружения** (доступ к пакетным registry)
   - мера: зеркала, кэш зависимостей, внутренний registry proxy.
   - *актуальный опыт: OSPanel + PHP 8.3, требуется `extension=intl` для Filament.*
2. **Дрейф требований/документов**
   - мера: запрет разработки без ссылки на пункт текущего ТЗ.
3. **Сложность multitenancy**
   - мера: обязательные тесты изоляции на каждое изменение роутов/моделей.
4. **Offline сложность и дубли заказов**
   - мера: idempotency key + журнал операций queue.
5. **Filament v5 namespace breaking changes** *(добавлено в v1.2)*
   - мера: использовать только `Filament\Actions\*` и `Filament\Schemas\*`, не смешивать с v2/v3 namespace.

---

## 14) Правила исполнения (чтобы «спокойно работать и не отвлекаться на архитектуру»)

1. Любая задача берётся только из раздела этапов этого ТЗ.
2. Любой PR обязан:
   - ссылаться на конкретный пункт ТЗ,
   - содержать тесты/проверки,
   - обновлять changelog при пользовательских изменениях.
3. Никаких «параллельных планов» вне этого документа.
4. Раз в неделю: короткий аудит статуса `Done / In Progress / Blocked / Next`.
5. **Filament разработка**: при добавлении новых ресурсов или action — проверять namespace совместимость с v5.

---

## 15) Артефакты, которые вести постоянно

- `docs/TZ_FULL_PROJECT_2026-04-07.md` — основной план (этот документ).
- `docs/CHANGELOG.md` — факт изменений по релизам.
- `docs/DoD.md` — чеклист качества (с актуальными отметками).
- `docs/tenant-audit.md` — матрица изоляции и security.
- `docs/AUDIT_2026-04-07.md` — snapshot аудита на дату.

---

## 16) Текущий статус (snapshot на 7 апреля 2026)

| Блок | Оценка | Статус |
|------|--------|--------|
| Product/Web Flow | B | ✅ Основной контур рабочий |
| API & Tenant Isolation | B | ✅ `SetTenantContext` на всех v1 route |
| Offline-first (PWA) | B+ | ✅ Dexie + SW + pendingOrders queue |
| Security & Access Control | **B+** *(было C)* | ✅ auth на tracking и vendor закрыты, API ошибки унифицированы |
| Filament Admin Panel | B | ✅ 4 ресурса работают (Filament v5) |
| Документация | B | ✅ Единый ТЗ + changelog + tenant-audit |
| Test Coverage | B | ✅ Есть feature-тесты на protected routes, cart sync, idempotency |
| Quality Gate | B | ✅ Тесты работают (php artisan test, pint), базовый пайплайн |

**Следующий приоритет:** Sprint 3 — E2E smoke + bugfix wave.

---

## 17) Итог

Это ТЗ фиксирует полный маршрут от текущего состояния к production-качеству и дальнейшему развитию.  
Дальше вам не нужно «думать архитектуру каждый день» — достаточно брать следующий пункт этапа, выполнять критерии приёмки и закрывать спринты последовательно.

**P0 и Sprint 2 закрыты. Следующий шаг — Sprint 3.**
