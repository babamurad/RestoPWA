#!/usr/bin/env bash
# Выкладка RestoPWA на сервер (Hostinger VPS). Запускать на сервере от пользователя,
# который владеет каталогом приложения (не root):
#   APP_DIR=/var/www/restopwa ./deploy/deploy.sh          # выложить main
#   APP_DIR=/var/www/restopwa ./deploy/deploy.sh <коммит> # выложить (или откатить на) коммит
#
# Фронтенд собирается здесь же (`npm run build`): каталога public/build в git нет.
# Миграции при откате пропускаются: `SKIP_MIGRATE=1 ./deploy/deploy.sh <коммит>`.
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/restopwa}"
TARGET="${1:-origin/main}"
cd "$APP_DIR"

PREVIOUS="$(git rev-parse HEAD)"
echo "==> Сейчас: $PREVIOUS (запишите на случай отката)"

# Если что-то упало, сайт не оставляем в режиме обслуживания навсегда
# и говорим, на какой коммит откатываться.
finish() {
    code=$?
    if [ "$code" -ne 0 ]; then
        echo "!! Выкладка остановилась с ошибкой. Сайт остаётся в режиме обслуживания."
        echo "!! Откат: SKIP_MIGRATE=1 ./deploy/deploy.sh $PREVIOUS"
    fi
}
trap finish EXIT

php artisan down --retry=60

git fetch origin
git checkout --quiet --detach "$TARGET"

composer install --no-dev --optimize-autoloader --no-interaction
npm ci --no-audit --no-fund
npm run build

if [ -z "${SKIP_MIGRATE:-}" ]; then
    php artisan migrate --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart

# Reverb держит код в памяти. Нужны права на supervisorctl (sudo без пароля для этой команды).
if command -v supervisorctl >/dev/null 2>&1; then
    sudo supervisorctl restart restopwa-reverb || echo "!! Reverb не перезапущен, сделайте вручную"
fi

php artisan up
echo "==> Готово: $(git rev-parse --short HEAD)"
echo "    Проверьте: /up отвечает 200, меню открывается, supervisorctl status."
