FROM php:8.2-apache

# Install MariaDB + tools + Supervisor
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y \
        mariadb-server \
        mariadb-client \
        git \
        supervisor && \
    docker-php-ext-install mysqli pdo pdo_mysql && \
    rm -rf /var/lib/apt/lists/*

# Clone the repo
RUN rm -rf /var/www/html/* && \
    git clone https://github.com/bafplus/presenter-que.git /var/www/html

WORKDIR /var/www/html

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy composer.json and install dependencies
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader

# Copy Docker support files
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY init-db.sh /init-db.sh
RUN chmod +x /init-db.sh

# Copy .env
COPY .env /var/www/html/.env

EXPOSE 80

# Entrypoint
ENTRYPOINT ["/init-db.sh"]
