FROM php:8.2-apache

# Install required packages: git, supervisor, and PHP extensions
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y \
        git \
        supervisor \
        unzip \
        libzip-dev \
        && docker-php-ext-install pdo pdo_mysql mysqli zip \
        && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Remove default Apache content and copy app
RUN rm -rf /var/www/html/*
COPY . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Copy Supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy and make init-db.sh executable
COPY init-db.sh /var/www/html/init-db.sh
RUN chmod +x /var/www/html/init-db.sh

# Expose port 80
EXPOSE 80

# Start init script (which will then start Supervisor)
ENTRYPOINT ["/var/www/html/init-db.sh"]

