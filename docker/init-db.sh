#!/bin/bash
set -e

# Start MariaDB in the background
mysqld_safe --datadir=/var/lib/mysql --user=mysql &
MYSQL_PID=$!

# Wait until MariaDB is ready
echo "Waiting for MariaDB to start..."
for i in {30..0}; do
    if mysqladmin ping &>/dev/null; then
        break
    fi
    sleep 1
done

# Check if database exists, initialize if not
DB_EXISTS=$(mysql -uroot -e "SHOW DATABASES LIKE 'live_presenter';" | grep live_presenter || true)
if [ -z "$DB_EXISTS" ]; then
    echo "Creating database and user..."
    mysql -uroot -e "CREATE DATABASE live_presenter CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
    mysql -uroot -e "CREATE USER 'your_db_user'@'localhost' IDENTIFIED BY 'your_db_password';"
    mysql -uroot -e "GRANT ALL PRIVILEGES ON live_presenter.* TO 'your_db_user'@'localhost';"
    mysql -uroot -e "FLUSH PRIVILEGES;"

    echo "Loading schema..."
    mysql -uyour_db_user -pyour_db_password live_presenter < /var/www/html/database.sql
fi

# Stop the temporary MariaDB background server
mysqladmin -uroot shutdown

# Start Supervisor (runs Apache + MariaDB in foreground)
exec /usr/bin/supervisord -n

