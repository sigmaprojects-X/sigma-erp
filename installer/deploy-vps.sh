#!/bin/bash
# ==============================================================================
# SIGMA ERP & POS - 1-Click Production Server Provisioning Script
# Target: Ubuntu 22.04 / 24.04 LTS (Biznet Gio NEO Lite / Cloud VPS)
# ==============================================================================
set -e

echo "=========================================================="
echo "   MEMULAI INSTALASI SISTEM SIGMA ERP DI VPS BIZNET GIO   "
echo "=========================================================="

# 1. Update paket sistem
echo "[1/8] Memperbarui sistem operasi Ubuntu..."
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl git unzip ufw software-properties-common ca-certificates lsb-release

# 2. Tambahkan repositori resmi PHP 8.3 (Ondrej PPA)
echo "[2/8] Menambahkan repositori PHP 8.3..."
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# 3. Pasang PHP 8.3 + FPM + Ekstensi Lengkap
echo "[3/8] Memasang PHP 8.3 FPM & ekstensi yang dibutuhkan..."
sudo apt install -y php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl php8.3-gd php8.3-soap php8.3-readline

# 4. Pasang Composer
echo "[4/8] Memasang Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
fi

# 5. Pasang & Konfigurasi PostgreSQL 16
echo "[5/8] Memasang dan mengamankan PostgreSQL..."
sudo apt install -y postgresql postgresql-contrib

# Buat password postgres & database sigma_erp
sudo -u postgres psql -c "ALTER USER postgres PASSWORD 'SigmaPostgres2026!';"
sudo -u postgres psql -tc "SELECT 1 FROM pg_database WHERE datname = 'sigma_erp'" | grep -q 1 || sudo -u postgres psql -c "CREATE DATABASE sigma_erp;"
sudo -u postgres psql -d sigma_erp -c "CREATE SCHEMA IF NOT EXISTS sigma;"

# 6. Pasang & Konfigurasi Web Server Nginx
echo "[6/8] Memasang dan mengonfigurasi Nginx..."
sudo apt install -y nginx

sudo tee /etc/nginx/sites-available/sigma-erp << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name pos.sigmajayanusantara.biz.id erp.sigmajayanusantara.biz.id;
    root /var/www/sigma-erp/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

sudo ln -sf /etc/nginx/sites-available/sigma-erp /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# 7. Aktifkan Firewall Server
echo "[7/8] Mengaktifkan UFW Firewall..."
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
echo "y" | sudo ufw enable

# 8. Siapkan SSH Deploy Key untuk akses private repository GitHub
echo "[8/8] Menyiapkan kunci akses aman GitHub..."
if [ ! -f ~/.ssh/id_ed25519 ]; then
    ssh-keygen -t ed25519 -N "" -f ~/.ssh/id_ed25519 -C "vps-sigma@sigmajayanusantara.biz.id"
fi

echo ""
echo "=========================================================="
echo "    INSTALASI SERVER & ENGINE DATABASE BERHASIL 100%!     "
echo "=========================================================="
echo ""
echo "Salin KUNCI PUBLIK GITHUB (Deploy Key) di bawah ini:"
echo "----------------------------------------------------------"
cat ~/.ssh/id_ed25519.pub
echo "----------------------------------------------------------"
echo ""
