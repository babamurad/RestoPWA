# RestoPWA Roadmap Q2 2026

_Обновлено: 6 апреля 2026_

## Статус внедрения

| ID | Стадия | Статус |
| :--- | :--- | :--- |
| A | Environment & Quality Gate | Готово |
| B | High-Fidelity Testing & Tenant Audit | Готово |
| C | Frontend Vitest & PWA Baseline | Готово |
| D | Documentation Synchronization | Готово |

---

## Q2 2026 Roadmap

### E1. Real-time Order Tracking (Enhanced Map)

- **Описание**: Мониторинг статуса заказа в реальном времени с интерактивным прогресс-баром.
- **Статус**: Не начато
- **Зависимости**: Laravel Reverb или Pusher WebSocket сервер.
- **Задачи**:
  - [ ] Интеграция Laravel Reverb для WebSocket соединений.
  - [ ] UI компонент: прогресс-бар (Received → Cooking → Delivery → Delivered).
  - [ ] Background sync для push-уведомлений о смене статуса.

### E2. Push Notifications (Production-grade)

- **Описание**: Полноценные push-уведомления для клиентов при закрытом браузере.
- **Статус**: Не начато
- **Зависимости**: VAPID keys, Service Worker push hooks.
- **Задачи**:
  - [ ] VAPID key management в админ-панели.
  - [ ] Service Worker обработка push payload.
  - [ ] UI для opt-in подписки на уведомления.

### E3. Multi-vendor Management App

- **Описание**: Упрощённое PWA-приложение для ресторанов-партнёров (управление заказами).
- **Статус**: Не начато
- **Зависимости**: Аутентификация по QR-коду, vendor-специфичный API.
- **Задачи**:
  - [ ] Dedicated PWA для вендоров.
  - [ ] Dashboard управления заказами.
  - [ ] Интеграция с существующим tenant API.

### F1. Payments & Checkout Polish

- **Описание**: Интеграция платёжных шлюзов и улучшение UX checkout.
- **Статус**: Не начато
- **Задачи**:
  - [ ] Yandex Pay / Stripe / Tinkoff интеграция.
  - [ ] Автозаполнение адреса через Yandex Maps.
  - [ ] Система купонов и лояльности.

### F2. Discovery & Reviews

- **Описание**: Улучшенный поиск ресторанов и система отзывов.
- **Статус**: Не начато
- **Задачи**:
  - [ ] Расширенная фильтрация и поиск.
  - [ ] Система рейтингов и отзывов.
  - [ ] Персонализированные рекомендации.

---

## Технические приоритеты Q2

1. **WebSocket Server** (Laravel Reverb) — основа для real-time функционала.
2. **VAPID Push Infrastructure** — production-ready notifications.
3. **Vendor PWA** — отдельное приложение для B2B.

---

> [!TIP]
> **Приоритет**: Real-time Order Tracking (E1) — следующая ключевая фича для улучшения UX после стабилизации.
