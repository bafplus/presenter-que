#!/bin/bash
set -e

# Load environment variables from .env
export $(grep -v '^#' /var/www/html/.env | xargs)

echo "===== Starting init-db.sh ====="
echo "DB_HOST=$DB_HOST"
echo "DB_NAME=$DB_NAME"
echo "DB_USER=$DB_USER"
echo "DB_PASS=$DB_PASS"

# Wait until MariaDB is ready (using app user)
echo "Waiting for MariaDB at $DB_HOST..."
until mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" --skip-ssl -e "SELECT 1;" &>/dev/null; do
    echo "MariaDB not ready yet..."
    sleep 2
done

echo "MariaDB is ready."

# Load schema if database is empty
if [ -f /var/www/html/database.sql ]; then
    TABLES=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" --skip-ssl -e "SHOW TABLES;" | wc -l)
    if [ "$TABLES" -le 0 ]; then
        echo "Loading database schema..."
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" --skip-ssl < /var/www/html/database.sql
    else
        echo "Database already has tables, skipping schema import."
    fi
fi

# Start Supervisor (runs Apache + any other services)
echo "Starting Supervisor..."
exec /usr/bin/supervisord -n

