# E2E Smoke Testing (Laravel Dusk)

Этот документ описывает процесс запуска и отладки сквозных (E2E) тестов в проекте RestoPWA.

## Инфраструктура

Мы используем **Laravel Dusk** для тестирования критических путей пользователя. Тесты запускаются в браузере Chrome (в headless режиме в CI).

## Команды запуска

### Локальный запуск
Перед запуском убедитесь, что у вас установлен Chrome и запущен локальный сервер (например, через `.osp` или `php artisan serve`).

```bash
# Запуск всех тестов
php artisan dusk

# Запуск только smoke-тестов (быстрая проверка)
php artisan dusk --group=smoke
```

### Запуск в CI
В CI тесты запускаются автоматически через GitHub Actions (или другой настроенный инструмент) при создании Pull Request.

## Отладка (Triage)

Если тест упал:

1. **Скриншоты и Логи**:
   - Скриншоты падений сохраняются в `tests/Browser/screenshots`.
   - Логи консоли браузера сохраняются в `tests/Browser/console`.

2. **Очистка кэша PWA**:
   Тесты RestoPWA используют макрос `clearPwaCache()`, который очищает:
   - LocalStorage
   - SessionStorage
   - IndexedDB (Dexie)
   Это предотвращает влияние данных предыдущих тестов (flaky tests).

3. **Локальная отладка**:
   Чтобы видеть, что происходит в браузере, отключите headless режим в `DuskTestCase.php` или установите переменную окружения:
   ```bash
   DUSK_HEADLESS_DISABLED=true php artisan dusk
   ```

## Список Smoke-сценариев

- `SMK-01`: Guest happy path checkout — заказ «от меню до подтверждения».
- `SMK-02`: Order status visibility — проверка отображения трекинга.
- `SMK-03`: Empty cart — поведение при пустой корзине.
- `SMK-05`: Guest tracking security — проверка защиты Signed URL.

## Советы по написанию тестов

- Используйте `dusk="..."` атрибуты в Blade-шаблонах для надежных селекторов.
- Избегайте длинных `pause()`, используйте `waitForText()` или `waitForSelector()`.
- При работе с IndexedDB всегда делайте `pause(1000)` или ждите завершения асинхронных операций.
