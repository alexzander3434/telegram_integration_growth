FROM php:8.4-apache

RUN a2enmod rewrite headers

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    git \
    postgresql-client \
    unzip \
    libicu-dev \
    librabbitmq-dev \
    libpq-dev \
    libsqlite3-dev \
    libzip-dev \
  && docker-php-ext-install -j"$(nproc)" intl pdo_pgsql pdo_sqlite zip \
  && pecl install amqp \
  && docker-php-ext-enable amqp \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.json
RUN composer install --no-interaction --no-scripts --no-autoloader

COPY . .

RUN composer dump-autoload --optimize

RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

# Symfony routing via public/index.php (AllowOverride for .htaccess)
RUN sed -i 's#AllowOverride None#AllowOverride All#g' /etc/apache2/apache2.conf

# Symfony needs to write cache/logs as www-data
RUN mkdir -p var/cache var/log \
  && chown -R www-data:www-data var \
  && chmod -R ug+rwX var

EXPOSE 80

