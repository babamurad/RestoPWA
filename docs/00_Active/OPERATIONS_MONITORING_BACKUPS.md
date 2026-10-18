# Мониторинг ошибок и резервные копии

## 1. Sentry (мониторинг ошибок)

Ошибки сервера (Laravel) и браузера (PWA клиента) отправляются в Sentry. Пока DSN не задан, отправка выключена: ничего не меняется и никуда не уходит.

### Подключение
1. Зарегистрироваться на sentry.io (или поднять свой Sentry) и создать проект **Laravel**. При желании — второй проект **Vue** для браузера, можно обойтись и одним.
2. В `.env` на сервере:
   ```
   SENTRY_LARAVEL_DSN=https://…@…ingest.sentry.io/…   # DSN проекта Laravel
   SENTRY_TRACES_SAMPLE_RATE=0                         # 0 = только ошибки, без замеров скорости
   VITE_SENTRY_DSN=https://…@…ingest.sentry.io/…      # DSN для браузера (можно тот же)
   ```
3. `php artisan config:cache`.
4. **Пересобрать фронт** (`npm run build`): `VITE_SENTRY_DSN` вшивается в сборку. Без пересборки ошибки браузера не отправляются.
5. Проверка: `php artisan sentry:test` — в Sentry появится тестовое событие.

### Что уходит в Sentry
- Необработанные исключения сервера: стек, URL, окружение (`APP_ENV`).
- Ошибки Vue в браузере клиента. SDK (~150 КБ) загружается отдельным файлом и только при заданном DSN.
- Персональные данные не отправляются (`send_default_pii = false`): ни имён, ни телефонов, ни адресов, ни IP.

## 2. Резервные копии (spatie/laravel-backup)

### Что сохраняется
- **База данных**: дамп, сжатый gzip.
- **Загруженные файлы**: `storage/app/public` (фото ресторанов, блюд, аватары).
- **Не сохраняются**: код (он в git) и `.env` (секреты). `.env` храните отдельно, в менеджере паролей.

Архив шифруется паролем `BACKUP_ARCHIVE_PASSWORD`: в базе есть телефоны и адреса клиентов.

### Расписание (время Ашхабада)
| Время | Команда | Что делает |
|---|---|---|
| 01:30 | `backup:clean` | удаляет старые архивы: все за 7 дней, ежедневные 16 дней, еженедельные 8 недель, ежемесячные 4 месяца, ежегодные 2 года; общий объём не больше 5 ГБ |
| 02:00 | `backup:run` | новый архив |
| 09:00 | `backup:monitor` | проверяет, что свежий архив (не старше суток) есть; если нет — письмо на `BACKUP_NOTIFICATION_EMAIL` |

Нужен cron планировщика Laravel (он уже нужен для курьеров):
```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

### Настройка на сервере
1. Установить утилиту дампа для вашей БД: `mysqldump` (пакет `mysql-client` / `mariadb-client`) или `pg_dump` для PostgreSQL.
2. В `.env`:
   ```
   BACKUP_DISKS=backups                 # архивы в storage/app/backups
   BACKUP_ARCHIVE_PASSWORD=длинный-пароль
   BACKUP_NOTIFICATION_EMAIL=admin@ваш-домен
   ```
   Почта (`MAIL_*`) должна быть настроена, иначе письма о сбоях не дойдут.
3. **Обязательно держите копию вне сервера.** Если сервер сгорит, копия на нём же не поможет. Подойдёт любое S3-совместимое хранилище:
   - заполнить `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET` (и `AWS_ENDPOINT` для не-Amazon);
   - установить драйвер: `composer require league/flysystem-aws-s3-v3`;
   - `BACKUP_DISKS=backups,s3`.
4. Первый запуск вручную: `php artisan backup:run`, затем `php artisan backup:list` — архив должен появиться на всех дисках.

### Восстановление
1. Найти архив: `php artisan backup:list`, файлы лежат в `storage/app/backups/<APP_NAME>/` (или в бакете S3).
2. Распаковать с паролем:
   ```
   unzip -P "$BACKUP_ARCHIVE_PASSWORD" 2026-10-18-02-00-00.zip -d /tmp/restore
   ```
   Внутри: `db-dumps/mysql-<имя_БД>.sql.gz` (для PostgreSQL — `postgresql-<имя_БД>.sql.gz`) и файлы `storage/app/public/…`.
3. Перевести сайт в режим обслуживания: `php artisan down`.
4. Восстановить базу:
   - MySQL: `gunzip -c /tmp/restore/db-dumps/mysql-DB_NAME.sql.gz | mysql -u USER -p DB_NAME`
   - PostgreSQL: `gunzip -c /tmp/restore/db-dumps/postgresql-DB_NAME.sql.gz | psql -U USER DB_NAME`
5. Вернуть файлы: скопировать содержимое `storage/app/public` из архива на место, затем `php artisan storage:link`.
6. `php artisan migrate --force` (если архив старше последних миграций), `php artisan optimize:clear`, `php artisan up`.

### Пробное восстановление (делать после настройки и раз в квартал)
Бэкап, который ни разу не восстанавливали, — не бэкап. Порядок:
1. Создать пустую тестовую БД (`restopwa_restore_test`) на том же или другом сервере.
2. Восстановить в неё последний архив (шаги 2 и 4 выше, с именем тестовой БД).
3. Сверить: `SELECT COUNT(*) FROM orders;`, `… FROM users;`, `… FROM restaurants;` — числа близки к рабочей базе на момент бэкапа; последний заказ в тестовой БД сделан не раньше чем за сутки до архива.
4. Записать дату проверки и результат ниже, удалить тестовую БД.

| Дата | Архив | Результат | Кто проверял |
|---|---|---|---|
| | | | |
