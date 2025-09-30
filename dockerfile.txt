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

# Clone the repo (always latest main) into Apache's docroot
RUN rm -rf /var/www/html/* && \
    git clone https://github.com/bafplus/presenter-que.git /var/www/html

# Copy Docker support files
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/init-db.sh /init-db.sh
RUN chmod +x /init-db.sh

EXPOSE 80

ENTRYPOINT ["/init-db.sh"]
