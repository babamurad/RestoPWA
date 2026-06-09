<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# RestoPWA — Доставка еды (PWA)

RestoPWA — это современная платформа для доставки еды, построенная по принципам **Domain-Driven Design (DDD)**. Платформа поддерживает мультитенантность (один инстанс обслуживает несколько ресторанов) и работает как Progressive Web App (PWA) с поддержкой офлайн-режима.

## Технологический стек

С учетом последних изменений, проект использует следующий современный стек технологий:

### Backend:
- **PHP 8.3+**
- **Laravel 13** — основа бэкенда (с использованием строгой типизации и современных практик).
- **Livewire 4** — для создания реактивных компонентов.
- **Filament 5** — для построения мощных административных панелей.
- **MySQL** — основная реляционная база данных (миграции, Eloquent ORM).

### Frontend (PWA):
- **Vue 3** — ядро клиентского приложения.
- **Vue Router 5** и **Pinia 3** — маршрутизация и управление состоянием.
- **Tailwind CSS 4** — стилизация интерфейсов.
- **Vite 8** — сборщик ресурсов.

### Данные и офлайн:
- **IndexedDB (через Dexie)** — для обеспечения offline-first функционала (например, корзины).

### Тестирование:
- **PHPUnit 12** — юнит- и интеграционное тестирование бэкенда.
- **Vitest 3** — тестирование фронтенд-компонентов.
- **Laravel Dusk 8** — E2E (smoke) тестирование.

## Основные возможности

- **Offline-First Корзина**: Хранение данных в IndexedDB (Dexie) с синхронизацией цен на сервере.
- **PWA Инфраструктура**: Работа через Service Worker, установка на мобильные устройства, фоновая синхронизация заказов.
- **Мультитенантность**: Изоляция данных ресторанов (вендоров) через домены/поддомены и заголовки `X-Vendor-ID`.
- **Real-time Трекинг**: Отслеживание статуса заказа в реальном времени.

## Быстрый старт

Убедитесь, что у вас установлены PHP 8.3+, Composer и Node.js.

```bash
composer setup
```

Подробную инструкцию см. в [docs/getting-started.md](docs/getting-started.md).

## Качество кода

Мы используем **Laravel Pint** для соблюдения стандартов PSR-12 и **PHPUnit** для тестирования.

### Линтинг и юнит-тесты:
```bash
composer check
```

### E2E Smoke тесты (Laravel Dusk):
Для проверки критических путей пользователя (заказ, трекинг) используйте:
```bash
php artisan dusk --group=smoke
```
Подробности см. в [docs/testing-e2e.md](docs/testing-e2e.md).

> [!IMPORTANT]
> **Правило Checkout/Order**: Любой bugfix или изменение в логике оформления заказа (`Checkout`, `Order`, `Payment`) НЕ мержится в главную ветку без покрытия автотестом (Unit или E2E). Подробнее в [Матрице критических тестов](docs/00_Active/checkout_test_matrix.md).

## Лицензия

Проприетарное ПО. Все права защищены.
