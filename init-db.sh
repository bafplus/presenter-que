#!/bin/bash
set -e

# Load environment variables from .env
export $(grep -v '^#' /var/www/html/.env | xargs)

echo "===== Starting init-db.sh ====="
echo "DB_HOST=$DB_HOST"
echo "DB_NAME=$DB_NAME"
echo "DB_USER=$DB_USER"
echo "DB_PASS=$DB_PASS"
echo "MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD"

# Wait until MariaDB is ready (use root credentials)
echo "Waiting for MariaDB at $DB_HOST..."
while ! mysql -h "$DB_HOST" -u root -p"$MYSQL_ROOT_PASSWORD" -e "SELECT 1;" &>/dev/null; do
    echo "MariaDB not ready yet..."
    sleep 2
done

echo "MariaDB is ready."

# Check if database exists, create if needed
DB_EXISTS=$(mysql -h "$DB_HOST" -u root -p"$MYSQL_ROOT_PASSWORD" -e "SHOW DATABASES LIKE '$DB_NAME';" --skip-ssl | grep "$DB_NAME" || true)
if [ -z "$DB_EXISTS" ]; then
    echo "Creating database $DB_NAME and user $DB_USER..."
    mysql -h "$DB_HOST" -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" --skip-ssl
    mysql -h "$DB_HOST" -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE USER '$DB_USER'@'%' IDENTIFIED BY '$DB_PASS';" --skip-ssl
    mysql -h "$DB_HOST" -u root -p"$MYSQL_ROOT_PASSWORD" -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'%'; FLUSH PRIVILEGES;" --skip-ssl
else
    echo "Database $DB_NAME already exists."
fi

# Load schema if database is empty
TABLES=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" --skip-ssl -e "SHOW TABLES;" | wc -l)
if [ "$TABLES" -le 0 ]; then
    echo "Loading database schema..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" --skip-ssl < /var/www/html/database.sql
else
    echo "Database already has tables, skipping schema import."
fi

# Start Supervisor (runs Apache + any other services)
echo "Starting Supervisor..."
exec /usr/bin/supervisord -n


