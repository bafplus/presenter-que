#!/bin/bash
set -e

# Initialize MariaDB if data directory is empty
if [ ! -d /var/lib/mysql/mysql ]; then
    echo "Initializing MariaDB..."
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql
    mysqld_safe --skip-networking &
    sleep 5

    echo "Creating database and loading schema..."
    mysql -uroot -e "CREATE DATABASE IF NOT EXISTS presenterque;"
    mysql -uroot presenterque < /var/www/html/database.sql

    mysqladmin -uroot shutdown
fi

# Run Supervisor to keep Apache + MySQL alive
exec /usr/bin/supervisord -n

