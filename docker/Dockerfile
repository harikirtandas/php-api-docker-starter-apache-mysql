FROM php:8.4-apache

# dependencias de sistema: libzip para la extension zip, git y unzip para composer
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev unzip git \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mysqli zip

RUN a2enmod rewrite

# solo el binario de composer, no hace falta instalar composer completo via apt
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-dev.ini

# remapea www-data a la UID/GID del host (33 es la UID/GID default de www-data
# en esta imagen) para que lo que Apache escriba en el bind mount no quede
# root-owned en Linux nativo. En Mac es indistinto, Docker Desktop ya traduce
# el ownership del bind mount al usuario real del host.
ARG UID=33
ARG GID=33
RUN if [ "$UID" != "33" ]; then usermod -u "$UID" www-data || true; fi
RUN if [ "$GID" != "33" ]; then groupmod -g "$GID" www-data || true; fi

WORKDIR /var/www/html

# no CMD ni USER: Apache tiene que arrancar como root para poder bindear el
# puerto 80, y el propio httpd baja los workers a www-data solo
