# GAP-анализ проекта RestoPWA + ТЗ на доработку + AI-промпты

**Дата:** 2026-04-10  
**Автор:** AI-аудит (senior Laravel perspective)  
**Цель:** зафиксировать, какого функционала не хватает в текущей реализации, и дать пошаговое ТЗ + готовые промпты для AI-команды.

---

## 1) Что уже реализовано (в проекте сейчас)

1. Базовый клиентский путь заказа: каталог → корзина → checkout → success/tracking.
2. Tenant isolation через middleware (`SetTenantContext`, `ensure.tenant`) и scope-модель.
3. Offline-first корзина и очередь pending-заказов через IndexedDB + Service Worker.
4. История статусов заказа и vendor-операции (accept/cancel/update-status).
5. Админ-контур (Filament ресурсы).

---

## 2) Ключевые функциональные пробелы (GAP)

## P0 (критично для стабильности/продакшна)

### GAP-P0-1: Несогласованность API-роутов push-подписки
- Фронтенд отправляет запросы на `/api/v1/push/subscribe` и `/api/v1/push/unsubscribe`.
- Бэкенд зарегистрировал роуты как `/api/push/subscribe` и `/api/push/unsubscribe` (без `v1`).
- Итог: push-подписка фактически не работает из UI.

### GAP-P0-2: Нет полноценного guest checkout
- API создания заказа требует authenticated user (иначе 401).
- Страница checkout также защищена `auth` middleware.
- В результате оффер «гостевого» заказа не реализован end-to-end.

### GAP-P0-3: Offline sync в SW отправляет заказ без auth-контекста
- В `sw.js` offline-ресабмит идет через `fetch('/api/v1/orders', ...)` и CSRF, но backend endpoint ориентирован на authenticated user.
- Высокий риск массовых 401 при автоматической отправке очереди offline-заказов.

### GAP-P0-4: Платежи обозначены в UI, но фактически stub
- Есть выбор метода оплаты (card/cash/sbp), но `processPayment()` — заглушка.
- Нет payment lifecycle: pending/authorized/paid/failed/refunded, нет callback/webhook потока.

## P1 (важно для операционной зрелости)

### GAP-P1-1: Отсутствует SLA-уровень observability
- Нет системной телеметрии для checkout-funnel, offline-retry, отказов push, ошибок tenant resolution.
- Нет дашбордов по конверсии и failed checkout rate.

### GAP-P1-2: Нет полноценных уведомлений клиенту о статусах заказа
- Есть инфраструктура push, но нет стабильного продуктового контура (сегментация статусов, retry policy, дедупликация, fallback канал).

### GAP-P1-3: Нет явной политики rate-limit/anti-abuse на публичные API
- Для публичных endpoint’ов меню/каталога/checkout не описаны антифрод и throttling-ограничения.

### GAP-P1-4: Отсутствует целостный CI quality gate уровня релиза
- Есть тесты, но нет формализованного release gate: обязательный набор smoke + performance budget + security checks.

## P2 (улучшения продукта)

### GAP-P2-1: Нет realtime-трекинга (WebSocket/SSE)
- Сейчас фактически polling/ручное обновление.

### GAP-P2-2: Нет пользовательских сценариев лояльности
- Промокоды, бонусы, персональные офферы, retention-механики отсутствуют.

### GAP-P2-3: Нет бизнес-аналитики для vendor/admin
- Недостаточно отчетов: AOV, conversion, отмены по причинам, SLA кухни/доставки.

---

## 3) ТЗ на реализацию (implementation plan)

## Этап 1 (P0 Stabilization, 5–7 дней)

### Цели
- Восстановить корректный push-flow.
- Синхронизировать модель авторизации checkout/offline.
- Устранить архитектурный разрыв между UI и backend по гостевому сценарию.

### Задачи
1. **Push route alignment**
   - Выбрать единый контракт: либо перевод фронта на `/api/push/*`, либо версионировать backend до `/api/v1/push/*`.
   - Добавить feature-тесты на subscribe/unsubscribe.
2. **Checkout auth strategy**
   - Выбрать один из режимов:
     - A) Только auth checkout (и убрать guest-flow из требований/UI),
     - B) Полноценный guest checkout с signed tracking URL.
   - Реализовать выбранную модель end-to-end.
3. **Offline order sync contract**
   - Для offline очереди добавить поддерживаемый backend-сценарий:
     - либо tokenized guest submit,
     - либо гарантированный user session refresh перед sync,
     - либо отдельный endpoint для idempotent replay.
4. **Payment baseline**
   - Зафиксировать state machine платежей.
   - Реализовать минимум: `cash` как fully supported, `card/sbp` как disabled/feature-flagged до интеграции.

### Критерии приемки
- Push subscription проходит успешно из браузера (200/201), и запись видна в БД.
- Offline-заказ после восстановления сети уходит без ручных костылей в 95%+ кейсов.
- Нет конфликтов между заявленным guest-flow и реальной авторизацией.

## Этап 2 (P1 Hardening, 1–2 спринта)

### Цели
- Усилить надежность и безопасность на прод-уровне.

### Задачи
1. **Observability**: событийная модель + метрики по checkout funnel.
2. **Security hardening**: throttle, abuse protection, audit logging по чувствительным endpoint’ам.
3. **Release gates**: CI pipeline с блокирующими stage (test, lint, smoke, security, build).
4. **Notification reliability**: retry/dead-letter policy и fallback-канал (email/SMS, если push недоступен).

### Критерии приемки
- Есть дашборд с конверсией checkout и отказами по шагам.
- Есть формальный gate для релиза с порогами (pass/fail).

## Этап 3 (P2 Product Growth)

### Цели
- Улучшить UX и коммерческие метрики.

### Задачи
1. Realtime tracking (SSE/WebSocket).
2. Loyalty stack: промокоды, бонусы, кампании.
3. Vendor analytics: отчеты и экспорт.

---

## 4) Формат бэклога (готово к постановке в Jira/Linear)

Для каждой задачи заводить поля:
- `Problem`
- `User impact`
- `Scope`
- `Acceptance criteria`
- `Risks`
- `Estimate (S/M/L)`
- `Owner`
- `Dependencies`

---

## 5) Набор AI-промптов для реализации

Ниже промпты сделаны под поэтапную работу: архитектура → реализация → тесты → ревью.

### Prompt A — Architecture/Decision Record

```text
Ты Principal Laravel Architect. Проект: RestoPWA (Laravel 13, Livewire 4, PWA).
Нужно подготовить ADR по checkout/auth/offline-sync:
1) Сравни 3 варианта: auth-only checkout, guest checkout + signed URL, hybrid.
2) Для каждого: безопасность, UX, сложность, риски, миграция.
3) Дай финальную рекомендацию с аргументацией.
4) Опиши target API contract и последовательность миграции без даунтайма.
Формат: таблица + финальный Decision + список migration steps.
```

### Prompt B — Backend Implementation (Laravel)

```text
Ты senior Laravel developer.
Сделай patch-ready реализацию для P0:
- унификация push routes между frontend и backend;
- стабилизация offline order replay;
- корректная idempotency обработка на create order;
- при необходимости signed guest tracking URL.
Требования:
- строгая совместимость с текущим кодстайлом,
- покрыть feature/unit тестами,
- не вносить лишний рефакторинг.
В ответе:
1) список файлов,
2) изменения по файлам,
3) команды для проверки.
```

### Prompt C — Frontend/PWA Integration

```text
Ты senior Frontend/PWA engineer.
Обнови интеграцию checkout + service worker:
- исправь API endpoint mismatch,
- обеспечь надежную отправку pending orders после reconnect,
- добавь user-friendly UX для конфликтов синхронизации и ошибок auth.
Ограничения:
- без изменения публичного UX без причины,
- сохранить offline-first поведение.
Нужны:
1) patch-ready код,
2) матрица edge-cases,
3) ручной smoke checklist.
```

### Prompt D — Test Engineering / QA

```text
Ты Staff QA Automation.
Сформируй тест-стратегию и реализуй smoke pack для:
- order submit online/offline,
- idempotency,
- push subscribe/unsubscribe,
- tracking access control (owner/guest signed URL/tampered URL).
Для каждого кейса укажи:
- приоритет,
- preconditions,
- шаги,
- expected result,
- тип теста (feature/browser/unit).
Добавь команды запуска в CI.
```

### Prompt E — Security Review

```text
Ты Application Security Engineer.
Проведи security review изменений checkout/tracking/push:
- authn/authz,
- tenant isolation,
- signed URL misuse,
- replay/abuse/rate-limiting,
- sensitive logging.
Верни:
1) Blocking issues,
2) High/Medium findings,
3) конкретные remediation steps,
4) regression tests на каждую критичную уязвимость.
```

### Prompt F — Release Manager / Go-Live

```text
Ты Release Manager.
Подготовь go-live plan для P0/P1 изменений:
- rollout strategy (feature flags/canary),
- rollback plan,
- monitoring checklist на первые 24 часа,
- incident playbook.
Нужен четкий чеклист по ролям (backend/frontend/qa/devops/product).
```

---

## 6) Рекомендуемая последовательность применения AI-промптов

1. Prompt A (архитектурное решение)
2. Prompt B (backend реализация)
3. Prompt C (frontend/PWA реализация)
4. Prompt D (тесты)
5. Prompt E (безопасность)
6. Prompt F (релиз)

---

## 7) Definition of Ready для старта работ

- Зафиксирован decision по checkout модели (auth-only / guest / hybrid).
- Согласован единый API контракт push и order submit.
- Подтверждено, какие платежные методы реально поддерживаются в этом инкременте.
- Утверждены критерии релизного gate и owner’ы метрик.

