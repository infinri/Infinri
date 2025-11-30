# =============================================================================
# Infinri Production Dockerfile
# =============================================================================
# Multi-stage build for minimal production image with RoadRunner
# =============================================================================

# -----------------------------------------------------------------------------
# Stage 1: Build dependencies
# -----------------------------------------------------------------------------
FROM composer:2 AS composer

WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative


# -----------------------------------------------------------------------------
# Stage 2: Production image
# -----------------------------------------------------------------------------
FROM php:8.4-cli-alpine AS production

# Install system dependencies
RUN apk add --no-cache \
    libpq \
    libzip \
    icu \
    && apk add --no-cache --virtual .build-deps \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    && docker-php-ext-install \
    pdo_pgsql \
    zip \
    intl \
    opcache \
    && apk del .build-deps

# Install Redis extension
RUN apk add --no-cache --virtual .redis-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .redis-deps

# Install RoadRunner
COPY --from=ghcr.io/roadrunner-server/roadrunner:2024 /usr/bin/rr /usr/local/bin/rr

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# OPcache configuration
COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini

# Create non-root user
RUN addgroup -g 1000 infinri && adduser -u 1000 -G infinri -s /bin/sh -D infinri

# Set working directory
WORKDIR /app

# Copy application from builder
COPY --from=composer /build/vendor ./vendor
COPY . .

# Set permissions
RUN chown -R infinri:infinri /app \
    && chmod -R 755 /app \
    && chmod -R 775 /app/var

# Switch to non-root user
USER infinri

# Expose RoadRunner port
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget -qO- http://localhost:2114/health || exit 1

# Start RoadRunner
CMD ["rr", "serve", "-c", ".rr.yaml"]


# -----------------------------------------------------------------------------
# Stage 3: Development image (optional)
# -----------------------------------------------------------------------------
FROM production AS development

USER root

# Install development dependencies
RUN apk add --no-cache \
    git \
    nodejs \
    npm

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

USER infinri

# Override command for development (with file watching)
CMD ["rr", "serve", "-c", ".rr.yaml", "-w"]
