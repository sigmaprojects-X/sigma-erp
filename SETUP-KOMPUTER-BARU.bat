@echo off
setlocal enabledelayedexpansion
title SIGMA ERP - Setup Komputer Server Baru
color 0B

echo ===============================================================================
echo        SIGMA ERP ^& POS - SETUP OTOMATIS KOMPUTER SERVER BARU
echo ===============================================================================
echo.
echo Script ini akan menyiapkan seluruh dependensi, file konfigurasi, database,
echo dan shortcut desktop agar aplikasi langsung siap digunakan di komputer ini.
echo.

cd /d "%~dp0"

:: 1. Cek PHP
where php >nul 2>nul
if %errorlevel% neq 0 (
    color 0C
    echo [ERROR] PHP tidak terdeteksi!
    echo Harap install PHP 8.2+ terlebih dahulu dan centang 'Add to PATH'.
    pause
    exit /b 1
)
echo [OK] PHP terdeteksi: 
php -v | findstr /i "php 8"
echo.

:: 2. Siapkan File .env
if not exist ".env" (
    echo [*] Membuat file .env dari template...
    copy .env.example .env >nul
    echo [*] Menggenerate APP_KEY...
    php artisan key:generate --force
) else (
    echo [OK] File .env sudah ada.
)

:: 3. Konfirmasi Password PostgreSQL
echo.
set /p DB_PASS="Masukkan Password PostgreSQL Anda [Tekan Enter jika default '885588']: "
if "%DB_PASS%"=="" set DB_PASS=885588

powershell -NoProfile -Command "(Get-Content .env) -replace '^DB_PASSWORD=.*', 'DB_PASSWORD=%DB_PASS%' | Set-Content .env"

:: 4. Buat Database sigma_erp Jika Belum Ada
echo [*] Memastikan database PostgreSQL 'sigma_erp' tersedia...
set PGPASSWORD=%DB_PASS%
where psql >nul 2>nul
if %errorlevel% equ 0 (
    psql -U postgres -h 127.0.0.1 -p 5432 -c "CREATE DATABASE sigma_erp;" >nul 2>nul
)

:: 5. Jalankan Migrasi Database
echo [*] Menjalankan migrasi database SIGMA ERP...
php artisan migrate --force
echo [OK] Struktur tabel database siap.

:: 6. Optimasi Cache Laravel
echo [*] Membersihkan dan mengoptimalkan cache aplikasi...
php artisan optimize:clear >nul 2>nul

:: 7. Buat Shortcut di Desktop Windows & Windows Startup Otomatis dengan Logo Resmi SIGMA HD
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
    "& ie4uinit.exe -show" >nul 2>nul

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
echo    - Anda tidak perlu lagi membuka folder atau menjalankan file .bat secara manual!
echo.
pause
