# Выкладка и разбор инцидентов

Документ для того, кто выкладывает RestoPWA на сервер и не писал код. Пути и имена — как в `deploy/`: приложение в `/var/www/restopwa`, пользователь `www-data`. Замените на свои.

Связанные документы: [очереди](OPERATIONS_QUEUES.md), [Sentry и бэкапы](OPERATIONS_MONITORING_BACKUPS.md).

---

## 1. Чек-лист выкладки

Выполнять по порядку. Если шаг завершился ошибкой — остановиться и перейти к разделу «Откат».

### Перед выкладкой

- [ ] CI на `main` зелёный (Pint, PHPUnit, Vitest, сборка).
- [ ] Есть свежий бэкап: `php artisan backup:list` показывает копию не старше суток. Если нет — `php artisan backup:run`.
- [ ] Записан текущий коммит, чтобы было куда откатываться: `git rev-parse HEAD`.

### Выкладка

```bash
cd /var/www/restopwa.tmtourism.com
```

```bash
php artisan down --retry=60
```

```bash
git pull origin main
```

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

```bash
npm ci --no-audit --no-fund
```

```bash
npm run build
```

```bash
php artisan migrate --force
```

```bash
php artisan config:cache
```

```bash
php artisan route:cache
```

```bash
php artisan view:cache
```

```bash
php artisan queue:restart
```

```bash
sudo supervisorctl restart restopwa-reverb
```

```bash
php artisan up
```

Пояснения:

- Каталога `public/build` в git нет: сборку делает сервер (`npm run build`).
- `npm run build` обязателен: ключи Reverb и Sentry (`VITE_*`) берутся из `.env` сервера и вшиваются в сборку. Сборка, сделанная на другой машине, будет без них.
- `queue:restart` — воркеры дорабатывают текущую задачу и перезапускаются с новым кодом. Без этого шага они продолжат выполнять старый код.
- Reverb тоже держит код в памяти, поэтому его перезапускают отдельно.

### После выкладки

- [ ] `https://<домен>/up` отвечает 200.
- [ ] Главная открывается, меню ресторана загружается.
- [ ] `sudo supervisorctl status` — `restopwa-worker` и `restopwa-reverb` в состоянии `RUNNING`.
- [ ] Тестовый заказ проходит цикл: оформление → появился на канбане ресторана → «Готовятся» → «Готовы» → курьер принял → «Доставил».
- [ ] В Sentry нет новых ошибок за последние 10 минут.

### Откат

```bash
php artisan down --retry=60
```

```bash
git checkout <записанный коммит>
```

Затем повторить шаги выкладки, начиная с `composer install`, **кроме** `migrate`.

Миграции назад не откатывать без необходимости: большинство добавляют колонки и со старым кодом совместимы. Если новая миграция сломала данные — восстановить базу из бэкапа по инструкции в `OPERATIONS_MONITORING_BACKUPS.md`.

### Демо-данные

`php artisan db:seed` и `DemoDataSeeder` на проде **не выполняются** (сидер отказывается запускаться): они создают пользователей с паролем `password` и меняют настройки ресторанов.

Если демо-данные уже попали в базу (например, при показе заказчику), перед запуском их удаляют командой. Сначала без `--force` — она только показывает, что будет удалено:

```bash
php artisan demo:purge
```

```bash
php artisan demo:purge --force
```

Команда удаляет пользователей `demo.*@restopwa.local` вместе с их заказами, отзывами, адресами, профилями курьеров и заработком, пересчитывает рейтинги, убирает демо-выплаты (выплаты без заказов) и записи SMS-журнала этих пользователей. Рестораны из сидера остаются; чтобы удалить и их вместе с владельцем `owner@restopwa.local`, добавьте `--include-restaurants` (рестораны, у которых есть заказы реальных клиентов, команда не трогает). Не удаляются: пять районов доставки и записи об отказах без пользователя.

---

## 2. Reverb (реальное время) на проде

Без Reverb всё работает, но медленнее: канбан ресторана обновляется раз в 25 секунд, экран курьера — раз в 15 секунд, страница отслеживания — опросом.

### `.env`

```
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=<любое число>
REVERB_APP_KEY=<случайная строка>
REVERB_APP_SECRET=<случайная строка>
REVERB_HOST=<домен сайта>
REVERB_PORT=443
REVERB_SCHEME=https

REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
```

`REVERB_HOST/PORT/SCHEME` — адрес, по которому браузер подключается снаружи (через Nginx, 443). `REVERB_SERVER_*` — где процесс Reverb слушает на сервере.

Частая ошибка: `BROADCAST_CONNECTION` остался `log`. Тогда события пишутся в лог и до браузера не доходят.

### Nginx

Внутри блока `server` сайта:

```nginx
location /app {
    proxy_http_version 1.1;
    proxy_set_header Host $http_host;
    proxy_set_header Scheme $scheme;
    proxy_set_header SERVER_PORT $server_port;
    proxy_set_header REMOTE_ADDR $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_pass http://127.0.0.1:8080;
}
```

### Проверка

1. `sudo supervisorctl status restopwa-reverb` — `RUNNING`.
2. После изменения `.env`: `php artisan config:cache`, `npm run build`, перезапуск Reverb.
3. Открыть канбан ресторана, в соседней вкладке оформить заказ: карточка должна появиться за 1–2 секунды, а не через 25.

---

## 3. Разбор инцидентов

### Не уходят push и SMS, курьерам не приходят предложения

Скорее всего, стоит очередь.

1. `sudo supervisorctl status restopwa-worker` — должен быть `RUNNING`. Если нет: `sudo supervisorctl restart restopwa-worker:*`.
2. `php artisan queue:failed` — упавшие задачи с текстом ошибки. Повторить: `php artisan queue:retry all`.
3. `tail -n 100 storage/logs/worker.log`.

Предложения курьерам истекают и без очереди: команда `couriers:expire-offers` запускается планировщиком каждую минуту. Если не работает и она — проверить cron (`/etc/cron.d/restopwa`).

### SMS не доходят, хотя очередь работает

Посмотреть статусы в таблице `sms_logs`:

- `skipped` — отправка выключена или нет учётных данных шлюза. Нужны `SMS_ENABLED=true`, `TMSMS_LOGIN`, `TMSMS_PASSWORD` в `.env`, затем `php artisan config:cache` и `php artisan queue:restart`.
- `failed` — шлюз ответил ошибкой; текст в колонке `error_message` и в `storage/logs/laravel.log` по метке `[SMS]`.

### Push не приходят

1. В `.env` заданы `VAPID_PUBLIC_KEY` и `VAPID_PRIVATE_KEY`.
2. Сайт открыт по HTTPS с действующим сертификатом — без него браузер не выдаёт подписку.
3. В `storage/logs/laravel.log` искать `[Notifications] Push failed`.

### Канбан или экран курьера обновляются с задержкой

Не работает реальное время, приложение перешло на опрос. См. раздел 2: статус `restopwa-reverb`, значение `BROADCAST_CONNECTION`, блок `location /app` в Nginx, `storage/logs/reverb.log`.

### Письма о новых заказах не приходят ресторану

1. `MAIL_MAILER` в `.env` не должен быть `log` — иначе письма пишутся в лог, а не отправляются.
2. У ресторана указан владелец с рабочим email (админка → Рестораны).
3. Письма отправляются через очередь — проверить её, как в первом пункте.

### Ошибка 500 при оформлении заказа

1. В ответе сервера есть `trace_id`. Найти его в `storage/logs/laravel.log` — рядом будет причина.
2. Та же ошибка есть в Sentry с полным стеком.
3. Частые причины: не выполнены миграции после выкладки (`php artisan migrate --force`), недоступна база.

### Заказы отклоняются с «не удалось определить координаты»

Клиент не выбрал точку на карте, а геокодер не нашёл адрес. Проверить ключи `YANDEX_MAPS_API_KEY` / `GOOGLE_MAPS_API_KEY` и журнал геокодирования в админке. Доля таких отказов видна в аналитике платформы, в блоке причин отказов.

### Не создаётся бэкап

Утром приходит письмо от `backup:monitor`. Проверить место на диске (`df -h`), запустить вручную `php artisan backup:run` и прочитать ошибку. Подробности — в `OPERATIONS_MONITORING_BACKUPS.md`.

### Вход отвечает «Слишком много попыток»

Сработало ограничение: 5 неверных попыток в минуту на пару «email + адрес». Проходит само через минуту. Если так отвечает всем сразу — сервер видит один IP для всех посетителей: проверить, что Nginx передаёт `X-Forwarded-For`.

---

## 4. Первая настройка сервера (Hostinger VPS)

Делается один раз. Ubuntu 22.04/24.04, команды от пользователя с `sudo`. Путь приложения — `/var/www/restopwa`.

1. **Пакеты.** Nginx, PHP 8.3 (fpm, cli, pgsql, mbstring, xml, curl, zip, gd, intl, bcmath), PostgreSQL с PostGIS, Node.js 22, Composer, Supervisor, Certbot, Git.
2. **База.** Создать пользователя и базу PostgreSQL, в базе выполнить `CREATE EXTENSION postgis;`.
3. **Код.** `git clone` репозитория в `/var/www/restopwa`, владелец каталога — пользователь, от которого вы выкладываете (например `deploy`); `storage` и `bootstrap/cache` должны быть доступны на запись для `www-data`.
4. **Настройки.** `cp .env.example .env`, затем `php artisan key:generate` и заполнить `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, база (`DB_CONNECTION=pgsql`, ...), почта, `QUEUE_CONNECTION=database`, ключи Reverb (раздел 2), Sentry, VAPID, SMS. Значения `VITE_*` вшиваются в сборку, поэтому `.env` заполняют до первой сборки.
5. **Первый запуск:** `./deploy/deploy.sh`. Скрипт сам выполнит `composer install`, `npm ci`, `npm run build`, миграции и кэши. После первого запуска: `php artisan storage:link`.
6. **Nginx и HTTPS.** Сайт указывает на `/var/www/restopwa/public`, блок `location /app` для Reverb — из раздела 2. Сертификат: `sudo certbot --nginx -d <домен>`. Без HTTPS не работают PWA и push.
7. **Фоновые процессы.** `sudo cp deploy/supervisor/restopwa.conf /etc/supervisor/conf.d/`, затем `sudo supervisorctl reread && sudo supervisorctl update`. Планировщик: `sudo cp deploy/cron/restopwa /etc/cron.d/restopwa` (проверить путь и пользователя).
8. **Права на перезапуск Reverb** для пользователя выкладки: строка в `sudo visudo -f /etc/sudoers.d/restopwa`: `deploy ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart restopwa-reverb`.
9. **Администратор:** `php artisan admin:create <email> <пароль> --name=<имя>`.
10. **Проверка** по списку «После выкладки» из раздела 1. Демо-данные на проде не запускать.

Файлы `deploy/supervisor/legacy-*.conf` относятся к старому серверу (`restopwa.tmtourism.com`); на новом сервере не нужны.

### Обычная выкладка

`./deploy/deploy.sh` вместо ручного списка из раздела 1. Откат: `SKIP_MIGRATE=1 ./deploy/deploy.sh <коммит>`.

---

## 5. Что ещё не решено

- **Staging.** Отдельной тестовой среды нет. Выкладка идёт вручную скриптом `deploy/deploy.sh`.
- **Пробное восстановление бэкапа** на сервере ещё не проводилось; результат записать в таблицу в `OPERATIONS_MONITORING_BACKUPS.md`.
