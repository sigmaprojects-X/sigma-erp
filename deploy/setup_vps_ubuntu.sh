#!/bin/bash
# ==============================================================================
# SIGMA ERP & POS - AUTOMATED PRODUCTION VPS INSTALLER
# Supported OS: Ubuntu 22.04 LTS / Ubuntu 24.04 LTS
# Target Cloud: Biznet GIO / Jagoan Hosting / IDCloudHost / KilatVM
# ==============================================================================

set -e

echo "============================================================"
echo "    SIGMA ERP & POS - SETUP SERVER PRODUCTION VPS LOKAL     "
echo "============================================================"
echo ""

# 1. Update System & Install Dependencies
echo "[1/7] Memperbarui sistem operasi & paket dasar..."
sudo apt-get update -y
sudo apt-get upgrade -y
sudo apt-get install -y curl git unzip zip software-properties-common ca-certificates lsb-release ufw

# 2. Install Nginx & Certbot SSL
echo "[2/7] Menginstall Nginx & Certbot Let's Encrypt..."
sudo apt-get install -y nginx certbot python3-certbot-nginx

# 3. Install PHP 8.3 & Extensions
echo "[3/7] Menginstall PHP 8.3-FPM & Extensions..."
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update -y
sudo apt-get install -y php8.3 php8.3-fpm php8.3-cli php8.3-common php8.3-pgsql \
    php8.3-mbstring php8.3-xml php8.3-bcmath php8.3-curl php8.3-zip php8.3-intl \
    php8.3-gd php8.3-opcache php8.3-readline

# 4. Install Composer
echo "[4/7] Menginstall Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

# 5. Install PostgreSQL 16
echo "[5/7] Menginstall PostgreSQL 16..."
sudo apt-get install -y postgresql postgresql-contrib

# 6. Setup PostgreSQL User & Database
echo "[6/7] Menyiapkan database PostgreSQL 'sigma_erp'..."
sudo -u postgres psql -c "DO \$\$ BEGIN IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = 'sigma_user') THEN CREATE ROLE sigma_user WITH LOGIN PASSWORD 'SigmaPassword8855'; END IF; END \$\$;"
sudo -u postgres psql -c "SELECT 'CREATE DATABASE sigma_erp OWNER sigma_user' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'sigma_erp')\gexec"
sudo -u postgres psql -d sigma_erp -c "CREATE SCHEMA IF NOT EXISTS sigma AUTHORIZATION sigma_user;"

# 7. Configure Permissions
echo "[7/7] Mengatur izin folder direktori..."
WEB_ROOT="/var/www/sigma-erp"
if [ -d "$WEB_ROOT" ]; then
    sudo chown -R www-data:www-data "$WEB_ROOT"
    sudo chmod -R 775 "$WEB_ROOT/storage" "$WEB_ROOT/bootstrap/cache"
fi

echo ""
echo "============================================================"
echo "  [SUKSES] Stack Server Siap Digunakan!"
echo "  Web Server: Nginx (Port 80/443)"
echo "  Backend: PHP 8.3-FPM"
echo "  Database: PostgreSQL (Database: sigma_erp, User: sigma_user)"
echo "============================================================"
