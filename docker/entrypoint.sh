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

# Con RUN_SEED=1: carga categorías y menús de ejemplo UNA sola vez (deja una marca en la carpeta persistente)
if [ "${RUN_SEED:-0}" = "1" ] && [ ! -f "$DATA/.seed_ok" ]; then
    php artisan db:seed --class=FoodMatchSeeder --force \
      && php artisan db:seed --class=DemoMenusSeeder --force \
      && touch "$DATA/.seed_ok" || true
fi

# Con ADMIN_EMAIL=tucorreo: esa cuenta (ya registrada) pasa a ser admin y queda verificada
if [ -n "${ADMIN_EMAIL:-}" ]; then
    php artisan tinker --execute="App\\Models\\User::where('email', '${ADMIN_EMAIL}')->update(['es_admin'=>true,'email_verified_at'=>now()]);" || true
fi

php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true

# Con IMPORTAR_OSM=1: trae los restaurantes reales de la zona UNA vez, en segundo plano (no demora el arranque)
if [ "${IMPORTAR_OSM:-0}" = "1" ] && [ ! -f "$DATA/.osm_ok" ]; then
    ( php artisan foodmatch:importar-osm --radio=8000 && touch "$DATA/.osm_ok" ) >/proc/1/fd/1 2>&1 &
fi

exec "$@"
