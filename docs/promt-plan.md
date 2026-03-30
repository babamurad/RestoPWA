# RestoPWA — Full Development Plan (AI Prompt Pack)

---

## 👋 ЭТАП 0: Bootstrap & Архитектура

### 📝 Промт 0.1: Инициализация проекта с DDD-структурой
Создай структуру Laravel 13 проекта "RestoPWA" с Domain-Driven Design организацией каталогов.
- Создай следующую директорию в `app/`: `Domains/` содержащую подпапки: `Menu`, `Order`, `Payment`, `Vendor`, `Geo`, `User`.
- В каждом Domain создай подпапки: `Models`, `Actions`, `Policies`, `Resources` (Filament).
- Настрой `composer.json` для автозагрузки `App\Domains\` через PSR-4.
- Создай `Service` Provider `DomainServiceProvider`, который регистрирует политики и views из доменов.
- **Ограничение:** Не создавай модели пока, только скелет директорий и провайдер.

### 📝 Промт 0.2: Настройка Multitenancy (Global Scope)
Создай систему мультитенантности через Global Scope.
1. **Трейт:** `App\Domains\Vendor\Traits\BelongsToVendor` с методом `bootBelongsToVendor()`, который добавляет глобальный скоуп `BelongsToVendorScope`.
2. **Скоуп:** Класс `App\Domains\Vendor\Scopes\BelongsToVendorScope`, который в `apply()` добавляет `where vendor_id = current_vendor_id` из сессии/контекста.
3. **Сервис:** `App\Domains\Vendor\Services\TenantContext` с методами `setCurrentVendor(?string $vendorId)` и `getCurrentVendor()`.
4. **Middleware:** `App\Http\Middleware\SetTenantContext`, который из поддомена `{vendor}.resto.local` или из header `X-Vendor-ID` устанавливает текущего вендора.
5. **Регистрация:** В `AppServiceProvider` в `boot()` зарегистрируй singleton `TenantContext`.

> [!NOTE]
> Используй Laravel 13 синтаксис с type hints.

---

## 🗄️ ЭТАП 1: База данных (PostgreSQL + PostGIS)

### 📝 Промт 1.1: Миграции ядра с PostGIS
Создай миграции для PostgreSQL 16 с PostGIS extension:
1. `enable_postgis` миграция.
2. `restaurants`: uuid `id`, `slug` unique, `name`, `description`, `settings` jsonb, `delivery_zones` geometry(Polygon,4326) nullable, `is_active` boolean, `commission_rate` decimal(5,2), `owner_id` foreign uuid на `users`.
3. `categories`: `id`, `vendor_id` foreign, `parent_id` self-referencing nullable, `name`, `sort_order`, `is_active`.
4. `products`: uuid `id`, `vendor_id` foreign, `category_id` foreign, `name`, `description`, `price` decimal(10,2), `modifiers` jsonb (schema: `[{id, name, type:enum, options:[{id,name,price}]}]`), `image`, `weight_g`, `kcal`, `is_available` boolean, индекс `vendor_id+is_available`.
5. `orders`: uuid `id`, `vendor_id` foreign, `user_id` foreign, `status` string (`pending`, `confirmed`, `cooking`, `ready`, `delivering`, `completed`, `cancelled`), `address` jsonb, `items` jsonb (snapshot блюд), `total` decimal(10,2), `payment_status`, `delivery_fee` decimal, `created_at`.
6. `order_status_history`: `id`, `order_id` foreign, `from_status`, `to_status`, `metadata` jsonb, `created_at`.

> [!IMPORTANT]
> Все foreign keys с `onDelete cascade` где логично. Добавь индексы GiST на `delivery_zones`.

### 📝 Промт 1.2: Модели Eloquent с Type Casting
Создай модели в `Domains/`:
1. `Vendor\Models\Restaurant`: использует `BelongsToVendor` trait, casts `settings` в array, method `deliveryZones()` возвращает geometry как array координат, `scopeActive()`.
2. `Menu\Models\Category`: tree структура (parent/children), `BelongsToVendor`.
3. `Menu\Models\Product`: casts `modifiers` в `Collection`, casts `price` в `Money` (через пакет `brick/money` или custom cast), accessor `getFinalPriceAttribute` учитывающий modifiers, `scopeAvailable()`.
4. `Order\Models\Order`: casts `address` и `items` в array, casts `total` в `Money`, method `statusHistory()` hasMany `OrderStatusHistory`, boot метод для автоматического логирования изменений статуса в history.
5. `Order\Models\OrderStatusHistory`: casts `metadata` в array.

> [!TIP]
> Все модели используют `HasUuids` или `Ulids`. Добавь type hints PHP 8.3.

---

## 📱 ЭТАП 2: PWA Инфраструктура (Manifest + Service Worker)

### 📝 Промт 2.1: PWA Manifest и Blade Layout
Создай PWA инфраструктуру:
1. **Blade компонент:** `resources/views/components/pwa/meta.blade.php` содержащий:
   - theme-color meta (оранжевый `#FF6B35`)
   - viewport для мобильных
   - apple-touch-icon links
   - manifest link
2. **Маршрут:** `/manifest.json` возвращающий JSON: name "RestoPWA", short_name "Resto", start_url "/", display "standalone", orientation "portrait", background_color "#fff", theme_color "#FF6B35", icons массив с sizes 192x192 и 512x512.
3. **Service Worker:** `public/sw.js` с:
   - именем кэша "resto-pwa-v1"
   - `install` event: кэширование статики (`/build/assets/app.js`, css, шрифты)
   - `fetch` event: стратегия `NetworkFirst` для API (`/api/*`), `CacheFirst` для статики
   - `activate` event: cleanup старых кэшей
4. **Регистрация:** В `resources/js/app.js` код регистрации SW: `if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js')`.

### 📝 Промт 2.2: Offline Fallback UI
Создай offline-first UI компоненты Livewire:
1. `resources/views/components/offline-indicator.blade.php`: показывает баннер "Офлайн режим" когда `navigator.onLine === false`, скрывает при online.
2. `resources/views/components/offline-fallback.blade.php`: обертка которая показывает слот "Контент недоступен офлайн" если нет кэша.
3. `resources/views/offline.blade.php`: базовый HTML, который показывается когда SW не может достучаться до сети.
4. **JS:** В `app.js` слушатели событий online/offline, которые dispatch Livewire события `browser-online` / `browser-offline`.

---

## 🛒 ЭТАП 3: Offline-first Корзина (Dexie.js + Livewire)

### 📝 Промт 3.1: IndexedDB Сервис (JavaScript)
Создай JavaScript сервис `resources/js/services/CartService.js` использующий `Dexie.js`:
1. **Инициализация БД** 'RestoCart' версии 1 со схемами:
   - `cart`: `++id, vendorId, productId, modifiersHash, quantity, price, addedAt`
   - `pendingOrders`: `++id, payload, retries, createdAt`
2. **Методы:**
   - `addItem(productId, vendorId, modifiers, price)`: добавляет или обновляет quantity.
   - `removeItem(id)`
   - `updateQuantity(id, quantity)`
   - `getCartByVendor(vendorId)`
   - `clearVendorCart(vendorId)`
   - `getTotals()`
   - `queueOrder(orderPayload)`: сохраняет заказ для background sync.
3. **Экспорт:** `window.CartService` для доступа из Alpine.js.

### 📝 Промт 3.2: Livewire Компонент Корзины
Создай Livewire 4 компонент `app/Livewire/Cart/CartDrawer.php`:
1. **Свойства:** `$items` (array), `$vendorId` (string), `$isOffline` (bool), `$total`.
2. **Метод `mount()`:** вызывает `CartService.getCartByVendor` и заполняет `$items`.
3. **Метод `addItem()`:** добавляет в Dexie, затем dispatch `cart-updated` to self.
4. **Метод `syncWithServer()`:** если online, отправляет AJAX запрос к `/api/cart/sync`, получает актуальные цены.
5. **Blade view:** side-drawer (slide-over) с Alpine.js, список items с кнопками +/-, кнопка "Оформить".

---

## 📍 ЭТАП 4: Геолокация и Зоны доставки

### 📝 Промт 4.1: PostGIS GeoService
Создай сервис `App\Domains\Geo\Services\GeoService`:
1. `geocodeAddress(string $address)`: Yandex Geocoder API, возвращает coords.
2. `isPointInDeliveryZone(float $lat, float $lon, string $vendorId)`: PostGIS запрос `ST_Contains`.
3. `getRestaurantsByPoint(float $lat, float $lon)`: рестораны в зоне доставки, отсортированные по `ST_Distance`.
4. `calculateDeliveryFee()`: расчет стоимости из JSONB settings.

### 📝 Промт 4.2: Livewire Компонент AddressSelector
Создай Livewire компонент `Geo\AddressSelector`:
1. **Свойства:** `$address`, `$lat`, `$lon`, `$suggestions`, `$selectedVendorId`.
2. **Метод `detectLocation()`:** использует `navigator.geolocation` + `GeoService::geocodeAddress`.
3. **Метод `selectAddress($index)`:** проверяет `isPointInDeliveryZone`, сохраняет в сессии `current_address`.

---

## 🍽️ ЭТАП 5: Каталог и Меню

### 📝 Промт 5.1: API Resource с кэшированием
Создай контроллер `Domains\Menu\Http\Controllers\MenuController`:
1. **Метод `index`:** Валидация, кэширование через `Cache::tags(['menu', 'vendor:'.$id])`.
2. **Маршруты:** route model binding для vendor slug.

### 📝 Промт 5.2: Livewire Каталог
Создай Livewire компонент `Menu\Catalog`:
- Свойства: `$products`, `$categoryId`, `$filters`.
- UI: Grid, карточки блюд, управление модификаторами.

---

## 💳 ЭТАП 6: Оформление заказа и Background Sync

### 📝 Промт 6.1: Checkout Pipeline
Создай компонент `Order\CheckoutWizard`:
- Шаги: Address → Time → Payment → Confirm.
- `submitOrder`: Online → API, Offline → Queue (Dexie).

### 📝 Промт 6.2: Background Sync
Service Worker:
- `sync` event listener.
- Retry logic для отправки заказов из очереди.

---

## ⚡ ЭТАП 7: Real-time

### 📝 Промт 7.1: Reverb
Создай события:
- `OrderStatusUpdated`, `CourierLocationUpdated`.
- Интеграция через Laravel Echo.

### 📝 Промт 7.2: Push Notifications
Создай:
- `push_subscriptions` таблицу и модель.
- `PushService` для отправки уведомлений.

---

## 🛠️ ЭТАП 8: Filament Dashboard

### 📝 Промт 8.1: Resources
Создай ресурсы для администратора:
- `ProductResource`, `OrderResource`, `SettingsResource`.

### 📝 Промт 8.2: Kanban
Создай Kanban board для заказов:
- Drag & Drop изменение статусов.
- Real-time обновление через Echo.

---

## 🔌 ЭТАП 9: Интеграции и Полировка

### 📝 Промт 9.1: ЮKassa
Интеграция оплаты:
- `PaymentGatewayInterface` + `YooKassaGateway`.
- Обработка webhooks, событие `OrderPaid`.

### 📝 Промт 9.2: Оптимизация
- Vite PWA Plugin.
- Оптимизация изображений (WebP).
- Skeleton UI для загрузки.

---

## 🎨 ЭТАП 10: UI/UX (Layouts & Pages)

### 📝 Промт 10.1: Главный Layout
Создай `resources/views/layouts/app.blade.php`:
- Mobile-first (Glovo/Wolt style), TailwindCSS.
- Header с логотипом и корзиной.
- Подключение PWA мета-тегов.

### 📝 Промт 10.2: Главная страница
Список ресторанов (Grid):
- Название, рейтинг, время доставки.
- Livewire компонент `RestaurantsList`.

### 📝 Промт 10.3: Страница ресторана
- Header ресторана, категории меню.
- Livewire `Catalog` + `CartDrawer`.

### 📝 Промт 10.4: Checkout UI
- Визуализация шагов (Progress bar).
- Формы ввода адреса, времени и выбора оплаты.

### 📝 Промт 10.5: Order Tracking UI
- Статус заказа, Timeline событий.
- Карта с местоположением курьера.
lers\MenuController:
1. Метод index(Request $request):
- валидация: vendor_id required, category_id optional
- кэширование: Cache::tags(['menu', 'vendor:'.$request->vendor_id])->remember(...)
- возвращает JSON: categories, products, filters
2. Метод show($id)
3. Добавь route model binding для vendor slug
Промт 5.2: Livewire Каталог
Создай Livewire компонент Menu\Catalog:
1. Свойства: $vendorId, $categoryId, $products, $page, $hasMorePages, $filters
2. Методы: mount(), loadMore(), filterByCategory()
3. UI: grid, карточки, модификаторы, add to cart
ЭТАП 6: Оформление заказа и Background Sync
Промт 6.1: Checkout Pipeline
Создай компонент Order\CheckoutWizard:
Шаги: Address → Time → Payment → Confirm
submitOrder:
- online → API
- offline → queue
Промт 6.2: Background Sync
Service Worker:
- sync event
- retry logic
- отправка заказов
ЭТАП 7: Real-time
Промт 7.1: Reverb
Создай:
- OrderStatusUpdated
- CourierLocationUpdated
Tracking через Echo
Промт 7.2: Push
Создай:
- push_subscriptions
- PushService
- уведомления
ЭТАП 8: Filament Dashboard
Промт 8.1: Resources
Создай:
- ProductResource
- OrderResource
- SettingsResource
Промт 8.2: Kanban
Создай:
- Kanban board
- drag & drop
- realtime обновление

ЭТАП 9: Интеграции и Полировка
Промт 9.1: ЮKassa
Создай:
- PaymentGatewayInterface
- YooKassaGateway
- webhook обработку
- OrderPaid event
Промт 9.2: Оптимизация
Сделай:
- vite-plugin-pwa
- оптимизацию изображений
- preload
- skeleton UI
- очереди
- HTTP cache


Промт 10.1: Главный Layout
Создай основной layout resources/views/layouts/app.blade.php:

- mobile-first дизайн
- TailwindCSS
- header:
  - логотип
  - кнопка корзины
- slot для контента
- подключи pwa/meta компонент
- подключи app.js и app.css

Сделай минималистичный современный дизайн (как Glovo/Wolt)
Промт 10.2: Главная страница (список ресторанов)
Создай страницу Home:

Route: /

Показывает:
- список ресторанов (grid)
- название
- рейтинг (заглушка)
- время доставки
- кнопка "Открыть"

Используй Livewire компонент RestaurantsList
Промт 10.3: Страница ресторана
Route: /restaurant/{slug}

Страница:
- header ресторана
- категории
- каталог (Livewire Catalog)

Подключи CartDrawer
Промт 10.4: Checkout UI
Создай страницу Checkout:

- шаги (progress bar)
- Address
- Time
- Payment
- Confirm

Используй CheckoutWizard
Промт 10.5: Order Tracking UI
Создай страницу /orders/{id}:

- статус заказа
- timeline
- карта (если delivering)

Подписка на Echo