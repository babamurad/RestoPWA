# Release Candidate (RC) Checklist

Этот чеклист должен быть полностью пройден перед развертыванием RC на Production и после него.

## 1. Pre-Deployment (До релиза)
- [ ] Code Freeze объявлен, в RC-ветку принимаются только критичные багфиксы.
- [ ] Одобрен [Release DoD](release_dod.md).
- [ ] Пройден регресс согласно [Матрице критических тестов](checkout_test_matrix.md).
- [ ] Выполнен бэкап Production базы данных (сохранить дамп локально или в S3).
- [ ] Утвержден план отката:
  - Команда отката миграции: `php artisan migrate:rollback --step=N` (где N - количество новых миграций в релизе).
  - Инструкция сброса кеша PWA, если фронт сломается.
- [ ] Протестирован сам скрипт развертывания (из `tz.txt` или CI/CD), команды очистки кешей работают корректно без падений.

## 2. Deployment (Развертывание)
- [ ] Запуск maintenance mode: `php artisan down --secret=release_bypass` (опционально, если релиз с downtime).
- [ ] Pull кода на Production (`git pull`).
- [ ] Установка зависимостей (`composer install --no-dev --optimize-autoloader`).
- [ ] Запуск миграций (`php artisan migrate --force`).
- [ ] Очистка кэшей (`php artisan optimize:clear` и `php artisan optimize`).
- [ ] Выключение maintenance mode: `php artisan up`.

## 3. Post-Deployment Monitoring (72 часа после релиза)
- [ ] **Sentry / Logs Tracker**: Отсутствие новых всплесков 500 ошибок в процессе чекаута.
- [ ] **Business Metrics**: Успешные заказы проходят, отказы по причинам `outside_delivery_zone` и `invalid_total` находятся в пределах нормы.
- [ ] **Оффлайн синхронизация**: Отсутствуют массовые retries запросов к `/api/v1/orders` в логах серверов.
- [ ] **Performance**: Время ответа `/api/v1/orders` стабильно (мониторинг New Relic, Datadog или логов Nginx).

## 4. Экстренный откат (В случае инцидента P0)
- [ ] Если функционал заказа полностью сломан — выполнить откат коммита/ветки в Git.
- [ ] Выполнить откат миграции.
- [ ] Очистить кеши и пересобрать фронтенд (если требуется).
- [ ] Оповестить стейкхолдеров о статусе отката.
