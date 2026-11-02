#!/bin/bash
set -e

# 1. Database Setup
sudo -u postgres psql -c "ALTER USER postgres PASSWORD 'SigmaPostgres2026';"
sudo -u postgres psql -tc "SELECT 1 FROM pg_database WHERE datname = 'sigma_erp'" | grep -q 1 || sudo -u postgres psql -c "CREATE DATABASE sigma_erp;"
sudo -u postgres psql -d sigma_erp -c "CREATE SCHEMA IF NOT EXISTS sigma;"

# 2. Setup Nginx Virtual Host
sudo tee /etc/nginx/sites-available/sigma-erp << 'NGINX_CONF'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name pos.sigmajayanusantara.biz.id erp.sigmajayanusantara.biz.id 103.197.190.171 _;
    root /var/www/sigma-erp/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html;
    charset utf-8;

    client_max_body_size 64M;

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
NGINX_CONF

sudo ln -sf /etc/nginx/sites-available/sigma-erp /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# 3. Setup web folder ownership
sudo mkdir -p /var/www/sigma-erp
sudo chown -R ubuntu:www-data /var/www/sigma-erp
sudo chmod -R 775 /var/www/sigma-erp

echo "VPS_BASE_SETUP_DONE"
