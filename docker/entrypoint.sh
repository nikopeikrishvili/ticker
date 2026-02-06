#!/bin/sh
set -e

# Create log directories
mkdir -p /var/log/supervisor /var/log/nginx /var/log/php

# Wait for database to be ready
echo "Waiting for database..."
while ! mysqladmin ping -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; do
    sleep 1
done
echo "Database is ready!"

# Set permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Generate app key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:your-app-key-here" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Clear and cache config for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

echo "Application is ready!"

# Execute the main command
exec "$@"
