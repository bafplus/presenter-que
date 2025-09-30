#!/bin/bash
set -e

# Load environment variables from .env
export $(grep -v '^#' /var/www/html/.env | xargs)

echo "===== Starting init-db.sh ====="
echo "DB_HOST=$DB_HOST"
echo "DB_NAME=$DB_NAME"
echo "DB_USER=$DB_USER"
echo "DB_PASS=$DB_PASS"

# Wait until MariaDB server is accepting connections (without requiring DB to exist)
MAX_RETRIES=30
COUNT=0

echo "Waiting for MariaDB at $DB_HOST..."
until mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --skip-ssl -e "SELECT 1;" &>/dev/null; do
    COUNT=$((COUNT + 1))
    echo "MariaDB not ready yet... attempt $COUNT/$MAX_RETRIES"
    if [ "$COUNT" -ge "$MAX_RETRIES" ]; then
        echo "Error: MariaDB did not become ready in time."
        exit 1
    fi
    sleep 2
done

echo "MariaDB server is accepting connections."

# Check if database exists, create if missing
DB_EXISTS=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --skip-ssl -e "SHOW DATABASES LIKE '$DB_NAME';" | grep "$DB_NAME" || true)
if [ -z "$DB_EXISTS" ]; then
    echo "Creating database $DB_NAME..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --skip-ssl -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
else
    echo "Database $DB_NAME already exists."
fi

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

# Start Supervisor (runs Apache + other services)
echo "Starting Supervisor..."
exec /usr/bin/supervisord -n
