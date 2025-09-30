#!/bin/bash
set -e

# Load environment variables
export $(grep -v '^#' /var/www/html/.env | xargs)

echo "Waiting for MariaDB at $DB_HOST..."

# Wait until the database container is ready
until mysqladmin ping -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASS" --silent; do
  sleep 2
done

echo "MariaDB is ready."

# Load schema if database is empty
if [ -f /var/www/html/database.sql ]; then
    TABLES=$(mysql -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES;" | wc -l)
    if [ "$TABLES" -le 0 ]; then
        echo "Loading database schema..."
        mysql -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < /var/www/html/database.sql
    else
        echo "Database already has tables, skipping schema import."
    fi
fi

# Start Supervisor (runs Apache + any other services)
exec /usr/bin/supervisord -n

