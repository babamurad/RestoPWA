# Backlog фиксов после Этапа D

## Найденные ошибки

### D-B1: Миграция order_status_history
- **Проблема**: Таблица `order_status_history` использовала `bigint` для поля `id` вместо `uuid`, что вызывало ошибку привязки с заказами (uuid).
- **Статус**: ✅ Исправлено (миграция `2024_01_01_000015_fix_order_status_history_id_type.php`)
- **Решение**: Изменён тип колонки id на uuid

### D-B2: PushNotificationService требует пакет web-push
- **Проблема**: Класс `Minishlink\WebPush\WebPush` не найден, что вызывало ошибку при создании OrderObserver.
- **Статус**: ✅ Исправлено (nullable зависимость + fallback в AppServiceProvider)
- **Решение**: Добавлен fallback dummy-класс если пакет не установлен

### D-B3: Filament несовместим с Laravel 13
- **Проблема**: Filament 3.x и 4.x не поддерживают Laravel 13 (требует Laravel 10-12).
- **Статус**: ⚠️ Обходное решение
- **Решение**: Используется встроенная админ-панель на Blade

## Рекомендации

### Высокий приоритет
1. Установить пакет `web-push` для push-уведомлений:
   ```bash
   composer require minishlink/web-push
   ```

2. Протестировать полный цикл продаж через фронтенд

### Средний приоритет
1. Добавить валидацию slug для Restaurant/Category/Product
2. Реализовать soft-delete для заказов
3. Добавить экспорт заказов в Excel

### Низкий приоритет
1. Интегрировать Filament когда выйдет совместимая версия
2. Добавить webhook для уведомлений о статусе заказа

## Проверочные данные

```
Администратор:
  Email: admin@restopwa.local
  Password: secret123
  
Тестовый заказ доступен по адресу:
  /admin/orders/{order_id}
```
