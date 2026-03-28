# ============================================================
# Stage 1: Build frontend assets (Node.js / Vite)
# ============================================================
FROM node:20-alpine AS frontend-builder

WORKDIR /app

ARG VITE_API_URL
ENV VITE_API_URL=$VITE_API_URL

# Install dependencies first (layer cache)
COPY package.json package-lock.json ./
RUN npm ci

# Copy only files needed for the build
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources/ resources/
COPY public/ public/

# OAuth secrets are mounted at build time (not baked into image layers).
# Locally without secrets, Vite reads from .env automatically.
RUN --mount=type=secret,id=VITE_OAUTH_CLIENT_ID \
    --mount=type=secret,id=VITE_OAUTH_CLIENT_SECRET \
    VITE_OAUTH_CLIENT_ID=$(cat /run/secrets/VITE_OAUTH_CLIENT_ID 2>/dev/null || echo "") \
    VITE_OAUTH_CLIENT_SECRET=$(cat /run/secrets/VITE_OAUTH_CLIENT_SECRET 2>/dev/null || echo "") \
    npm run build

# ============================================================
# Stage 2: Production PHP-FPM application
# ============================================================
FROM php:8.3-fpm AS app

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer 2
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies (production, no dev packages)
COPY composer.json composer.lock ./
RUN composer install \
    --no-interaction \
    --no-dev \
    --optimize-autoloader \
    --no-scripts

# Copy application source
COPY . .

# Overlay compiled frontend assets from Stage 1
COPY --from=frontend-builder /app/public/build ./public/build

# Store a copy of seeded public assets in a path that is NOT covered by the
# public_data volume mount. The entrypoint copies missing files back at runtime.
RUN mkdir -p /var/www/html-image-defaults \
    && cp -r ./public/uploads /var/www/html-image-defaults/uploads \
    && cp -r ./public/build /var/www/html-image-defaults/build

# Run Composer post-install hooks (generates IDE helpers, etc.)
RUN composer run-script post-autoload-dump 2>/dev/null || true

# Copy custom PHP runtime config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Entrypoint: waits for DB, runs migrations, caches config, then starts php-fpm
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]