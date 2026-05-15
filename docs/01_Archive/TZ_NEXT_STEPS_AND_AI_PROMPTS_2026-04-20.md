# ТЗ: что делать дальше по RestoPWA + набор промптов для AI

**Дата:** 2026-04-20  
**Статус:** рабочий документ для следующего этапа (P0 Stabilization → P1 Hardening)

---

## 1) Краткий вывод по текущему состоянию

Проект уже имеет сильную основу:
- Laravel 13 + Filament 5 + Livewire 4 заявлены и подключены.
- Реализованы базовые PWA-сценарии, офлайн-очередь заказов, tenant-контекст и трекинг заказа.
- Есть feature/unit тесты и Dusk-каркас.

Но перед активным масштабированием есть **архитектурные разрывы в критическом checkout-пути**, которые нужно закрыть в первую очередь.

---

## 2) Подтверждённые GAP (по коду)

## GAP-1 (P0): mismatch push endpoints

**Симптом:**
- Фронтенд отправляет подписку на `/api/v1/push/subscribe` и `/api/v1/push/unsubscribe`.
- Бэкенд зарегистрировал только `/api/push/subscribe` и `/api/push/unsubscribe`.

**Риск:** push-функция в реальном UI не проходит end-to-end.

**Требование:** унифицировать контракт на уровне API и фронта + покрыть feature-тестами.

---

## GAP-2 (P0): offline submit конфликтует с auth-only order creation

**Симптом:**
- Заказы отправляются на `/api/v1/orders` из frontend и service worker.
- Endpoint создания заказа расположен в web-роутах и защищен middleware `auth`.
- Контроллер `OrderController::store` дополнительно возвращает 401, если `$request->user()` пустой.

**Риск:** offline replay и часть guest/невалидно авторизованных сценариев будут стабильно падать в 401.

**Требование:** принять архитектурное решение по checkout-модели (auth-only / guest / hybrid) и привести offline contract в соответствие.

---

## GAP-3 (P0): API-контур заказов фрагментирован

**Симптом:**
- Создание заказа реализовано через `web.php` c путём `/api/v1/orders`.
- Чтение заказов (index/show) живёт в `routes/api.php` под `auth:sanctum`.

**Риск:** разный middleware stack и правила доступа для похожих API-операций усложняют сопровождение и интеграции.

**Требование:** собрать order API в единый контракт (одна зона маршрутов + единая auth-стратегия + единая схема ошибок).

---

## GAP-4 (P1): quality gate описан, но локально не воспроизводится без bootstrap-зависимостей

**Симптом:**
- `php artisan test` не запускается в чистой среде без `vendor/autoload.php`.

**Риск:** нестабильный вход в проект для новой команды/AI-агента.

**Требование:** зафиксировать обязательный bootstrap (`composer install`, `npm install`) и добавить short-run check profile для быстрого preflight.

---

## 3) Целевое ТЗ на ближайший этап

## Этап A (P0): Checkout Stabilization — 5 рабочих дней

### Цель
Закрыть разрывы в критическом пути: checkout, offline replay, push subscribe.

### Задачи
1. **Route/API унификация push**
   - Выбрать canonical endpoint (рекомендуется `/api/v1/push/*`).
   - Обновить backend routes или frontend вызовы.
   - Добавить feature-тесты subscribe/unsubscribe (200/401/validation).

2. **Единая модель авторизации заказа**
   - Зафиксировать ADR: auth-only / guest signed / hybrid.
   - Привести `POST /api/v1/orders` + SW replay + CartAlpine sync к одному сценарию.
   - Обеспечить idempotency при повторной отправке очереди.

3. **Консолидация order API**
   - Убрать split между web/api группами для order endpoints.
   - Нормализовать middleware: tenant-context + auth strategy + throttling.
   - Нормализовать формат ошибок через единый response contract.

4. **Regression tests на критический путь**
   - Online submit (happy path).
   - Offline queue replay после reconnect.
   - 401/403 сценарии доступа.
   - Push subscribe contract.

### Критерии приёмки
- Push subscribe/unsubscribe работают из UI без 404.
- Offline заказ уходит автоматически после online в целевом сценарии.
- Нет расхождения маршрутов `orders` между web и api пространствами.
- Минимальный smoke test pack зелёный.

---

## Этап B (P1): Hardening — 1 спринт

### Цель
Сделать контур эксплуатационно надёжным.

### Задачи
1. Ввести release gate (lint + tests + build + smoke).
2. Добавить throttling/rate-limit для публичных API.
3. Ввести telemetry событий checkout funnel (submit start/success/fail, sync fail, push fail).
4. Зафиксировать runbook инцидентов checkout/offline.

### Критерии приёмки
- Есть формализованный CI gate с блокирующими этапами.
- Есть минимум один дашборд по конверсии checkout и offline sync.

---

## Этап C (P2): Product Growth — по roadmap

1. Realtime tracking (SSE/WebSocket).
2. Платежные интеграции как отдельный bounded context.
3. Vendor-аналитика и отчётность.

---

## 4) Готовые AI-промпты (использовать по порядку)

## Prompt 1 — Архитектурное решение checkout/auth/offline

```text
Ты Principal Laravel Architect в проекте RestoPWA (Laravel 13, Livewire 4, Filament 5, PWA).
Нужно подготовить ADR для checkout:
1) Сравни 3 модели: auth-only, guest-signed-url, hybrid.
2) Для каждой оцени: безопасность, UX, сложность реализации, миграционные риски.
3) Предложи целевой API-контракт для:
   - POST /api/v1/orders
   - offline replay из service worker
   - tracking (auth + guest signed)
4) Дай решение с планом внедрения без даунтайма.
Формат: таблица + финальное Decision + migration plan по шагам.
```

## Prompt 2 — Backend patch (маршруты + middleware + тесты)

```text
Ты senior Laravel developer.
Сделай patch-ready изменения для P0 stabilization:
- устранить mismatch push endpoints между frontend и backend;
- консолидировать order API маршруты (создание/чтение в едином контракте);
- привести middleware к единой модели (tenant + auth + throttle);
- не ломать текущие экраны.
Обязательно:
- добавить/обновить feature тесты;
- сохранить idempotency на create order;
- использовать существующие ApiResponses.
В ответе: 1) список файлов, 2) дифф по файлам, 3) команды проверки.
```

## Prompt 3 — Frontend/PWA patch (offline-replay)

```text
Ты senior Frontend/PWA engineer.
Нужно исправить checkout/offline поток:
- привести frontend endpoints к backend контракту;
- стабилизировать retry pending orders в CartAlpine + service worker;
- корректно обрабатывать 401/403/409 и показывать понятный UX.
Ограничения:
- не ломать текущий UI;
- сохранить offline-first поведение;
- не дублировать отправку при повторных reconnect.
В ответе:
1) patch-ready код,
2) edge-case matrix,
3) ручной smoke-checklist.
```

## Prompt 4 — QA smoke matrix + автотесты

```text
Ты Staff QA Automation.
Собери smoke-пак для RestoPWA (критический путь заказа):
- online checkout happy path;
- offline queue + auto replay;
- push subscribe/unsubscribe contract;
- tracking access control (owner, guest signed, tampered signature).
Для каждого кейса укажи:
- ID, приоритет, preconditions, шаги, ожидаемый результат,
- тип теста (Feature/Browser/Unit),
- merge-gate (обязателен/необязателен).
После матрицы предложи минимальный набор тестов для CI до 10 минут.
```

## Prompt 5 — Security review перед merge

```text
Ты Application Security Engineer.
Проведи security-review изменений checkout/tracking/push:
- authN/authZ consistency;
- tenant isolation;
- signed URL misuse;
- replay/rate-limit/abuse;
- утечки чувствительных данных в логах.
Верни:
1) Blocking issues,
2) High/Medium findings,
3) remediation steps,
4) regression-tests на каждую критичную проблему.
Формат: таблица severity -> finding -> fix -> test.
```

## Prompt 6 — Release plan

```text
Ты Release Manager.
Сделай go-live план для P0/P1 изменений:
- rollout strategy (feature flags/canary);
- rollback strategy;
- мониторинг первые 24 часа;
- incident playbook для checkout/offline/push.
Нужен чеклист по ролям: backend, frontend, QA, DevOps, Product.
```

---

## 5) Что делать прямо сейчас (next actions)

1. Принять ADR-решение по checkout модели (Prompt 1).
2. В этом же инкременте закрыть push mismatch и API-консолидацию (Prompt 2 + Prompt 3).
3. Зафиксировать merge-gate smoke набор (Prompt 4).
4. Перед релизом выполнить security review и go-live план (Prompt 5 + Prompt 6).

---

## 6) DoR для запуска работ

Задача считается готовой к реализации, если:
- зафиксирован единый контракт по `/api/v1/orders` и `/api/v1/push/*`;
- определена модель checkout auth;
- согласованы обязательные smoke тесты merge-gate;
- назначены owner’ы для backend/frontend/qa.
