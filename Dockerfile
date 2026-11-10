# Render has no native PHP runtime, so the service runs from Docker (runtime: docker in render.yaml).
FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip libzip-dev libicu-dev zlib1g-dev libjpeg-dev libpng-dev libwebp-dev $PHPIZE_DEPS \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install zip intl bcmath gd \
 # Firestore PHP client needs the gRPC extension. The first build takes several minutes.
 && pecl install grpc && docker-php-ext-enable grpc \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/app
COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction
COPY . .

ENV APACHE_DOCUMENT_ROOT=/var/www/app/public
COPY docker/apache.conf /etc/apache2/conf-available/afms.conf
RUN a2enconf afms \
 && sed -ri "s#/var/www/html#${APACHE_DOCUMENT_ROOT}#g" /etc/apache2/sites-available/*.conf \
 && printf 'expose_php=Off\ndisplay_errors=Off\nlog_errors=On\nerror_log=/dev/stderr\nsession.use_strict_mode=1\nupload_max_filesize=3M\npost_max_size=4M\nmemory_limit=256M\ndate.timezone=UTC\n' > /usr/local/etc/php/conf.d/app.ini \
 && chown -R www-data:www-data /var/www/app/storage

# Render injects $PORT
CMD ["sh","-c","sed -i \"s/Listen 80/Listen ${PORT:-10000}/\" /etc/apache2/ports.conf && sed -i \"s/:80>/:${PORT:-10000}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
