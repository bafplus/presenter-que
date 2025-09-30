#!/bin/bash
set -e

# Load .env
if [ -f /var/www/html/.env ]; then
  export $(grep -v '^#' /var/www/html/.env | xargs)
fi

DB_NAME=${DB_NAME:-live_presenter}
DB_USER=${DB_USER:-your_db_user}
DB_PASS=${DB_PASS:-your_db_password}

# Start MariaDB
mysqld_safe --datadir=/var/lib/mysql --user=mysql &
MYSQL_PID=$!

# Wait for DB
echo "Waiting for MariaDB..."
for i in {30..0}; do
    if mysqladmin ping &>/dev/null; then break; fi
    sleep 1
done

# Initialize DB if missing
DB_EXISTS=$(mysql -uroot -e "SHOW DATABASES LIKE '$DB_NAME';" | grep "$DB_NAME" || true)
if [ -z "$DB_EXISTS" ]; then
    echo "Creating DB and user..."
    mysql -uroot -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
    mysql -uroot -e "CREATE USER '$DB_USER'@'%' IDENTIFIED BY '$DB_PASS';"
    mysql -uroot -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'%';"
    mysql -uroot -e "FLUSH PRIVILEGES;"

    echo "Loading schema..."
    mysql -u$DB_USER -p$DB_PASS $DB_NAME < /var/www/html/database.sql
fi

# Stop temporary DB
mysqladmin -uroot shutdown

# Start Supervisor (Apache + MariaDB)
exec /usr/bin/supervisord -n
