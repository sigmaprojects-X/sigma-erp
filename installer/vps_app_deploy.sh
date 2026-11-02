#!/bin/bash
set -e

echo "=== EXTRACTING APPLICATION TO /var/www/sigma-erp ==="
sudo rm -rf /var/www/sigma-erp/*
sudo tar -xzf /home/ubuntu/sigma-prod.tar.gz -C /var/www/sigma-erp
sudo tar -xzf /home/ubuntu/public-build.tar.gz -C /var/www/sigma-erp/public

echo "=== WRITING PRODUCTION .ENV ==="
cat << 'EOF' | sudo tee /var/www/sigma-erp/.env
APP_NAME="SIGMA ERP & POS"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://pos.sigmajayanusantara.biz.id

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sigma_erp
DB_USERNAME=postgres
DB_PASSWORD=SigmaPostgres2026
DB_SCHEMA=sigma

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

MEMCACHED_HOST=127.0.0.1
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
EOF

echo "=== RUNNING COMPOSER & ARTISAN ==="
cd /var/www/sigma-erp

# Ensure git doesn't complain about safe.directory
git config --global --add safe.directory /var/www/sigma-erp || true

# Run Composer Install
composer install --no-dev --optimize-autoloader --no-interaction

# Laravel Setup
php8.3 artisan key:generate --force
php8.3 artisan storage:link --force || true
php8.3 artisan migrate --force
php8.3 artisan db:seed --force
php8.3 artisan optimize

echo "=== SETTING FILE PERMISSIONS ==="
sudo chown -R ubuntu:www-data /var/www/sigma-erp
sudo chown -R www-data:www-data /var/www/sigma-erp/storage /var/www/sigma-erp/bootstrap/cache
sudo chmod -R 775 /var/www/sigma-erp/storage /var/www/sigma-erp/bootstrap/cache

sudo systemctl restart php8.3-fpm
sudo systemctl reload nginx

echo "=== SIGMA_ERP_DEPLOYMENT_COMPLETE ==="
