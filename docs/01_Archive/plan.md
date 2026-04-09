# RestoPWA — План доработки по промтам

## 1) Этап 0.1 — DDD-структура
- Создать домены: `Payment`, `Geo`, `User` в `app/Domains/`.
- В каждом домене добавить подпапки:
  - `Models`
  - `Actions`
  - `Policies`
  - `Resources` (при необходимости `Resources/views`, `Resources/Filament`)
- Проверить соответствие `DomainServiceProvider` фактической структуре каталогов.
- Прогнать:
  - `composer dump-autoload`
  - `php artisan optimize:clear`

---

## 2) Этап 0.2 — Multitenancy через Global Scope
- Создать сервис `App\Domains\Vendor\Services\TenantContext`:
  - `setCurrentVendor(?string $vendorId): void`
  - `getCurrentVendor(): ?string`
- Зарегистрировать singleton в `AppServiceProvider`.
- Создать `App\Domains\Vendor\Scopes\BelongsToVendorScope`:
  - `apply()` добавляет `where vendor_id = current_vendor_id`.
- Создать трейт `App\Domains\Vendor\Traits\BelongsToVendor`:
  - `bootBelongsToVendor()` подключает global scope.
- Создать middleware `App\Http\Middleware\SetTenantContext`:
  - Источники: `X-Vendor-ID` header или поддомен `{vendor}.resto.local`.
- Подключить middleware к нужным маршрутам/группам.
- Написать feature-тесты для scope/middleware.

---

## 3) Этап 1.1 — База данных (PostgreSQL 16 + PostGIS)
- Проверить миграции на строгое соответствие промту:
  - `restaurants.delivery_zones` как `geometry(Polygon,4326)`.
  - GiST индекс на `delivery_zones`.
- Проверить `foreign keys` и `onDelete`-правила.
- Проверить составные индексы под запросы:
  - `products(vendor_id, is_available)`
  - `orders(vendor_id, status)`
- Добавить smoke-тест миграций под PostgreSQL+PostGIS.

---

## 4) Этап 1.2 — Модели и касты
- Привести `Category` к консистентной схеме ID:
  - либо UUID в миграции,
  - либо убрать `HasUuids`.
- Реализовать реальный `MoneyCast` (через `brick/money` или custom cast).
- Убрать placeholder-касты.
- Проверить `BelongsToVendor` в моделях `Restaurant`, `Category`, `Product` (если нужно).
- Проверить `Order::booted()`:
  - Логирование смены статуса в `order_status_history`.
- Добавить unit/feature тесты на casts/scopes/history.

---

## 5) Этап 2.1 — PWA (Manifest + Service Worker)
- Уточнить `manifest.json`:
  - `name`, `short_name`, `start_url`, `display`, `orientation`, цвета, иконки.
  - (Опционально) `id`, `scope`.
- Доработать `public/sw.js`:
  - install: кэшировать JS/CSS/шрифты/критичную статику.
  - fetch: `NetworkFirst` для `/api/*`, `CacheFirst` для статики.
  - navigation fallback на `/offline` при сетевой ошибке.
  - activate: cleanup старых кэшей.
- Проверить регистрацию SW в `resources/js/app.js`.
- Прогнать Lighthouse PWA проверку.

---

## 6) Этап 2.2 — Offline UI
- Проверить компоненты:
  - `offline-indicator`
  - `offline-fallback`
  - `offline.blade.php`
- Убедиться, что offline-страница не зависит от внешних CDN в полном offline.
- Проверить dispatch событий:
  - `browser-online`
  - `browser-offline`

---

## 7) Этап 3.1 — IndexedDB сервис (Dexie)
- Создать `resources/js/services/CartService.js`.
- Инициализировать БД `RestoCart` v1:
  - `cart: ++id, vendorId, productId, modifiersHash, quantity, price, addedAt`
  - `pendingOrders: ++id, payload, retries, createdAt`
- Реализовать методы:
  - `addItem`
  - `removeItem`
  - `updateQuantity`
  - `getCartByVendor`
  - `clearVendorCart`
  - `getTotals`
  - `queueOrder`
- Экспортировать `window.CartService`.
- Добавить JSDoc-типизацию.

---

## 8) Этап 3.2 — Livewire CartDrawer (stateless)
- Создать `app/Livewire/Cart/CartDrawer.php` и blade view.
- Реализовать свойства:
  - `$items`, `$vendorId`, `$isOffline`, `$total`
- Реализовать методы:
  - `mount()`
  - `addItem(productId, modifiers, price)`
  - `removeItem(id)`
  - `syncWithServer()` (`/api/cart/sync`)
- Добавить listeners на `browser-offline` / `browser-online`.
- UI: side-drawer + анимация + список позиций + +/- + disabled checkout если доставка невозможна.
- Весь runtime state корзины хранить в Dexie.

---

## 9) Рекомендуемый порядок внедрения
1. Multitenancy (Этап 0.2)
2. Модели/касты и консистентность ID (Этап 1.2)
3. SW fallback + offline UI (Этап 2)
4. Dexie CartService (Этап 3.1)
5. Livewire CartDrawer + sync API (Этап 3.2)
6. Финальные интеграционные тесты

---

## 10) Definition of Done
- [ ] Все домены и подпапки созданы по DDD-шаблону.
- [ ] Multitenancy работает (scope + middleware + tenant context).
- [ ] Миграции полностью проходят на PostgreSQL 16 + PostGIS.
- [ ] Модели без placeholder-кастов.
- [ ] SW корректно отдает offline fallback.
- [ ] Dexie корзина и очередь заказов работают.
- [ ] Livewire CartDrawer stateless и синхронизируется с API.
- [ ] Покрытие тестами базовых сценариев (unit/feature/integration).

## Промт 4.2: Livewire Компонент AddressSelector
plain
Создай Livewire компонент Geo\AddressSelector:
1. Свойства: $address (string), $lat, $lon, $suggestions (array), $selectedVendorId
2. Метод detectLocation(): использует браузерный navigator.geolocation, получает coords, вызывает GeoService::geocodeAddress на сервере (обратное геокодирование), устанавливает $address
3. Метод searchAddress(): debounced (300ms) поиск подсказок через Yandex Geocoder, заполняет $suggestions
4. Метод selectAddress($index): берет выбранный адрес, проверяет через GeoService isPointInDeliveryZone для $selectedVendorId, если не доставляет - показывает error, если доставляет - сохраняет в сессии 'current_address' и dispatch 'address-selected'
5. Blade: поле ввода с autocomplete (datalist или div overlay), кнопка "Определить автоматически", отображение зоны доставки (входит/не входит)
