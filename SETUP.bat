@echo off
setlocal enabledelayedexpansion
title SIGMA ERP & POS - Windows Setup Wizard
color 0B

echo ===============================================================================
echo            SIGMA ERP ^& POS PRO - WIZARD INSTALASI KOMERSIL
echo ===============================================================================
echo.
echo Selamat datang di Setup Wizard SIGMA ERP ^& POS Pro.
echo Program ini akan memasang aplikasi dan mengonfigurasi database di komputer ini.
echo.

cd /d "%~dp0"

:: 1. Cek Ketersediaan PHP 8.3+
where php >nul 2>nul
if !errorlevel! neq 0 (
    color 0C
    echo [ERROR] PHP belum terdeteksi di PATH sistem Windows!
    echo Harap pastikan PHP 8.3+ terinstall dan ditambahkan ke System Environment PATH.
    pause
    exit /b 1
)

php -r "exit(version_compare(PHP_VERSION, '8.3.0', '>=') ? 0 : 1);"
if !errorlevel! neq 0 (
    color 0C
    echo [ERROR] Versi PHP tidak memenuhi syarat! SIGMA ERP membutuhkan PHP 8.3+.
    php -v
    pause
    exit /b 1
)

:: 2. Deteksi Binary psql
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

:: 3. Tentukan Default Folder Instalasi & Parameter
set "DEFAULT_APP_DIR=C:\SIGMA-ERP"
set "CHOSEN_APP_DIR=%DEFAULT_APP_DIR%"
set "CHOSEN_PORT=8008"
set "CHOSEN_PASS=885588"

echo -------------------------------------------------------------------------------
echo KONFIGURASI PENEMPATAN APLIKASI ^& KONEKSI DATABASE:
echo -------------------------------------------------------------------------------
echo [1] Lokasi Aplikasi default : %DEFAULT_APP_DIR%
echo [2] Port Web Server default : %CHOSEN_PORT%
echo -------------------------------------------------------------------------------
echo.

set /p USER_APP="Tentukan Folder Instalasi Aplikasi [%DEFAULT_APP_DIR%]: "
if not "%USER_APP%"=="" set "CHOSEN_APP_DIR=%USER_APP%"

set /p USER_PORT="Tentukan Port Aplikasi [%CHOSEN_PORT%]: "
if not "%USER_PORT%"=="" set "CHOSEN_PORT=%USER_PORT%"

set /p USER_PASS="Password Database PostgreSQL [%CHOSEN_PASS%]: "
if not "%USER_PASS%"=="" set "CHOSEN_PASS=%USER_PASS%"

echo.
echo ===============================================================================
echo MEMULAI PROSES INSTALASI KE: %CHOSEN_APP_DIR%
echo ===============================================================================
echo.

:: 4. Buat Direktori Tujuan
if not exist "%CHOSEN_APP_DIR%" (
    mkdir "%CHOSEN_APP_DIR%"
    if !errorlevel! neq 0 (
        color 0C
        echo [ERROR] Gagal membuat folder instalasi: %CHOSEN_APP_DIR%
        pause
        exit /b 1
    )
)

:: 5. Salin Berkas Aplikasi ke Folder Tujuan
echo [1/6] Menyalin seluruh berkas aplikasi SIGMA ERP ^& POS...
robocopy "%~dp0." "%CHOSEN_APP_DIR%" /E /XD ".git" "node_modules" "storage\logs" "backups" /XF "SETUP.bat" "SETUP-STANDALONE.bat" "*.zip" >nul
if !errorlevel! geq 8 (
    color 0C
    echo [ERROR] Gagal menyalin berkas aplikasi ke %CHOSEN_APP_DIR%!
    pause
    exit /b 1
)
echo [OK] Berkas aplikasi berhasil disalin.

:: 6. Konfigurasi File .env di Folder Tujuan Secara Deterministik
echo [2/6] Mengonfigurasi file environment sistem (.env)...
cd /d "%CHOSEN_APP_DIR%"

if not exist ".env" (
    if exist ".env.example" (
        copy /y ".env.example" ".env" >nul
        if !errorlevel! neq 0 (
            color 0C
            echo [ERROR] Gagal membuat file .env dari .env.example!
            pause
            exit /b 1
        )
    ) else (
        color 0C
        echo [ERROR] File .env.example tidak ditemukan di %CHOSEN_APP_DIR%!
        pause
        exit /b 1
    )
)

set "SIGMA_ENV_PORT=!CHOSEN_PORT!"
set "SIGMA_ENV_DB_PASS=!CHOSEN_PASS!"
php -r "$file = '.env'; $lines = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES) : []; $port = getenv('SIGMA_ENV_PORT') ?: '8008'; $updates = ['APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_PORT' => $port, 'APP_URL' => 'http://localhost:' . $port, 'DB_CONNECTION' => 'pgsql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '5432', 'DB_DATABASE' => 'sigma_erp', 'DB_USERNAME' => 'postgres', 'DB_PASSWORD' => getenv('SIGMA_ENV_DB_PASS') ?: '', 'DB_SCHEMA' => 'sigma']; $seen = []; foreach ($lines as $i => $line) { if (preg_match('/^\s*#?\s*([A-Z0-9_]+)\s*=/', $line, $m)) { $k = $m[1]; if (array_key_exists($k, $updates)) { $lines[$i] = $k . '=' . $updates[$k]; $seen[$k] = true; } } } foreach ($updates as $k => $v) { if (!isset($seen[$k])) { $lines[] = $k . '=' . $v; } } file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);"
if !errorlevel! neq 0 (
    color 0C
    echo [ERROR] Gagal menulis konfigurasi ke file .env!
    pause
    exit /b 1
)
echo [OK] Konfigurasi tersimpan di .env.

:: 7. Generate Application Key
echo [3/6] Mengamankan Application Security Key...
php artisan key:generate --force
if !errorlevel! neq 0 (
    color 0C
    echo [ERROR] Gagal menjalankan php artisan key:generate!
    pause
    exit /b 1
)
echo [OK] Application Security Key aktif.

:: 8. Buat Database sigma_erp, Jalankan Migrasi & Seeder Awal
echo [4/6] Menginisialisasi Database, Schema ^& Data Awal PostgreSQL...
set "PGPASSWORD=!CHOSEN_PASS!"
%PSQL_BIN% -v ON_ERROR_STOP=1 -U postgres -h 127.0.0.1 -p 5432 -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname = 'sigma_erp'" | findstr /c:"1" >nul
if !errorlevel! neq 0 (
    %PSQL_BIN% -v ON_ERROR_STOP=1 -U postgres -h 127.0.0.1 -p 5432 -d postgres -c "CREATE DATABASE sigma_erp;"
    if !errorlevel! neq 0 (
        color 0C
        echo [ERROR] Gagal membuat database 'sigma_erp'. Pastikan PostgreSQL berjalan dan password benar!
        pause
        exit /b 1
    )
)

%PSQL_BIN% -v ON_ERROR_STOP=1 -U postgres -h 127.0.0.1 -p 5432 -d sigma_erp -c "CREATE SCHEMA IF NOT EXISTS sigma;"
if !errorlevel! neq 0 (
    color 0C
    echo [ERROR] Gagal membuat schema 'sigma' pada database 'sigma_erp'!
    pause
    exit /b 1
)

php artisan migrate --force
if !errorlevel! neq 0 (
    color 0C
    echo [ERROR] Migrasi tabel database gagal! Instalasi dihentikan.
    pause
    exit /b 1
)

php artisan db:seed --force
if !errorlevel! neq 0 (
    color 0C
    echo [ERROR] Seeding data awal (db:seed) gagal! Instalasi dihentikan.
    pause
    exit /b 1
)
echo [OK] Struktur tabel dan data awal (user/master) siap digunakan.

:: 9. Optimasi Cache Laravel
echo [5/6] Mengoptimalkan performa cache sistem...
php artisan optimize:clear
if !errorlevel! neq 0 (
    color 0C
    echo [ERROR] Gagal membersihkan cache sistem!
    pause
    exit /b 1
)

:: 10. Buat Shortcut Windows Desktop & Start Menu dengan Logo Resmi SIGMA HD
echo [6/6] Membuat Shortcut di Desktop Windows & Mengaktifkan Auto-Start...
powershell -NoProfile -Command ^
    "$ws = New-Object -ComObject WScript.Shell; " ^
    "$desktop = [Environment]::GetFolderPath('Desktop'); " ^
    "$startup = [Environment]::GetFolderPath('Startup'); " ^
    "$icon = '%CHOSEN_APP_DIR%\public\sigma.ico'; " ^
    "$as = $ws.CreateShortcut([System.IO.Path]::Combine($startup, 'SIGMA_ERP_AutoStart.lnk')); " ^
    "$as.TargetPath = '%CHOSEN_APP_DIR%\start_server_silent.vbs'; " ^
    "$as.WorkingDirectory = '%CHOSEN_APP_DIR%'; " ^
    "$as.Save(); " ^
    "$s1 = $ws.CreateShortcut([System.IO.Path]::Combine($desktop, 'SIGMA ERP & POS.lnk')); " ^
    "$s1.TargetPath = '%CHOSEN_APP_DIR%\START_SIGMA_ERP.vbs'; " ^
    "$s1.WorkingDirectory = '%CHOSEN_APP_DIR%'; " ^
    "$s1.IconLocation = $icon + ',0'; " ^
    "$s1.Description = 'Buka Dashboard SIGMA ERP & POS'; " ^
    "$s1.Save(); " ^
    "$s2 = $ws.CreateShortcut([System.IO.Path]::Combine($desktop, 'SIGMA KASIR & POS.lnk')); " ^
    "$s2.TargetPath = '%CHOSEN_APP_DIR%\START_SIGMA_KASIR.vbs'; " ^
    "$s2.WorkingDirectory = '%CHOSEN_APP_DIR%'; " ^
    "$s2.IconLocation = $icon + ',0'; " ^
    "$s2.Description = 'Buka Layar Kasir SIGMA POS Pro'; " ^
    "$s2.Save(); " ^
    "$s3 = $ws.CreateShortcut([System.IO.Path]::Combine($desktop, 'SIGMA POS Kasir (Kiosk).lnk')); " ^
    "$s3.TargetPath = '%CHOSEN_APP_DIR%\START-KASIR-KIOSK.bat'; " ^
    "$s3.WorkingDirectory = '%CHOSEN_APP_DIR%'; " ^
    "$s3.IconLocation = $icon + ',0'; " ^
    "$s3.Save(); " ^
    "$s4 = $ws.CreateShortcut([System.IO.Path]::Combine($desktop, 'SIGMA Backup Database.lnk')); " ^
    "$s4.TargetPath = '%CHOSEN_APP_DIR%\BACKUP-DATABASE.bat'; " ^
    "$s4.WorkingDirectory = '%CHOSEN_APP_DIR%'; " ^
    "$s4.IconLocation = $icon + ',0'; " ^
    "$s4.Save(); " ^
    "& ie4uinit.exe -show"

echo.
echo ===============================================================================
echo                INSTALASI SIGMA ERP ^& POS PRO BERHASIL 100%!
echo ===============================================================================
echo.
echo Folder Aplikasi : %CHOSEN_APP_DIR%
echo URL Aplikasi    : http://localhost:%CHOSEN_PORT%
echo.
echo Shortcut telah dibuat di Desktop Windows:
echo - SIGMA ERP Server (Dashboard Back-Office)
echo - SIGMA POS Kasir (Layar Kasir Cepat / Kiosk)
echo - SIGMA Backup Database (Pencadangan Instan)
echo.

set /p OPEN_NOW="Jalankan Server SIGMA Sekarang? (Y/N) [Default Y]: "
if /i "%OPEN_NOW%"=="" set OPEN_NOW=Y
if /i "%OPEN_NOW%"=="Y" (
    start "" "%CHOSEN_APP_DIR%\START-SIGMA-SERVER.bat"
)

exit /b 0
