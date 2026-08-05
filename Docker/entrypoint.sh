#!/bin/bash
set -e

cd /var/www

if [ ! -f ".env" ]; then
    echo "Creating .env file for environment: ${APP_ENV:-local}"
    cp .env.example .env
else
    echo ".env file exists."
fi

if [ ! -f "vendor/autoload.php" ]; then
    composer install --no-progress --no-interaction
fi

if [ ! -d "node_modules" ]; then
    npm install --no-audit --no-fund
fi

# APP_KEY must exist before anything that touches the encrypter.
if ! grep -qE '^APP_KEY=.+' .env; then
    php artisan key:generate --force
fi

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# The database container accepts connections before it is ready to serve them.
echo "Waiting for database at ${DB_HOST:-database}:${DB_PORT:-3306}..."
until php -r "new PDO('mysql:host='.(getenv('DB_HOST') ?: 'database').';port='.(getenv('DB_PORT') ?: '3306'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    sleep 2
done
echo "Database is ready."

php artisan migrate --force

# Seed once only; CountriesTableSeeder is not idempotent.
if [ ! -f "storage/.seeded" ]; then
    php artisan db:seed --force
    touch storage/.seeded
fi

if [ ! -d "public/build" ]; then
    npm run build
fi

php artisan serve --port="${PORT:-8000}" --host=0.0.0.0
