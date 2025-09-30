FROM php:8.2-apache

# Install system dependencies + PHP extensions
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y \
        mariadb-client git supervisor unzip && \
    docker-php-ext-install mysqli pdo pdo_mysql && \
    rm -rf /var/lib/apt/lists/*

# Set workdir
WORKDIR /var/www/html

# Copy app source
COPY . /var/www/html

# Install Composer (copy binary from official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy init script and Supervisor config
COPY init-db.sh /init-db.sh
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN chmod +x /init-db.sh

EXPOSE 80

ENTRYPOINT ["/init-db.sh"]


# Entrypoint
ENTRYPOINT ["/init-db.sh"]
