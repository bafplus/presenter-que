#!/bin/bash
set -e

# Load environment variables
export $(grep -v '^#' /var/www/html/.env | xargs)

echo "===== Starting init-db.sh ====="
echo "DB_HOST=$DB_HOST"
echo "DB_NAME=$DB_NAME"
echo "DB_USER=$DB_USER"
echo "DB_PASS=$DB_PASS"

# Create database if it doesn't exist
DB_EXISTS=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --skip-ssl -e "SHOW DATABASES LIKE '$DB_NAME';" | grep "$DB_NAME" || true)
if [ -z "$DB_EXISTS" ]; then
    echo "Creating database $DB_NAME..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --skip-ssl -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
else
    echo "Database $DB_NAME already exists."
fi

# Import schema if empty
if [ -f /var/www/html/database.sql ]; then
    TABLES=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" --skip-ssl -e "SHOW TABLES;" | wc -l)
    if [ "$TABLES" -le 0 ]; then
        echo "Loading database schema..."
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" --skip-ssl < /var/www/html/database.sql
    else
        echo "Database already has tables, skipping schema import."
    fi
fi

# Start Supervisor (runs Apache)
echo "Starting Supervisor..."
exec /usr/bin/supervisord -n

