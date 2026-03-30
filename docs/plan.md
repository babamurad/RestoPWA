1) Этап 0.1 — Довести DDD-скелет до полного соответствия
Создать недостающие домены:

app/Domains/Payment

app/Domains/Geo

app/Domains/User

В каждом домене создать подпапки:

Models

Actions

Policies

Resources (и внутри обычно views/Filament по вашему подходу)

Проверить DomainServiceProvider:

Оставить динамическую регистрацию policy/view.

Убедиться, что namespace и пути views реально совпадают с фактической структурой.

Прогнать:

composer dump-autoload

php artisan optimize:clear

2) Этап 0.2 — Multitenancy через Global Scope
Создать TenantContext сервис:

setCurrentVendor(?string $vendorId): void

getCurrentVendor(): ?string

Зарегистрировать singleton в AppServiceProvider::boot() (или register(), если так удобнее архитектурно).

Создать BelongsToVendorScope:

В apply() фильтр where vendor_id = currentVendor.

Обработка случая null (решите стратегию: не фильтровать, или блокировать выдачу).

Создать trait BelongsToVendor:

bootBelongsToVendor() → добавить global scope.

(Опционально) auto-fill vendor_id при creating.

Создать middleware SetTenantContext:

Источник 1: header X-Vendor-ID.

Источник 2: поддомен {vendor}.resto.local.

Приоритет источников зафиксировать документально.

Подключить middleware:

Глобально или на нужные группы роутов (web/api).

Добавить тесты:

Scope действительно режет данные по vendor.

Middleware корректно извлекает vendor из subdomain/header.

3) Этап 1.1 — Миграции PostgreSQL/PostGIS
Проверить строгую схему restaurants.delivery_zones:

Если хотите строго по промту, убрать fallback text.

Если оставляете fallback — задокументировать как intentional deviation.

Проверить FK и onDelete:

Уже в целом ок, но проверьте “логично” для всех связей (особенно parent_id и category_id).

Проверить индексы:

GiST на delivery_zones.

Составные индексы по фактическим запросам (vendor_id + status, vendor_id + is_available и т.д.).

Добавить миграционные smoke-тесты под PostgreSQL 16 + PostGIS.

4) Этап 1.2 — Модели + типизация + касты
Исправить несоответствие Category id:

Либо в миграции categories сделать UUID.

Либо убрать HasUuids из Category.

Рекомендую один стандарт ID по проекту.

Реализовать реальный MoneyCast:

Через brick/money или свой cast-класс.

Убрать placeholder class_exists(...) ? ... : 'array'.

Убедиться, что BelongsToVendor реально существует и подключен в нужных моделях.

Restaurant::deliveryZones():

Привести к стабильному формату (GeoJSON/array координат), документировать контракт.

Order::booted():

Проверить, что history пишется корректно при всех способах смены статуса (mass update отдельно!).

Добавить unit/feature тесты:

casts (settings, address, items, metadata, money fields)

scopes (active, available, tenant scope)

status history logging.

5) Этап 2.1 — PWA infra (Manifest + SW)
Manifest:

Добавить/проверить id, scope.

При необходимости prefer_related_applications: false.

Иконки реально положить в public/.

Service Worker:

В install-кэш добавить CSS, шрифты и critical assets.

В fetch catch возвращать /offline для navigation requests.

Версионирование кэша (resto-pwa-v2 и cleanup).

Проверить корректность стратегии:

NetworkFirst для /api/*

CacheFirst для статики

Отдельно navigate fallback.

Протестировать Lighthouse PWA и offline mode в DevTools.

6) Этап 2.2 — Offline UI
offline-indicator:

Сделать минималистичный и не перекрывающий critical UI.

offline-fallback:

Точно определить, когда показывать “контент недоступен офлайн”:

только при отсутствии кэшированного контента.

offline.blade.php:

Оставить максимально легкой, без внешних CDN (чтобы в offline реально работала всегда).

Проверить dispatch событий:

browser-online, browser-offline совместимы с Livewire 4 в вашем стеке.

7) Этап 3.1 — Dexie CartService
Создать resources/js/services/CartService.js:

DB: RestoCart, версия 1.

Таблицы: cart, pendingOrders.

Реализовать все методы из промта.

Добавить стабильный modifiersHash:

canonical stringify + hash (чтобы одинаковые модификаторы действительно совпадали).

Экспорт:

window.CartService = ...

JSDoc:

Описать типы CartItem, PendingOrderPayload, Totals.

Добавить JS-тесты (минимум unit для add/update/getTotals).

8) Этап 3.2 — Livewire CartDrawer (stateless)
Создать app/Livewire/Cart/CartDrawer.php и blade-view.

Весь state хранить в Dexie:

сервер — только sync endpoint.

Реализовать методы:

mount, addItem, removeItem, syncWithServer.

Обработать кейс price change после sync.

События online/offline:

listener на browser-offline/browser-online.

UI:

side-drawer + +/- + disabled checkout при недоступной доставке.

Feature-тесты на sync-контракт API.

9) Рекомендуемый порядок выполнения (чтобы не ломать друг друга)
Multitenancy (0.2)

Исправление ID/кастов моделей (1.2)

PWA SW fallback и офлайн-страница (2.1/2.2)

Dexie CartService (3.1)

Livewire CartDrawer + sync API (3.2)

Финально — интеграционные тесты end-to-end.

10) Definition of Done (чеклист)
 Все домены и подпапки созданы по шаблону.

 TenantContext + Scope + Trait + Middleware работают в web/api.

 Миграции проходят на PostgreSQL 16 + PostGIS без ручных правок.

 Модели без placeholder-кастов, money cast реален.

 SW корректно отдает /offline при отсутствии сети.

 CartService на Dexie полностью реализован.

 CartDrawer stateless и синхронизируется с сервером.

 Тесты: unit + feature + smoke offline/pwa.