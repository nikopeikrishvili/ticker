#!/bin/sh
set -e

# Create log directories
mkdir -p /var/log/supervisor /var/log/nginx /var/log/php

# Wait for database to be ready (supports both PostgreSQL and MySQL)
if [ -n "$DATABASE_URL" ]; then
    echo "Waiting for database..."
    # Extract host from DATABASE_URL
    DB_HOST=$(echo $DATABASE_URL | sed -e 's|.*@\(.*\):.*|\1|')
    DB_PORT=$(echo $DATABASE_URL | sed -e 's|.*:\([0-9]*\)/.*|\1|')

    MAX_RETRIES=30
    RETRY_COUNT=0

    while ! nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null; do
        RETRY_COUNT=$((RETRY_COUNT + 1))
        if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
            echo "ERROR: Database at $DB_HOST:$DB_PORT not reachable after $MAX_RETRIES attempts"
            exit 1
        fi
        echo "Database not ready, waiting... (attempt $RETRY_COUNT/$MAX_RETRIES)"
        sleep 2
    done
    echo "Database is ready!"
fi

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
