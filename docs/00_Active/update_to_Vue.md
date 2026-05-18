# Переход на Laravel + Vue.js (SPA / PWA)

## Что есть сейчас и что важно сохранить

### 1. Backend уже можно использовать как основу для Vue
У тебя уже есть API-группа `/api/v1` с меню, ресторанами, категориями, корзиной, заказами и push-подписками. Это хороший фундамент для Vue SPA/PWA: не нужно начинать backend с нуля, нужно нормализовать контракты API и постепенно перевести UI на эти endpoints.

### 2. Публичные страницы сейчас в основном Blade/Livewire
Основные публичные web-маршруты ведут на Laravel views: главная, рестораны, карточка ресторана, корзина, заказы, профиль и tracking. Checkout сейчас сделан через Livewire-компонент `CheckoutWizard`.

### 3. Заказы уже отправляются из JS в API
В `resources/js/app.js` есть логика отправки заказа через `fetch('/api/v1/orders')` с CSRF, vendor id и idempotency key. Это значит, что часть логики уже близка к frontend-app архитектуре и может быть перенесена в Vue composables/stores.

### 4. PWA-слой есть, но его нужно привести в рабочее состояние
В проекте есть `public/manifest.json` с `display: standalone`, `start_url`, `scope`, `icons` и `shortcuts`. Также есть service worker с кэшированием navigation/API/static/image requests. Но в текущем Blade layout service worker принудительно unregister-ится, а caches очищаются на загрузке страницы. Это нужно будет исправить до упаковки в мобильное приложение.

---

## Пошаговый план перехода на Laravel + Vue.js

### Этап 0. Зафиксировать целевую архитектуру
Рекомендую выбрать такую схему:

```text
Laravel
├── API backend: /api/v1/*
├── Auth/session/Sanctum
├── Filament admin/vendor panels
├── queues/events/notifications
├── storage/media
└── Vue frontend
    ├── публичная PWA: рестораны, меню, корзина, checkout, заказы, профиль
    ├── offline/cart/order sync
    └── позже Capacitor/TWA/mobile shell
```

**То есть:**
* **Laravel** остаётся backend-ядром: модели, домены, заказы, рестораны, меню, админка, vendor panel.
* **Vue** забирает пользовательский frontend: главная, каталог, корзина, checkout, профиль, история заказов, tracking.
* **Filament** лучше не переписывать на Vue на первом этапе, потому что он уже подключён и решает админские/vendor задачи.
* **Livewire** постепенно убрать только из публичного mobile/PWA flow, начиная с checkout/cart, где Vue даст больше контроля над состоянием.

### Этап 1. Аудит текущих экранов и разделение на «оставить» / «переписать»
Сначала нужно составить таблицу текущих экранов:

| Раздел | Сейчас | Цель |
| :--- | :--- | :--- |
| **Главная** | Blade view через `RestaurantController::home` | Vue route `/` |
| **Список ресторанов** | Blade view | Vue route `/restaurants` |
| **Ресторан/меню** | Blade view | Vue route `/restaurants/:slug` |
| **Корзина** | Blade + JS/Livewire | Vue store + route `/cart` |
| **Checkout** | Livewire `CheckoutWizard` | Vue route `/checkout` |
| **Заказы** | Blade view | Vue route `/orders` |
| **Профиль** | Blade view | Vue route `/profile` |
| **Tracking** | Blade/API mix | Vue route `/order/:id/track` |
| **Admin/vendor** | Filament/Blade | Оставить |

> [!NOTE]
> **Почему так:** Текущий `RestaurantController` отдаёт Blade views и загружает данные напрямую из моделей. Для Vue эти данные лучше получать через API, а не через серверный HTML.

### Этап 2. Подготовить Laravel API как стабильный контракт для Vue
Перед установкой Vue стоит привести API к единому формату.

#### Что сделать:

1. **Описать API-контракты для frontend:**
   * `GET /api/v1/restaurants`
   * `GET /api/v1/restaurants/{slug}`
   * `GET /api/v1/menu/{vendor}`
   * `GET /api/v1/menu/product/{product}`
   * `POST /api/v1/cart/sync`
   * `POST /api/v1/orders`
   * `GET /api/v1/orders`
   * `GET /api/v1/orders/{id}`
   * `POST /api/v1/push/subscribe`
   * `POST /api/v1/push/unsubscribe`

   *Эти endpoints уже частично есть в `routes/api.php`.*

2. **Унифицировать JSON-ответы.**
   Сейчас, например, `MenuController::index` возвращает сразу `categories`, `products`, `filters`, а `show` возвращает плоский объект продукта. Для Vue лучше иметь предсказуемый формат:
   ```json
   {
     "success": true,
     "data": {},
     "meta": {},
     "message": null
   }
   ```

3. **Проверить auth-модель.**
   В API заказы и push защищены `auth:sanctum`. Для Vue нужно решить: остаёмся на cookie session + Sanctum SPA auth или переходим к token-based auth для mobile shell.

4. **Сделать backend независимым от Blade-flow.**
   Например, web-route `/api/v1/orders` сейчас дополнительно объявлен в `routes/web.php` под `auth` + `SetTenantContext`. Лучше постепенно оставить API в `routes/api.php`, а web routes использовать только для SSR fallback или SPA entry.

### Этап 3. Установить Vue в текущий Laravel/Vite проект
Так как Vite уже настроен через `laravel-vite-plugin`, отдельный frontend-проект создавать необязательно.

#### Рекомендуемый путь:

1. **Добавить зависимости:**
   ```bash
   npm remove react react-dom lucide-react
   npm install vue @vitejs/plugin-vue vue-router pinia
   ```
   *React-зависимости сейчас есть в `package.json`, поэтому их можно убрать после переноса текущих React/JS частей или если они реально не используются.*

2. **Обновить `vite.config.js`:**
   ```javascript
   import { defineConfig } from 'vite';
   import laravel from 'laravel-vite-plugin';
   import vue from '@vitejs/plugin-vue';

   export default defineConfig({
     plugins: [
       laravel({
         input: ['resources/css/app.css', 'resources/js/app.js'],
         refresh: true,
       }),
       vue(),
       tailwindcss(), // TailwindCSS плагин
     ],
   });
   ```

3. **Создать структуру директорий:**
   ```text
   resources/js/
   ├── app.js
   ├── vue/
   │   ├── App.vue
   │   ├── router/
   │   │   └── index.js
   │   ├── stores/
   │   │   ├── cart.js
   │   │   ├── auth.js
   │   │   ├── restaurants.js
   │   │   └── orders.js
   │   ├── api/
   │   │   ├── client.js
   │   │   ├── restaurants.js
   │   │   ├── menu.js
   │   │   ├── orders.js
   │   │   └── push.js
   │   ├── pages/
   │   ├── components/
   │   └── composables/
   ```

> [!NOTE]
> На первом этапе `resources/js/app.js` должен монтировать Vue app, но временно можно оставить часть существующих сервисов, например cart/order submission, пока они не перенесены. Сейчас `app.js` импортирует `CartService`, `CartAlpine` и `SweetAlert2`.

### Этап 4. Сделать Vue SPA entry внутри Laravel
Нужно добавить Blade-шаблон, который будет только контейнером для Vue.

Вставьте в файл `resources/views/app.blade.php`:
```html
<div id="app"></div>
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

Сейчас общий layout подключает Vite, Livewire styles/scripts, PWA meta, Yandex Maps и несколько глобальных переменных. Это нужно разделить:
* `resources/views/app.blade.php` — для Vue PWA.
* **Старые Blade layouts** — временно оставить для admin/vendor/legacy pages.
* **Filament** — не трогать.
* **Livewire scripts** — не подключать в Vue shell, если страница больше не использует Livewire.

#### Routing strategy
На время миграции лучше использовать гибридный подход:
```php
Route::view('/app/{any?}', 'app')->where('any', '.*');
```

А когда Vue-приложение будет полностью готово:
```php
Route::view('/{any?}', 'app')
    ->where('any', '^(?!admin|vendor|api|filament|storage|build).*$');
```
*Это позволит постепенно перевести публичные страницы, не ломая `/api`, `/vendor`, `/admin`, Filament и assets.*

### Этап 5. Перенести состояние корзины в Pinia
Это один из ключевых этапов, потому что мобильная PWA сильно зависит от корректной работы корзины.

#### Что перенести:
Из текущей JS-логики нужно выделить:
* Добавление товара;
* Изменение количества;
* Удаление товара;
* Подсчёт `totals`;
* `multi-vendor` ограничения (нельзя добавлять товары из разных ресторанов в одну корзину);
* `localStorage`/`IndexedDB` persistence (сохранение состояния);
* Синхронизация корзины с API;
* Offline сценарии.

В API уже есть endpoint `POST /api/v1/cart/sync`. Значит, Vue cart store может работать так:

```text
Pinia cart store
├── state: items, vendorId, totals, syncStatus
├── actions:
│   ├── addItem()
│   ├── removeItem()
│   ├── updateQuantity()
│   ├── clear()
│   ├── sync()
│   └── restoreFromStorage()
└── persistence:
    ├── localStorage (для быстрого восстановления)
    └── IndexedDB (для offline-очереди при необходимости)
```

### Этап 6. Перенести каталог и меню

#### Первым переносить:
1. Главную страницу.
2. Список ресторанов.
3. Страницу ресторана.
4. Категории меню.
5. Карточки продуктов.
6. Product modal / details.

> [!NOTE]
> **Почему:** API для ресторанов и меню уже есть. `MenuController` уже отдаёт категории, продукты и фильтры, а продукты идут с pagination metadata.

#### Vue-страницы:
```text
pages/
├── HomePage.vue
├── RestaurantsPage.vue
├── RestaurantMenuPage.vue
├── ProductPage.vue
└── SearchPage.vue
```

> [!IMPORTANT]
> **Важно:** Не копировать Blade-логику один-в-один. Лучше сделать frontend-driven flow:

```text
RestaurantMenuPage.vue
├── loadRestaurant(slug)
├── loadMenu(slug, categoryId?)
├── render category tabs
├── render product grid/list
└── add to Pinia cart
```

### Этап 7. Перенести checkout с Livewire на Vue
Checkout сейчас завязан на Livewire-маршрут `CheckoutWizard`. Это лучше заменить на Vue multi-step flow.

#### Целевая структура:
```text
pages/CheckoutPage.vue
components/checkout/
├── CheckoutCartStep.vue
├── CheckoutAddressStep.vue
├── CheckoutDeliveryStep.vue
├── CheckoutPaymentStep.vue
├── CheckoutConfirmStep.vue
└── CheckoutSuccessState.vue
```

#### Логика отправки заказа:
Сейчас заказ отправляется через browser event `submit-order` и `fetch('/api/v1/orders')`. В Vue лучше заменить это на прямой action:
```javascript
await ordersStore.submitOrder(payload);
```

**И внутри action:**
```text
ordersStore.submitOrder()
├── generate idempotency key (генерация ключа идемпотентности)
├── attach vendor id (привязка ID ресторана)
├── POST /api/v1/orders
├── on success clear cart (при успехе очистить корзину)
├── redirect to success page (редирект на страницу успеха)
└── on network fail save pending order (при сбое сети сохранить отложенный заказ)
```

### Этап 8. Перенести auth / profile / orders
В `routes/web.php` сейчас login/register/logout остаются web-маршрутами. Для PWA есть два варианта.

#### Вариант A — быстрее
Оставить Laravel Blade login/register, а Vue использовать только после авторизации.
* **Плюсы:**
  * Быстрее в реализации;
  * Меньше рисков безопасности;
  * CSRF/session уже полностью работают из коробки.
* **Минусы:**
  * UX менее «мобильный»;
  * Сложнее потом упаковывать в mobile shell (Capacitor/Cordova).

#### Вариант B — правильнее для PWA/mobile
Сделать Vue-экраны для login/register/profile и использовать Sanctum SPA auth.
* **Плюсы:**
  * Единый бесшовный mobile UX;
  * Гораздо проще для последующей сборки под iOS/Android (Capacitor);
  * Auth state полностью управляется в Pinia.
* **Минусы:**
  * Требует аккуратной настройки CSRF, cookie-сессий, CORS и Sanctum stateful domains.

> [!TIP]
> **Рекомендация:** Начните с **Варианта A**, а затем перейдите к **Вариант B**, когда каталог, корзина и checkout будут полностью стабилизированы.

### Этап 9. Привести PWA в рабочее состояние
Перед мобильной упаковкой нужно исправить PWA-слой.

#### Что обязательно сделать:

1. **Убрать принудительный unregister service worker.**
   Сейчас layout на каждой загрузке unregister-ит все service workers и удаляет caches. Для PWA это критический блокер.
   
2. **Включить регистрацию service worker в Vue app.**
   В `resources/js/app.js` регистрация service worker сейчас закомментирована.
   
3. **Синхронизировать manifest.**
   Есть `public/manifest.json` с PWA-настройками и иконками. Но в `routes/web.php` дополнительно есть route `/manifest.json`, который отдаёт другой JSON и другие пути к иконкам. Нужно оставить один источник истины (лучше статический файл).
   
4. **Проверить service worker cache strategy.**
   Сейчас navigation-запросы кэшируются, а fallback идёт на `/offline`. API-запросы обрабатываются по стратегии `NetworkFirst`. Это хорошая база, но её нужно адаптировать под Vue routes и assets.
   
5. **Проверить background sync заказов.**
   В service worker есть sync-обработчик для `order-sync` и `menu-sync`. Отложенные заказы хранятся в IndexedDB `pendingOrders`, а menu cache — в `menuCache`.

### Этап 10. Перенести offline / order sync в Vue-friendly архитектуру
Сейчас offline sync живёт частично в `resources/js/app.js`, частично в `public/sw.js`. Нужно сделать понятные и четкие границы:

```text
Vue app
├── detects online/offline (детектирует статус сети)
├── saves pending order to IndexedDB (сохраняет отложенный заказ)
├── registers background sync (регистрирует фоновую синхронизацию)
└── shows user state (отображает статус пользователю)

Service Worker
├── serves offline fallback (отдает офлайн-заглушку)
├── caches shell/assets/API GET (кэширует статику и GET-запросы к API)
├── processes pending orders (обрабатывает очередь отложенных заказов)
└── posts messages back to clients (отправляет уведомления обратно клиенту)
```

*Service worker уже умеет синхронизировать `pending orders` с `/api/v1/orders`, передавая заголовки `X-Vendor-ID` и `X-Idempotency-Key`. После успешной синхронизации он удаляет сохраненный заказ, отправляет сообщение клиентам и показывает push-уведомление.*

### Этап 11. Настроить тестирование миграции
Текущие скрипты уже позволяют запускать сборку и тесты:

#### Минимальный набор проверок после каждого этапа:
```bash
npm run build
npm run test
composer test
composer lint
```

#### Что необходимо добавить для Vue:
* **Unit tests для Pinia stores:**
  * `cart store` (проверка добавления, удаления, multi-vendor);
  * `orders store` (проверка отправки и генерации UUID);
  * `auth store`;
  * `offline queue`.
* **Component tests (компонентное тестирование):**
  * Карточка продукта (`ProductCard`);
  * Боковая корзина / страница корзины (`CartDrawer` / `CartPage`);
  * Шаги оформления (`CheckoutSteps`);
  * Меню ресторана (`RestaurantMenu`).
* **E2E (сквозные сценарии):**
  1. Открыть главную страницу;
  2. Выбрать ресторан;
  3. Добавить товар в корзину;
  4. Оформить заказ;
  5. Проверить offline fallback (имитация отключения сети);
  6. Проверить installability PWA (корректность манифеста и SW).

### Этап 12. Постепенно удалить старый frontend-код
Удалять старые Blade/Livewire части нужно только после того, как Vue-экран полностью заменил соответствующую функциональность.

#### Порядок удаления:

1. **Старые Blade views публичной части:**
   * `home.blade.php`
   * `restaurants/*.blade.php`
   * `cart.blade.php`
   * `orders/index.blade.php`
   * `profile/edit.blade.php`
   * `order/tracking.blade.php`
   * `order/success.blade.php`

2. **Livewire-компоненты публичной части:**
   * `checkout wizard`
   * `cart drawer`
   * `geo selector` (если будет переписан на Vue)

3. **Alpine-specific код:**
   * `CartAlpine`
   * `x-data="cartManager"` из layout после перехода на Vue (сейчас он установлен на тег `<body>`).

4. **React-зависимости из `package.json`:**
   * `react`
   * `react-dom`
   * `lucide-react`

---

## Рекомендуемая последовательность миграции по спринтам

### Спринт 1 — Подготовка инфраструктуры
* **Цель:** Vue установлен, SPA shell работает, старый сайт не сломан.
* **Задачи:**
  * Установить Vue, Vue Router, Pinia;
  * Добавить `@vitejs/plugin-vue`;
  * Создать `resources/js/vue/App.vue`;
  * Создать `resources/views/app.blade.php`;
  * Добавить временный route `/app/{any}`;
  * Подключить базовый router;
  * Настроить API client;
  * Проверить `npm run build`.

### Спринт 2 — Каталог
* **Цель:** Vue показывает главную, рестораны и меню через API.
* **Задачи:**
  * Сделать `HomePage.vue`;
  * Сделать `RestaurantsPage.vue`;
  * Сделать `RestaurantMenuPage.vue`;
  * Подключить API-запросы:
    * `restaurants`
    * `restaurant details`
    * `menu`
    * `product details`
  * Сделать loading/error/empty состояния;
  * Добавить skeleton UI.

### Спринт 3 — Корзина
* **Цель:** Vue cart store полностью заменяет текущую клиентскую корзину.
* **Задачи:**
  * Создать `stores/cart.js`;
  * Перенести `localStorage`/`IndexedDB` persistence;
  * Перенести `multi-vendor` проверки;
  * Сделать cart drawer/page;
  * Подключить `POST /api/v1/cart/sync`;
  * Написать unit-тесты для cart store.

### Спринт 4 — Checkout
* **Цель:** Checkout работает без Livewire.
* **Задачи:**
  * Создать `CheckoutPage.vue`;
  * Разбить checkout на шаги;
  * Перенести mapping для отправки заказа;
  * Подключить `POST /api/v1/orders`;
  * Перенести `idempotency key` (ключ идемпотентности);
  * Сделать success/error flows;
  * Сделать fallback-вариант для offline order.

### Спринт 5 — Auth, Profile, Orders
* **Цель:** Пользовательские разделы работают в Vue SPA.
* **Задачи:**
  * Сделать `auth store`;
  * Подключить текущий login/session flow или Sanctum SPA;
  * Сделать profile page;
  * Сделать список заказов (`orders list`);
  * Сделать детали заказа (`order details`);
  * Сделать отслеживание заказа (`order tracking`);
  * Проверить guest signed tracking, если он нужен.

### Спринт 6 — PWA stabilization
* **Цель:** Приложение реально installable и offline-ready.
* **Задачи:**
  * Убрать unregister service worker из общего layout;
  * Включить регистрацию service worker;
  * Убрать конфликт между `public/manifest.json` и Laravel route `/manifest.json`;
  * Проверить иконки, maskable icons, theme color;
  * Проверить offline страницу;
  * Проверить background sync;
  * Проверить push subscribe/unsubscribe endpoints.

### Спринт 7 — Переключение production-маршрутов
* **Цель:** Vue становится основной публичной частью.
* **Задачи:**
  * Перенести SPA route с `/app/*` на `/`;
  * Исключить из SPA fallback:
    * `/api/*`
    * `/admin/*`
    * `/vendor/*`
    * `/filament/*`
    * `/storage/*`
    * `/build/*`
  * Оставить Filament/vendor panel как есть;
  * Проверить работоспособность deep links (прямых ссылок):
    * `/restaurants/:slug`
    * `/cart`
    * `/checkout`
    * `/orders`
    * `/profile`
    * `/order/:id/track`

### Спринт 8 — Мобильное приложение
После того как PWA стабилизирована, можно делать мобильную упаковку под iOS и Android.

#### Вариант 1: Capacitor
Подходит, если нужны:
* Push-уведомления;
* Native splash screen (заставка);
* Status bar control;
* Доступ к native API (filesystem/camera/geolocation);
* Полноценная публикация в App Store / Google Play.

**План действий:**
```bash
npm install @capacitor/core @capacitor/cli
npx cap init RestoPWA com.yourcompany.restopwa
npm install @capacitor/android @capacitor/ios
npx cap add android
npx cap add ios
npm run build
npx cap sync
```

#### Вариант 2: Trusted Web Activity (TWA) для Android
Подходит, если приложение остаётся вебом, а нужна в основном Android-публикация.
* **Плюсы:**
  * Проще в настройке;
  * Минимум native-кода;
  * Использует реальный PWA URL.
* **Минусы:**
  * Слабее контроль над устройством;
  * Не решает проблему с iOS.

> [!TIP]
> **Моя рекомендация:** Для твоего проекта я бы выбрал **Capacitor**, потому что у тебя уже есть push/offline/order-sync логика, а позже могут понадобиться native notifications и более контролируемый mobile UX.

---

## Главные риски миграции

### 1. Auth и Sanctum
API уже использует `auth:sanctum` для заказов, истории заказов и push. Если Vue будет работать как SPA на том же домене — это проще. Если mobile shell будет открывать API с другого origin — нужно аккуратно настроить CORS, session domain и Sanctum stateful domains.

### 2. Дублирование manifest
Сейчас есть `public/manifest.json`, но также Laravel route `/manifest.json` отдаёт manifest из PHP. Это может привести к странному поведению PWA. Нужно оставить один manifest.

### 3. Service worker сейчас фактически отключается
Даже если `public/sw.js` написан, текущий layout удаляет регистрации service worker и caches. Это нужно исправить до любых mobile/PWA тестов.

### 4. Checkout нельзя переписывать «на глаз»
Checkout — критичный поток. Сейчас отправка заказа использует CSRF, vendor id и idempotency key. При переносе во Vue это обязательно сохранить, иначе появятся дубли заказов, проблемы с vendor context и ошибки авторизации.

---

## Итоговая рекомендация
Я бы делал так:
1. **Не переписывать backend.** Laravel уже подходит как отличный API/backend.
2. **Не трогать Filament/vendor panel** на первом этапе.
3. **Добавить Vue параллельно**, настроив роутинг через `/app/*`.
4. **Сначала перенести каталог и корзину**, потому что API уже полностью готов.
5. **Потом перенести checkout**, строго сохранив idempotency и offline flow.
6. **Потом auth/profile/orders.**
7. **После этого включить нормальный PWA service worker.**
8. **Только после стабильной PWA делать Capacitor/mobile build.**