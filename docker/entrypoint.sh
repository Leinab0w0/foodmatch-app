#!/bin/sh
# Se ejecuta cada vez que el contenedor arranca.
set -e
cd /var/www/html

# Carpeta que SÍ se conserva entre reinicios (en Azure es /home, con almacenamiento persistente activado).
DATA="${DATA_DIR:-/home/data}"
mkdir -p "$DATA/public" "$DATA/db"

# Base de datos: SQLite por defecto, guardada en la carpeta persistente
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    export DB_CONNECTION=sqlite
    export DB_DATABASE="${DB_DATABASE:-$DATA/db/database.sqlite}"
    [ -f "$DB_DATABASE" ] || touch "$DB_DATABASE"
fi

# Fotos de los locales: storage/app/public apunta a la carpeta persistente
rm -rf storage/app/public
ln -s "$DATA/public" storage/app/public
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data "$DATA" storage bootstrap/cache 2>/dev/null || true

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan migrate --force

# Solo la primera vez (o cuando lo pidas con RUN_SEED=1): carga categorías y datos base
if [ "${RUN_SEED:-0}" = "1" ]; then
    php artisan db:seed --force || true
fi

php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true

exec "$@"
