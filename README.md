# RestoPWA — доставка еды

Платформа доставки еды для нескольких ресторанов: клиентское приложение (PWA и Android через Capacitor), панель ресторана, панель администратора и раздел курьера.

## Что внутри

| Часть | Где | Для кого |
|---|---|---|
| Клиентское приложение | `/` — Vue 3, работает офлайн | клиенты |
| Раздел курьера | `/courier` — в том же приложении | курьеры |
| Панель ресторана | `/vendor` — Filament | рестораны |
| Панель администратора | `/admin` — Filament | платформа |

Основные возможности: меню и корзина с офлайн-режимом, оформление заказа с проверкой зоны и цены доставки по районам, самовывоз, оплата наличными, канбан заказов, автоназначение курьеров, отслеживание заказа, отзывы, аналитика, два языка (русский и туркменский).

## Технологии

- **Сервер:** PHP 8.3, Laravel 13, Filament 5, Livewire 4, Sanctum, Reverb (websocket).
- **База:** PostgreSQL с PostGIS на проде; тесты идут на SQLite.
- **Клиент:** Vue 3, Vue Router, Pinia, vue-i18n, Tailwind CSS 4, Vite 8, Dexie (IndexedDB), Capacitor (Android).
- **Тесты:** PHPUnit, Vitest.

## Быстрый старт

Нужны PHP 8.3+, Composer и Node.js 22+.

```bash
composer setup
```

```bash
composer dev
```

Первая команда ставит зависимости, создаёт `.env`, выполняет миграции и собирает фронтенд; вторая запускает сервер, очередь, логи и Vite. Подробнее — в [docs/02_Reference/getting-started.md](docs/02_Reference/getting-started.md).

Создать администратора: `php artisan admin:create`.

## Проверки

```bash
composer check
```

```bash
npm test
```

`composer check` запускает Laravel Pint и PHPUnit, `npm test` — Vitest. То же самое выполняет CI на каждый pull request.

Сквозные тесты в браузере (Laravel Dusk) идут отдельно: им нужны Chrome, собранный фронтенд и запущенное приложение — пошаговая инструкция в [docs/testing-e2e.md](docs/testing-e2e.md). В CI они выполняются заданием «E2E (Dusk)».

> [!IMPORTANT]
> Любое изменение в оформлении заказа, статусах и деньгах (`Order`, `Payment`, комиссии, выплаты) сопровождается автотестом. См. [матрицу критических тестов](docs/00_Active/checkout_test_matrix.md).

## Документация

- [Что осталось до запуска](docs/00_Active/TZ_REMAINING_2026-10-19.md)
- [Выкладка и разбор инцидентов](docs/00_Active/OPERATIONS_DEPLOY.md)
- [Очереди и фоновые процессы](docs/00_Active/OPERATIONS_QUEUES.md)
- [Sentry и резервные копии](docs/00_Active/OPERATIONS_MONITORING_BACKUPS.md)
- [Контракт API заказа](docs/00_Active/order_contract.md)
- [Геосервис](docs/02_Reference/GEO_SERVICE.md)

## Мобильное приложение (Android)

Адрес сайта, с которым работает приложение, задаётся в `.env` переменной `VITE_MOBILE_API_BASE`.

```bash
npm run cap:sync
```

```bash
npm run cap:open:android
```

## Лицензия

Проприетарное ПО. Все права защищены.
