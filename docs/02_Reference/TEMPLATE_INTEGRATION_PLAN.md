# План внедрения шаблонов в RestoPWA (только Вариант A)

Да, согласен: в вашем текущем проекте переход на React **не дает ощутимых преимуществ**.

Причина: стек уже построен вокруг Laravel + Livewire + Alpine + Dexie + PWA, и добавление отдельного React SPA увеличит сложность (двойной frontend pipeline, дублирование маршрутизации и состояния корзины) без пропорциональной пользы.

---

## 1) Целевая архитектура (фиксируем)

Используем только:
- **Laravel Blade** для страниц и layout,
- **Livewire** для интерактивных серверных компонентов,
- **Alpine.js + Dexie** для offline-first состояния корзины,
- **Service Worker + Manifest** для PWA.

Шаблоны берем из:
- `resources/template/restopwa-html` (основной источник верстки),
- `resources/template/app` (только как справочник по UI/UX, без переноса React-стека).

---

## 2) Правила внедрения шаблонов

1. Не переносить React/TS инфраструктуру (`src`, `vite.config.ts`, React зависимости).
2. Переносить только:
   - HTML-структуру,
   - CSS-классы/визуальные паттерны,
   - UX-поведение, которое совместимо с Alpine/Livewire.
3. Все данные — только из Laravel API/БД (никаких mock в прод-коде).
4. Все tenant-зависимые запросы должны идти через текущий tenant context.

---

## 3) Маппинг шаблонов на Laravel-страницы

Источник `resources/template/restopwa-html`:
- `index.html` → `GET /` (главная)
- `restaurant.html` → `GET /restaurants/{slug}`
- `cart.html` → Livewire `CartDrawer` + (опц.) `GET /cart`
- `checkout.html` → `GET /checkout`
- `orders.html` → `GET /orders`
- `profile.html` → `GET /profile`
- `success.html` → `GET /order/success/{id}`

Для каждого экрана создать:
- Blade view,
- при необходимости Livewire-компонент,
- API-контракт,
- offline-сценарий.

---

## 4) Поэтапный план внедрения (A-only)

### Этап 1: Базовый layout и навигация
1. Привести `resources/views/layouts/app.blade.php` к финальному каркасу:
   - подключение PWA meta,
   - offline indicator,
   - глобальный cart manager,
   - mount `CartDrawer`.
2. Добавить единые Blade-компоненты:
   - header/location,
   - карточки ресторана/блюда,
   - bottom nav,
   - order card.
3. Вынести общие стили в `resources/css/app.css`.

### Этап 2: Главная + ресторан
1. Перенести верстку из `index.html` и `restaurant.html`.
2. Подключить реальные данные:
   - рестораны,
   - категории,
   - продукты.
3. Проверить tenant-фильтрацию на уровне backend.

### Этап 3: Корзина + checkout (offline-first)
1. Связать UI с `CartService` (Dexie).
2. Довести `CartDrawer` до контракта:
   - корректный state,
   - +/- удаление,
   - синхронизация с сервером,
   - обработка изменения цены.
3. Реализовать checkout online и очередь offline (pendingOrders + retry).

### Этап 4: Orders + Profile + Success
1. Перенести `orders.html`, `profile.html`, `success.html`.
2. Подключить реальные данные пользователя и заказов.
3. Добавить middleware/auth guard для приватных экранов.

### Этап 5: PWA полировка
1. Проверить `manifest.json` и иконки.
2. Доработать `public/sw.js`:
   - cache assets,
   - API network-first,
   - offline fallback на `/offline`.
3. Проверить обновление SW и очистку старых кэшей.

---

## 5) API и данные (обязательный минимум)

Нужные endpoint-контракты:
- `GET /api/v1/restaurants`
- `GET /api/v1/restaurants/{slug}/menu`
- `POST /api/v1/cart/sync`
- `POST /api/v1/orders`
- `GET /api/v1/orders`
- `GET /api/v1/profile`

Требования:
- tenant validation в каждом endpoint,
- серверная переоценка заказа на checkout/sync,
- согласованный формат ошибок для offline retry.

---

## 6) Риски и митигации

1. **Расхождение состояния корзины (Livewire vs Dexie)**
   - Решение: источник истины — Dexie, сервер только reconciliation.

2. **Tenant leakage**
   - Решение: middleware + global scope + feature tests.

3. **Offline inconsistencies (цены/наличие)**
   - Решение: обязательный серверный пересчет при `cart/sync` и перед созданием заказа.

4. **Перегрузка Blade-представлений**
   - Решение: декомпозиция на Blade-компоненты + частичные view.

---

## 7) Тест-план

### Backend
- Feature: tenant scope/middleware.
- Feature: cart sync и checkout.
- Feature: orders/profile доступы.
- Migration smoke: PostgreSQL + PostGIS.

### Frontend
- Unit: `CartService` (Dexie операции + queue).
- Интеграция: Livewire CartDrawer ↔ Alpine cart manager.
- E2E smoke: home → restaurant → cart → checkout.

### PWA
- Offline fallback page.
- Работа кэша после деплоя новой версии.
- Lighthouse PWA baseline.

---

## 8) Definition of Done

- [ ] Все экраны из `restopwa-html` перенесены в Blade/Livewire.
- [ ] React-стек не используется в runtime проекта.
- [ ] Корзина offline-first стабильно работает на Dexie.
- [ ] Sync/checkout корректно обрабатывают изменение цен.
- [ ] Tenant-изоляция подтверждена тестами.
- [ ] PWA корректно работает в offline сценариях.
- [ ] Нет дублирующих frontend pipeline в проде.

---

## 9) Короткий план на 10 рабочих дней

1-2 день: layout + base components + styles.  
3-4 день: home + restaurant + data wiring.  
5-6 день: cart + checkout + sync/queue.  
7-8 день: orders + profile + success.  
9 день: PWA/offline polish.  
10 день: тесты, багфикс, cleanup.
