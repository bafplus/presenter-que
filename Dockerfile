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

# Clone the repo into Apache docroot
RUN rm -rf /var/www/html/* && \
    git clone https://github.com/bafplus/presenter-que.git /var/www/html

WORKDIR /var/www/html

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies (includes vlucas/phpdotenv)
RUN composer install --no-dev --optimize-autoloader

# Copy Docker support files
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY init-db.sh /init-db.sh
RUN chmod +x /init-db.sh

# Copy .env into container so PHP and init script can read it
COPY .env /var/www/html/.env

EXPOSE 80

# Run init-db.sh as entrypoint (initializes DB + starts Supervisor)
ENTRYPOINT ["/init-db.sh"]

