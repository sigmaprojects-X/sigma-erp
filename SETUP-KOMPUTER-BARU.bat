@echo off
setlocal enabledelayedexpansion
title SIGMA ERP - Setup Komputer Server Baru
color 0B

echo ===============================================================================
echo        SIGMA ERP ^& POS - SETUP OTOMATIS KOMPUTER SERVER BARU
echo ===============================================================================
echo.
echo Script ini akan menyiapkan seluruh dependensi, file konfigurasi, database,
echo data awal (seeder), dan shortcut desktop agar aplikasi siap digunakan.
echo.

cd /d "%~dp0"

:: 1. Cek PHP 8.3+ (Bundled Portable PHP atau System)
if exist "%~dp0php\php.exe" (
    set "PATH=%~dp0php;!PATH!"
    echo [OK] Menggunakan Bundled Portable PHP 8.3 bawaan paket:
    "%~dp0php\php.exe" -v | findstr /i "php 8"
    echo.
) else (
    where php >nul 2>nul
    if !errorlevel! neq 0 (
        color 0C
        echo [ERROR] PHP tidak terdeteksi!
        echo Harap install PHP 8.3+ terlebih dahulu dan centang 'Add to PATH'.
        pause
        exit /b 1
    )
    php -r "exit(version_compare(PHP_VERSION, '8.3.0', '>=') ? 0 : 1);"
    if !errorlevel! neq 0 (
        color 0C
        echo [ERROR] Versi PHP terlalu lama! SIGMA ERP membutuhkan PHP 8.3+.
        php -v
        pause
        exit /b 1
    )
    echo [OK] PHP 8.3+ terdeteksi di PATH:
    php -v | findstr /i "php 8"
    echo.
)

:: 2. Siapkan File .env
if not exist ".env" (
    if not exist ".env.example" (
        color 0C
        echo [ERROR] File .env.example tidak ditemukan!
        pause
        exit /b 1
    )
    echo [*] Membuat file .env dari template .env.example...
    copy /y ".env.example" ".env" >nul
    if !errorlevel! neq 0 (
        color 0C
        echo [ERROR] Gagal menyalin .env.example ke .env!
        pause
        exit /b 1
    )
) else (
    echo [OK] File .env sudah tersedia.
)

:: 3. Wizard Konfigurasi Interaktif (Port, Password DB, dan Folder Backup)
echo -------------------------------------------------------------------------------
echo KONFIGURASI SERVER & FOLDER PENYIMPANAN BACKUP:
echo -------------------------------------------------------------------------------
set "SRV_PORT=8008"
set /p SRV_PORT="Port Web Server [Tekan Enter jika default '8008']: "
if "!SRV_PORT!"=="" set "SRV_PORT=8008"

set "DB_PASS=885588"
set /p DB_PASS="Password Database PostgreSQL [Tekan Enter jika default '885588']: "
if "!DB_PASS!"=="" set "DB_PASS=885588"

set "DEF_BACKUP=D:\SIGMA_BACKUP"
if not exist "D:\" set "DEF_BACKUP=%~dp0storage\app\backups"
set "CHOSEN_BACKUP=!DEF_BACKUP!"
set /p CHOSEN_BACKUP="Folder Penyimpanan Backup Database [Tekan Enter jika '!DEF_BACKUP!']: "
if "!CHOSEN_BACKUP!"=="" set "CHOSEN_BACKUP=!DEF_BACKUP!"

if not exist "!CHOSEN_BACKUP!" (
    mkdir "!CHOSEN_BACKUP!" 2>nul
)

echo.
echo [*] Menyimpan konfigurasi ke file .env...
set "SIGMA_ENV_PORT=!SRV_PORT!"
set "SIGMA_ENV_DB_PASS=!DB_PASS!"
set "SIGMA_ENV_BACKUP=!CHOSEN_BACKUP!"
php -r "$file = '.env'; $lines = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES) : []; $port = getenv('SIGMA_ENV_PORT') ?: '8008'; $backup = getenv('SIGMA_ENV_BACKUP') ?: ''; $updates = ['APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_PORT' => $port, 'APP_URL' => 'http://localhost:' . $port, 'DB_CONNECTION' => 'pgsql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '5432', 'DB_DATABASE' => 'sigma_erp', 'DB_USERNAME' => 'postgres', 'DB_PASSWORD' => getenv('SIGMA_ENV_DB_PASS') ?: '', 'DB_SCHEMA' => 'sigma', 'SIGMA_BACKUP_DIR' => $backup]; $seen = []; foreach ($lines as $i => $line) { if (preg_match('/^\s*#?\s*([A-Z0-9_]+)\s*=/', $line, $m)) { $k = $m[1]; if (array_key_exists($k, $updates)) { $lines[$i] = $k . '=' . $updates[$k]; $seen[$k] = true; } } } foreach ($updates as $k => $v) { if (!isset($seen[$k])) { $lines[] = $k . '=' . $v; } } file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);"
if !errorlevel! neq 0 (
    color 0C
    echo [ERROR] Gagal menulis konfigurasi database ke file .env!
    pause
    exit /b 1
)

:: Buka Port Firewall Windows Otomatis untuk Jaringan LAN/WiFi
netsh advfirewall firewall add rule name="SIGMA ERP Server (Port !SRV_PORT!)" protocol=TCP dir=in localport=!SRV_PORT! action=allow >nul 2>nul

echo [*] Menggenerate APP_KEY...
php artisan key:generate --force
if !errorlevel! neq 0 (
    color 0C
    echo [ERROR] Gagal membuat APP_KEY Laravel!
    pause
    exit /b 1
)

:: 4. Deteksi Binary psql & Buat Database sigma_erp
set PSQL_BIN=psql
where psql >nul 2>nul
if !errorlevel! neq 0 (
    if exist "C:\Program Files\PostgreSQL\18\bin\psql.exe" (
        set PSQL_BIN="C:\Program Files\PostgreSQL\18\bin\psql.exe"
    ) else if exist "C:\Program Files\PostgreSQL\17\bin\psql.exe" (
        set PSQL_BIN="C:\Program Files\PostgreSQL\17\bin\psql.exe"
    ) else if exist "C:\Program Files\PostgreSQL\16\bin\psql.exe" (
        set PSQL_BIN="C:\Program Files\PostgreSQL\16\bin\psql.exe"
    ) else if exist "C:\Program Files\PostgreSQL\15\bin\psql.exe" (
        set PSQL_BIN="C:\Program Files\PostgreSQL\15\bin\psql.exe"
    ) else (
        color 0C
        echo [ERROR] psql.exe tidak ditemukan! Pastikan PostgreSQL 15+ sudah terinstall.
        pause
        exit /b 1
    )
)

echo [*] Memastikan database PostgreSQL 'sigma_erp' dan schema 'sigma' tersedia...
set "PGPASSWORD=!DB_PASS!"
%PSQL_BIN% -v ON_ERROR_STOP=1 -U postgres -h 127.0.0.1 -p 5432 -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname = 'sigma_erp'" | findstr /c:"1" >nul
if !errorlevel! neq 0 (
    %PSQL_BIN% -v ON_ERROR_STOP=1 -U postgres -h 127.0.0.1 -p 5432 -d postgres -c "CREATE DATABASE sigma_erp;"
    if !errorlevel! neq 0 (
        color 0C
        echo [ERROR] Gagal membuat database 'sigma_erp'. Periksa apakah service PostgreSQL berjalan dan password benar!
        pause
        exit /b 1
    )
)

%PSQL_BIN% -v ON_ERROR_STOP=1 -U postgres -h 127.0.0.1 -p 5432 -d sigma_erp -c "CREATE SCHEMA IF NOT EXISTS sigma;"
if !errorlevel! neq 0 (
    color 0C
    echo [ERROR] Gagal menginisialisasi schema 'sigma' pada database 'sigma_erp'!
    pause
    exit /b 1
)

:: 5. Jalankan Migrasi Database & Seeder Awal
echo [*] Menjalankan migrasi database SIGMA ERP...
php artisan migrate --force
if !errorlevel! neq 0 (
    color 0C
    echo [ERROR] Gagal menjalankan migrasi database! Proses setup dihentikan.
    pause
    exit /b 1
)
echo [OK] Struktur tabel database siap.

echo [*] Menjalankan seeder data awal (User, Perusahaan, Toko, CoA)...
php artisan db:seed --force
if !errorlevel! neq 0 (
    color 0C
    echo [ERROR] Gagal menjalankan seeder data awal (db:seed)! Proses setup dihentikan.
    pause
    exit /b 1
)
echo [OK] Data awal dan akun pengguna berhasil disiapkan.

:: 6. Optimasi Cache Laravel
echo [*] Membersihkan dan mengoptimalkan cache aplikasi...
php artisan optimize:clear
if !errorlevel! neq 0 (
    color 0C
    echo [ERROR] Gagal membersihkan cache aplikasi!
    pause
    exit /b 1
)

:: 7. Buat Shortcut di Desktop Windows & Windows Startup Otomatis
echo [*] Membuat Shortcut di Desktop Windows & Mengaktifkan Auto-Start Komputer...
powershell -NoProfile -Command ^
    "$ws = New-Object -ComObject WScript.Shell; " ^
    "$desktop = [Environment]::GetFolderPath('Desktop'); " ^
    "$startup = [Environment]::GetFolderPath('Startup'); " ^
    "$icon = '%~dp0public\sigma.ico'; " ^
    "$as = $ws.CreateShortcut([System.IO.Path]::Combine($startup, 'SIGMA_ERP_AutoStart.lnk')); " ^
    "$as.TargetPath = '%~dp0start_server_silent.vbs'; " ^
    "$as.WorkingDirectory = '%~dp0'; " ^
    "$as.Save(); " ^
    "$s1 = $ws.CreateShortcut([System.IO.Path]::Combine($desktop, 'SIGMA ERP & POS.lnk')); " ^
    "$s1.TargetPath = '%~dp0START_SIGMA_ERP.vbs'; " ^
    "$s1.WorkingDirectory = '%~dp0'; " ^
    "$s1.IconLocation = $icon + ',0'; " ^
    "$s1.Description = 'Buka Dashboard SIGMA ERP & POS (Otomatis Nyalakan Server)'; " ^
    "$s1.Save(); " ^
    "$s2 = $ws.CreateShortcut([System.IO.Path]::Combine($desktop, 'SIGMA KASIR & POS.lnk')); " ^
    "$s2.TargetPath = '%~dp0START_SIGMA_KASIR.vbs'; " ^
    "$s2.WorkingDirectory = '%~dp0'; " ^
    "$s2.IconLocation = $icon + ',0'; " ^
    "$s2.Description = 'Buka Layar Kasir SIGMA POS Pro (Otomatis Nyalakan Server)'; " ^
    "$s2.Save(); " ^
    "$s3 = $ws.CreateShortcut([System.IO.Path]::Combine($desktop, 'SIGMA POS Kasir (Kiosk).lnk')); " ^
    "$s3.TargetPath = '%~dp0START-KASIR-KIOSK.bat'; " ^
    "$s3.WorkingDirectory = '%~dp0'; " ^
    "$s3.IconLocation = $icon + ',0'; " ^
    "$s3.Description = 'Layar Kasir Fullscreen Kiosk'; " ^
    "$s3.Save(); " ^
    "& ie4uinit.exe -show"

echo.
echo ===============================================================================
echo                   SETUP KOMPUTER BARU SUKSES 100%!
echo ===============================================================================
echo.
echo 1. Shortcut Resmi Berlogo SIGMA telah dibuat di Desktop:
echo    - SIGMA ERP & POS (Dashboard Back-Office - Otomatis Nyalakan Server)
echo    - SIGMA KASIR & POS (Layar Kasir Standar - Otomatis Nyalakan Server)
echo    - SIGMA POS Kasir (Kiosk) (Layar Kasir Cepat / Fullscreen)
echo 2. Auto-Start Windows telah AKTIF:
echo    - Server SIGMA akan OTOMATIS berjalan di background saat komputer dinyalakan.
echo.
pause
