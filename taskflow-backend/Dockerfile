# Usa una imagen base de PHP-FPM con la versión que necesites
FROM php:8.2-fpm-alpine

# Instalar dependencias del sistema necesarias para PHP
RUN apk add --no-cache \
    git \
    curl \
    libxml2-dev \
    postgresql-dev \
    autoconf \
    g++ \
    make

# Instalar extensiones de PHP comunes para Laravel/aplicaciones web
# Aquí instalamos pgsql (para PostgreSQL) y otras importantes
RUN docker-php-ext-install pdo pdo_pgsql opcache \
    && docker-php-ext-enable opcache

# Si necesitas Composer, agrégalo
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar el usuario no-root para seguridad
RUN adduser -D -u 1000 appuser
USER appuser

# Exponer el puerto de PHP-FPM
EXPOSE 9000
CMD ["php-fpm"]