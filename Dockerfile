# ============================================================
# FoodMatch · imagen de producción (PHP 8.3 + Apache)
# Etapa 1: instala las dependencias de PHP con Composer
# Etapa 2: imagen final liviana que sirve la app
# ============================================================
FROM composer:2 AS dependencias
WORKDIR /app
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs

FROM php:8.3-apache

# Extensiones que usa Laravel (pdo_sqlite, mbstring, xml, fileinfo, tokenizer ya vienen incluidas)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip \
    && docker-php-ext-install pdo_mysql zip opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Apache sirve la carpeta /public de Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
COPY docker/apache.conf /etc/apache2/conf-available/foodmatch.conf
RUN a2enconf foodmatch

# Apache trae un alias interno /icons/ que tapa nuestra carpeta public/icons (íconos de la app): lo quitamos
RUN sed -i '/Alias \/icons\//d' /etc/apache2/mods-available/alias.conf

# PHP en modo producción + límite de subida de fotos (3 MB de la app + margen)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf "upload_max_filesize=6M\npost_max_size=8M\nexpose_php=Off\n" > "$PHP_INI_DIR/conf.d/foodmatch.ini"

WORKDIR /var/www/html
COPY --from=dependencias /app ./
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
