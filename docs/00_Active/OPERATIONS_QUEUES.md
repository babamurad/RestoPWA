# Очереди и фоновые процессы на проде

## Зачем
Медленные и внешние операции выполняются в фоне, а не во время HTTP-запроса:
- **push-уведомления и SMS клиенту** о смене статуса заказа. Раньше push отправлялся прямо в запросе ресторатора или курьера, и если push падал, SMS тоже не уходила;
- **истечение предложения курьеру** через 30 секунд и передача заказа следующему курьеру;
- рассылки Reverb, которые помечены для очереди.

Если воркер не запущен, уведомления не уходят, а предложения курьерам не передаются дальше. Минутный планировщик подстрахует предложения, но с задержкой до минуты.

## Как устроено
| Очередь | Задачи | Почему отдельно |
|---|---|---|
| `high` | `ExpireCourierOffer` | у предложения 30 секунд, оно не должно ждать за SMS |
| `default` | `SendOrderStatusNotification` (push + постановка SMS), `SendSmsJob` | внешние сервисы, могут тормозить |

- Подключение: `QUEUE_CONNECTION=database` (таблица `jobs`). Redis не обязателен. Если позже понадобится `redis`, всё настроено так же.
- `after_commit = true`: задача, поставленная внутри транзакции (создание заказа, назначение курьера), уходит в очередь только после коммита. Воркер не прочитает данные, которых ещё нет.
- SMS: 3 попытки с паузой 1 и 5 минут, таймаут 30 секунд. Push повторно не отправляется: лучше потерять один push, чем прислать клиенту два одинаковых.
- Упавшие задачи хранятся в `failed_jobs` 7 дней: `php artisan queue:failed`, повтор — `php artisan queue:retry all`.
- Каждые 5 минут `queue:monitor` проверяет очереди. Если в одной из них больше 50 задач (скорее всего, воркер остановлен), в лог пишется ошибка, а при подключённом Sentry туда уходит сообщение.

## Установка на сервере (Ubuntu/Debian)
1. В `.env`: `QUEUE_CONNECTION=database`. Если нужен realtime — `BROADCAST_CONNECTION=reverb` и ключи `REVERB_*`.
2. Supervisor держит воркеры запущенными и перезапускает их после сбоев:
   ```
   sudo apt install supervisor
   sudo cp deploy/supervisor/restopwa.conf /etc/supervisor/conf.d/restopwa.conf
   sudo nano /etc/supervisor/conf.d/restopwa.conf      # путь к приложению и пользователь
   sudo supervisorctl reread && sudo supervisorctl update
   sudo supervisorctl status                            # restopwa-worker_00/01 и restopwa-reverb: RUNNING
   ```
   Программу `restopwa-reverb` можно удалить из файла, если realtime не используется.
3. Планировщик (cron, каждую минуту):
   ```
   sudo cp deploy/cron/restopwa /etc/cron.d/restopwa   # тоже поправить путь и пользователя
   ```
4. Проверка: сменить статус тестового заказа на «В пути». В `storage/logs/worker.log` должны появиться строки `SendOrderStatusNotification … DONE` и `SendSmsJob … DONE`.

## При каждом деплое
Воркеры держат код в памяти. После `git pull`, `composer install` и `migrate` обязательно:
```
php artisan queue:restart
```
Воркеры спокойно доделают текущие задачи и перезапустятся с новым кодом (supervisor поднимет их сам).

## Если что-то пошло не так
- `sudo supervisorctl status`: воркеры в состоянии `RUNNING`?
- `php artisan queue:monitor database:high,database:default`: сколько задач ждёт.
- `php artisan queue:failed`: что упало и почему; после исправления `php artisan queue:retry all`.
- `tail -f storage/logs/worker.log` и `storage/logs/laravel.log`.
