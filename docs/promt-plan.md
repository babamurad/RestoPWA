# RestoPWA — Full Development Plan (AI Prompt Pack)

---

## ЭТАП 0: Bootstrap & Архитектура

### Промт 0.1: Инициализация проекта с DDD-структурой

Создай структуру Laravel 13 проекта "RestoPWA" с Domain-Driven Design организацией каталогов. 
Создай следующую директорию в app/: Domains/ содержащую подпапки: Menu, Order, Payment, Vendor, Geo, User.
В каждом Domain создай подпапки: Models, Actions, Policies, Resources (Filament).
Настрой composer.json для автозагрузки App\Domains\ через PSR-4.
Создай ServiceProvider DomainServiceProvider который регистрирует политики и views из доменов.
Не создавай модели пока, только скелет директорий и провайдер.
Промт 0.2: Настройка Multitenancy (Global Scope)
Создай систему мультитенантности через Global Scope.
1. Создай трейт App\Domains\Vendor\Traits\BelongsToVendor с методом bootBelongsToVendor() который добавляет глобальный скоуп BelongsToVendorScope
2. Создай класс App\Domains\Vendor\Scopes\BelongsToVendorScope который в apply() добавляет where vendor_id = current_vendor_id из сессии/контекста
3. Создай сервис App\Domains\Vendor\Services\TenantContext с методами setCurrentVendor(?string $vendorId) и getCurrentVendor()
4. Создай middleware App\Http\Middleware\SetTenantContext который из поддомена {vendor}.resto.local или из header X-Vendor-ID устанавливает текущего вендора
5. В AppServiceProvider в boot() зарегистрируй singleton TenantContext
Используй Laravel 13 синтаксис с type hints.
ЭТАП 1: База данных (PostgreSQL + PostGIS)
Промт 1.1: Миграции ядра с PostGIS
Создай миграции для PostgreSQL 16 с PostGIS extension:
1. enable_postgis миграция
2. restaurants: uuid id, slug unique, name, description, settings jsonb, delivery_zones geometry(Polygon,4326) nullable, is_active boolean, commission_rate decimal(5,2), owner_id foreign uuid на users
3. categories: id, vendor_id foreign, parent_id self-referencing nullable, name, sort_order, is_active
4. products: uuid id, vendor_id foreign, category_id foreign, name, description, price decimal(10,2), modifiers jsonb (schema: [{id, name, type:enum, options:[{id,name,price}]}]), image, weight_g, kcal, is_available boolean, index vendor_id+is_available
5. orders: uuid id, vendor_id foreign, user_id foreign, status string (pending,confirmed,cooking,ready,delivering,completed,cancelled), address jsonb, items jsonb (snapshot блюд), total decimal(10,2), payment_status, delivery_fee decimal, created_at
6. order_status_history: id, order_id foreign, from_status, to_status, metadata jsonb, created_at
Все foreign keys с onDelete cascade где логично. Добавь индексы GiST на delivery_zones.
Промт 1.2: Модели Eloquent с Type Casting
Создай модели в Domains/:
1. Vendor\Models\Restaurant: использует BelongsToVendor trait, casts settings в array, method deliveryZones() возвращает geometry как array координат, scopeActive()
2. Menu\Models\Category: tree структура (parent/children), BelongsToVendor
3. Menu\Models\Product: casts modifiers в Collection, casts price в Money (через пакет brick/money или custom cast), accessor getFinalPriceAttribute учитывающий modifiers, scopeAvailable()
4. Order\Models\Order: casts address и items в array, casts total в Money, method statusHistory() hasMany OrderStatusHistory, boot метод для автоматического логирования изменений статуса в history
5. Order\Models\OrderStatusHistory: casts metadata в array
Все модели используют HasUuids или Ulids. Добавь type hints PHP 8.3.
ЭТАП 2: PWA Инфраструктура (Manifest + Service Worker)
Промт 2.1: PWA Manifest и Blade Layout
Создай PWA инфраструктуру:
1. Создай blade компонент resources/views/components/pwa/meta.blade.php содержащий:
- theme-color meta (оранжевый #FF6B35)
- viewport для мобильных
- apple-touch-icon links
- manifest link
2. Создай маршрут /manifest.json возвращающий JSON: name "RestoPWA", short_name "Resto", start_url "/", display "standalone", orientation "portrait", background_color "#fff", theme_color "#FF6B35", icons массив с sizes 192x192 и 512x512 (placeholder пути)
3. Создай Service Worker файл public/sw.js с:
- именем кэша "resto-pwa-v1"
- install event: кэширование статики (/build/assets/app.js, css, шрифты)
- fetch event: стратегия NetworkFirst для API (/api/*), CacheFirst для статики
- activate event: cleanup старых кэшей
4. Добавь в resources/js/app.js код регистрации SW: if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js')
Используй Workbox если возможно, или vanilla JS.
Промт 2.2: Offline Fallback UI
Создай offline-first UI компоненты Livewire:
1. Компонент resources/views/components/offline-indicator.blade.php: показывает баннер "Офлайн режим" когда navigator.onLine === false, скрывает при online
2. Компонент resources/views/components/offline-fallback.blade.php: обертка которая показывает слот "Контент недоступен офлайн" если нет кэша
3. Создай страницу resources/views/offline.blade.php с базовым HTML которая показывается когда SW не может достучаться до сети (fallback page в манифесте)
4. Добавь в app.js слушатели событий online/offline которые dispatch Livewire события 'browser-online' / 'browser-offline'
ЭТАП 3: Offline-first Корзина (Dexie.js + Livewire)
Промт 3.1: IndexedDB Сервис (JavaScript)
Создай JavaScript сервис resources/js/services/CartService.js использующий Dexie.js:
1. Инициализация БД 'RestoCart' версии 1 со схемами:
- cart: ++id, vendorId, productId, modifiersHash, quantity, price, addedAt
- pendingOrders: ++id, payload, retries, createdAt
2. Методы:
- addItem(productId, vendorId, modifiers, price): добавляет или обновляет quantity если modifiersHash совпадает
- removeItem(id)
- updateQuantity(id, quantity)
- getCartByVendor(vendorId): возвращает все items для конкретного ресторана
- clearVendorCart(vendorId): удаляет всю корзину ресторана
- getTotals(): считает сумму и количество
- queueOrder(orderPayload): сохраняет заказ в pendingOrders для background sync
3. Экспортируй window.CartService для доступа из Alpine.js
Добавь JSDoc типы.
Промт 3.2: Livewire Компонент Корзины
Создай Livewire 4 компонент app/Livewire/Cart/CartDrawer.php:
1. Свойства: $items (array), $vendorId (string), $isOffline (bool), $total
2. Метод mount(): вызывает CartService.getCartByVendor и заполняет $items
3. Метод addItem(productId, modifiers, price): добавляет в Dexie, затем dispatch 'cart-updated' to self
4. Метод removeItem(id): удаляет из Dexie и массива
5. Метод syncWithServer(): если online, отправляет AJAX запрос к /api/cart/sync с текущими items, получает актуальные цены и обновляет локальные (обработка price change)
6. Слушатель событий: на 'browser-offline' устанавливает $isOffline = true
7. Blade view: side-drawer (slide-over) с Alpine.js для анимации, список items с кнопками +/-, кнопка "Оформить" (disabled если vendor не доставляет по адресу)
Важно: компонент должен работать без серверного state (stateless), весь state в Dexie.
ЭТАП 4: Геолокация и Зоны доставки
Промт 4.1: PostGIS GeoService
Создай сервис App\Domains\Geo\Services\GeoService:
1. Метод geocodeAddress(string $address): использует Http client для Yandex Geocoder API, возвращает ['lat' => float, 'lon' => float, 'address' => string]
2. Метод isPointInDeliveryZone(float $lat, float $lon, string $vendorId): использует PostGIS запрос "SELECT ST_Contains(delivery_zones, ST_SetSRID(ST_MakePoint(?, ?), 4326)) FROM restaurants WHERE id = ?", возвращает bool
3. Метод getRestaurantsByPoint(float $lat, float $lon): возвращает коллекцию ресторанов у которых delivery_zones содержит точку и is_active = true, отсортированные по расстоянию (ST_Distance)
4. Метод calculateDeliveryFee(float $lat, float $lon, string $vendorId): если есть зона с fee в JSONB settings, возвращает fee, иначе default
Добавь обработку исключений и кэширование геокодинга на 1 час.
Промт 4.2: Livewire Компонент AddressSelector
Создай Livewire компонент Geo\AddressSelector:
1. Свойства: $address (string), $lat, $lon, $suggestions (array), $selectedVendorId
2. Метод detectLocation(): использует браузерный navigator.geolocation, получает coords, вызывает GeoService::geocodeAddress на сервере (обратное геокодирование), устанавливает $address
3. Метод searchAddress(): debounced (300ms) поиск подсказок через Yandex Geocoder, заполняет $suggestions
4. Метод selectAddress($index): берет выбранный адрес, проверяет через GeoService isPointInDeliveryZone для $selectedVendorId, если не доставляет - показывает error, если доставляет - сохраняет в сессии 'current_address' и dispatch 'address-selected'
5. Blade: поле ввода с autocomplete, кнопка "Определить автоматически", отображение зоны доставки
ЭТАП 5: Каталог и Меню
Промт 5.1: API Resource с кэшированием
Создай API контроллер Domains\Menu\Http\Controllers\MenuController:
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