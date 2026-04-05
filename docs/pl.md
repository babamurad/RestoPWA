# RestoPWA — статус выполнения и план дальнейших работ

_Актуализировано: 5 апреля 2026_

---

## 1) Что уже сделано

### Архитектура и multitenancy
- DDD-структура в `app/Domains/*` (Menu, Order, Vendor, Geo).
- Реализованы ключевые части multitenancy:
  - `TenantContext`
  - `BelongsToVendorScope`
  - `BelongsToVendor` trait
  - `SetTenantContext` middleware
- Зарегистрирован `DomainServiceProvider`.

### База данных и доменные сущности
- Миграции: `restaurants`, `categories`, `products`, `orders`, `order_status_history`, `push_subscriptions`, PostGIS.
- Составные индексы: `products(vendor_id, is_available)`, `orders(vendor_id, status)`.
- Реализованы доменные модели и сервисы (Menu / Order / Vendor / Geo).

### PWA и offline-first
- `public/sw.js` с offline-логикой и fallback на `/offline`.
- Регистрация service worker в `resources/js/app.js`.
- Offline UI-компоненты: `offline-indicator`, `offline-fallback`, `offline.blade.php`.
- `CartService` на Dexie (`RestoCart`) подключён через Alpine.

### Livewire-компоненты
- `Cart\CartDrawer` + view `cart-drawer.blade.php` ✅
- `Order\CheckoutWizard` + view `checkout-wizard.blade.php` ✅
- `Order\KanbanBoard` + view `kanban-board.blade.php` ✅
- `Geo\AddressSelector` ✅

### Blade-компоненты (layout / UI)
- `header.blade.php`, `bottom-nav.blade.php` ✅
- `restaurant-card.blade.php`, `product-card.blade.php` ✅
- `order-card.blade.php`, `add-to-cart.blade.php`, `cart-button.blade.php` ✅
- `offline-fallback.blade.php`, `offline-indicator.blade.php` ✅

### Страницы (web routes + blade views)
- `GET /` → `home.blade.php` — реальные данные (рестораны, категории) ✅
- `GET /restaurants/{slug}` → `restaurants/show.blade.php` ✅
- `GET /cart` → `cart.blade.php` ✅
- `GET /orders` → `orders/index.blade.php` ✅
- `GET /profile` → `profile/edit.blade.php` ✅
- `GET /order/{id}/track` → `order/tracking.blade.php` ✅
- `GET /offline` → `offline.blade.php` ✅
- `GET /manifest.json` ✅

### API (routes/api.php)
- `GET /api/v1/restaurants` ✅
- `GET /api/v1/restaurants/{slug}` ✅
- `GET /api/v1/menu/{vendor}` ✅
- `GET /api/v1/categories` ✅
- `POST /api/orders` (OrderController) ✅
- Push-подписки (sanctum-auth) ✅

### Вендорская панель
- Products CRUD (`/vendor/products`) ✅
- Orders (список, kanban, статусы, receipt) ✅
- Settings ✅

### Тесты
- Feature: `BelongsToVendorScopeTest`, `OrderStatusHistoryTest`, `ProductScopeTest`, `SetTenantContextMiddlewareTest` ✅
- Unit: `MoneyCastTest`, `TenantContextTest`, `SetTenantContextSubdomainTest` ✅

---

## 2) Что не завершено или требует работы

### 2.1 Критичные функциональные пробелы

| # | Пробел | Приоритет |
|---|--------|-----------|
| 1 | Нет маршрута `GET /order/success/{id}` и view `success.blade.php` | 🔴 Высокий |
| 2 | Нет маршрута `GET /restaurants` (список всех ресторанов) — возвращает строку | 🔴 Высокий |
| 3 | `/orders` и `/profile` работают через `test@example.com` вместо реальной auth | 🔴 Высокий |
| 4 | Нет `POST /api/v1/cart/sync` и `GET /api/v1/orders` (обязательный API-минимум) | 🔴 Высокий |
| 5 | `CartDrawer` не имеет серверной синхронизации (server sync, перерасчёт цен) | 🟡 Средний |
| 6 | Нет e2e offline checkout + повторной синхронизации заказов | 🟡 Средний |

### 2.2 Технический долг

| # | Задача |
|---|--------|
| 1 | Tenant-изоляция не проверена во всех API-эндпоинтах (напр. `POST /api/orders` вне middleware) |
| 2 | SW-кеширование: нет версионирования и стратегии инвалидации при деплое |
| 3 | Нет интеграционных тестов: каталог → корзина → checkout |
| 4 | `GET /api/v1/orders` и `GET /api/v1/profile` отсутствуют в `api.php` |

### 2.3 Продуктовые пробелы
- Нет auth для публичных пользовательских маршрутов (login/register).
- Нет Lighthouse-аудита PWA.

---

## 3) План дальнейших работ

### Этап A (1 неделя): закрыть критичные пробелы

**Цель:** MVP пользовательского контура без «заглушек».

- [ ] A1. Реализовать `success.blade.php` + маршрут `GET /order/success/{id}`.
- [ ] A2. Реализовать `restaurants/index.blade.php` + маршрут `GET /restaurants`.
- [ ] A3. Подключить Laravel Auth (Breeze или ручной): login, register, guard для `/orders`, `/profile`, `/checkout`.
- [ ] A4. Добавить API-эндпоинты:
  - `POST /api/v1/cart/sync` — серверный пересчёт корзины,
  - `GET /api/v1/orders` — история заказов.
- [ ] A5. Перенести `POST /api/orders` под `middleware([SetTenantContext::class])`.

**Артефакты этапа:**
- Полный рабочий пользовательский путь: главная → ресторан → корзина → checkout → success.

---

### Этап B (1–2 недели): стабилизация и offline-first

**Цель:** надёжная offline-логика и tenant-безопасность.

- [ ] B1. Доработать `CartDrawer`: server sync, перерасчёт цен, конфликт-резолв.
- [ ] B2. Реализовать offline checkout queue (pendingOrders + retry в `CartService`).
- [ ] B3. Провести аудит tenant-изоляции всех endpoint'ов.
- [ ] B4. Уточнить SW-стратегию: версионирование кэша, инвалидация при деплое.

**Артефакты этапа:**
- Демо «от выбора блюда до успешного заказа» (online + offline).
- Технический отчёт по tenant-аудиту.

---

### Этап C (1 неделя): качество и операционная готовность

**Цель:** предсказуемый проект для командной работы.

- [ ] C1. Добавить интеграционные тесты: каталог → корзина → checkout.
- [ ] C2. Unit-тесты для `CartService` (Dexie queue operations).
- [ ] C3. Провести Lighthouse PWA-аудит и зафиксировать базовый балл.
- [ ] C4. Оформить changelog и Definition of Done для каждого модуля.
- [ ] C5. Подготовить план пост-MVP (real-time, push-уведомления, платёжные интеграции).

**Артефакты этапа:**
- Roadmap на 4–6 недель; релизный чеклист; критерии приёмки.

---

## 4) Ссылочный план по шаблонам

Подробный план внедрения HTML-шаблонов из `resources/template/restopwa-html` → см. [TEMPLATE_INTEGRATION_PLAN.md](./TEMPLATE_INTEGRATION_PLAN.md).

Маппинг шаблон → view:

| HTML-шаблон | Blade view | Статус |
|-------------|-----------|--------|
| `index.html` | `home.blade.php` | ✅ Перенесён |
| `restaurant.html` | `restaurants/show.blade.php` | ✅ Перенесён |
| `cart.html` | `cart.blade.php` | ✅ Перенесён |
| `checkout.html` | `livewire/order/checkout-wizard.blade.php` | ✅ Перенесён |
| `orders.html` | `orders/index.blade.php` | ✅ Перенесён |
| `profile.html` | `profile/edit.blade.php` | ✅ Перенесён |
| `success.html` | ❌ отсутствует | 🔴 Нужно создать |

---

## 5) Формат статуса задач

Для каждой задачи использовать единый шаблон:

| Поле | Значение |
|------|---------|
| **Статус** | `Не начато` / `В работе` / `Готово` / `Заблокировано` |
| **Ответственный** | — |
| **Плановая дата** | — |
| **Факт** | — |
| **Риски/блокеры** | — |
| **Следующее действие** | — |
