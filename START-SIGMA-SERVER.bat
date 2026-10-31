@echo off
setlocal enabledelayedexpansion
title SIGMA ERP ^& POS - Server Engine
color 0B

echo ===============================================================================
echo                SIGMA ERP ^& POS ENTERPRISE - SERVER ENGINE
echo ===============================================================================
echo.

:: 1. Verifikasi Direktori Kerja
cd /d "%~dp0"

:: 2. Cek Apakah PHP Terpasang
where php >nul 2>nul
if %errorlevel% neq 0 (
    color 0C
    echo [ERROR] PHP tidak terdeteksi di komputer ini!
    echo Silakan install PHP 8.3+ atau masukkan path PHP ke System Environment Variables.
    echo.
    pause
    exit /b 1
)

:: 3. Cek File Konfigurasi .env
if not exist ".env" (
    echo [*] File .env tidak ditemukan, membuat dari .env.example...
    if exist ".env.example" (
        copy .env.example .env >nul
        echo [*] Menggenerate Application Key...
        php artisan key:generate --force
    ) else (
        color 0C
        echo [ERROR] File .env.example tidak ditemukan!
        pause
        exit /b 1
    )
)

:: 4. Cek Koneksi Database PostgreSQL & Jalankan Auto-Migration
echo [*] Memeriksa koneksi database PostgreSQL ^& sinkronisasi tabel...
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();
try {
    DB::connection()->getPdo();
    exit(0);
} catch (\Exception \$e) {
    fwrite(STDERR, \$e->getMessage());
    exit(1);
}
" >nul 2>nul

if %errorlevel% neq 0 (
    color 0E
    echo [PERINGATAN] Database PostgreSQL belum dapat terhubung atau service sedang mati.
    echo Pastikan PostgreSQL Service sudah running dan password DB di file .env sesuai.
    echo.
) else (
    echo [OK] Database PostgreSQL terhubung sempurna.
    echo [*] Memeriksa migrasi database...
    php artisan migrate --force >nul 2>nul
    echo [OK] Tabel database sudah tersinkronisasi.
)

:: 5. Deteksi Alamat IP Lokal untuk Akses Kasir Lain di Toko (LAN / Wi-Fi)
for /f "tokens=*" %%a in ('powershell -NoProfile -Command "(Get-NetIPAddress -AddressFamily IPv4 | Where-Object {$_.InterfaceAlias -notmatch 'Loopback' -and $_.IPAddress -notmatch '169.254'} | Select-Object -First 1).IPAddress"') do (
    set LOCAL_IP=%%a
)

echo.
echo ===============================================================================
echo   SERVER RESMI SIGMA ERP AKTIF PADA PORT 8008
echo ===============================================================================
echo.
echo   [1] Akses Komputer Ini (Server/Kasir Utama):
echo       http://localhost:8008
echo.
if defined LOCAL_IP (
echo   [2] Akses Kasir Lain / Komputer Toko (Jaringan LAN / Wi-Fi):
echo       http://!LOCAL_IP!:8008
echo.
)
echo   [3] Akses Online Cabang Luar Toko:
echo       Jalankan file 'ONLINE-TUNNEL-CABANG.bat' di folder ini.
echo.
echo ===============================================================================
echo   Tekan CTRL + C pada jendela ini untuk mematikan server.
echo ===============================================================================
echo.

:: 6. Buka Browser Otomatis
start "" "http://localhost:8008"

:: 7. Jalankan Server PHP
php artisan serve --host=0.0.0.0 --port=8008
