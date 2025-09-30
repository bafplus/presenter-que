FROM php:8.2-apache

# Install required packages for PHP extensions and Composer
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y \
        git \
        unzip \
        libzip-dev \
        curl \
        supervisor \
        && docker-php-ext-install pdo pdo_mysql mysqli zip \
        && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy source code
COPY . /var/www/html

# Install Composer (build-time only)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader \
    && rm -rf /root/.composer/cache

# Copy Supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy and make init-db.sh executable
COPY init-db.sh /var/www/html/init-db.sh
RUN chmod +x /var/www/html/init-db.sh

# Expose port 80
EXPOSE 80

# Start init script (which will then start Supervisor)
ENTRYPOINT ["/var/www/html/init-db.sh"]
