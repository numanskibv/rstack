# ---- Stage 1: Build JS assets ----
FROM node:20 AS node-builder

# Composer nodig voor vendor/livewire/flux (geïmporteerd in app.css)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN apt-get update && apt-get install -y php-cli php-mbstring php-xml unzip && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --ignore-platform-reqs

COPY vite.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/
COPY artisan ./
COPY app/ ./app/
COPY bootstrap/ ./bootstrap/
COPY config/ ./config/
COPY routes/ ./routes/

RUN npm run build

# ---- Stage 2: PHP runtime ----
FROM php:8.3-fpm-alpine AS app

# Systeem-afhankelijkheden
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite \
    libpng-dev \
    libzip-dev \
    curl \
    unzip \
    git \
    openssh-client \
    && docker-php-ext-install pdo pdo_sqlite zip gd opcache \
    && rm -rf /var/cache/apk/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/rstack

# PHP-afhankelijkheden
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# App-bestanden
COPY . .

# Gebouwde JS-assets overnemen
COPY --from=node-builder /app/public/build ./public/build

# Permissies
RUN chown -R www-data:www-data /var/www/rstack \
    && chmod -R 755 /var/www/rstack/storage \
    && chmod -R 755 /var/www/rstack/bootstrap/cache

# Configuratiebestanden
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/rstack.ini

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
