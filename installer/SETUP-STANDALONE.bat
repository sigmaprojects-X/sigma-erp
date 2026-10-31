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

:: 1. Cek Ketersediaan PHP
where php >nul 2>nul
if %errorlevel% neq 0 (
    color 0C
    echo [PERINGATAN] PHP belum terdeteksi di PATH sistem Windows!
    echo Harap pastikan PHP 8.2+ terinstall dan ditambahkan ke System Environment PATH.
    echo.
)

:: 2. Tentukan Default Drive & Folder Instalasi
set "DEFAULT_APP_DIR=C:\SIGMA-ERP"
if exist "D:\" (
    set "DEFAULT_DATA_DIR=D:\SIGMA-DATABASE"
) else (
    set "DEFAULT_DATA_DIR=C:\SIGMA-DATABASE"
)

:: 3. Jalankan GUI Pemilihan Folder jika PowerShell tersedia
set "CHOSEN_APP_DIR=%DEFAULT_APP_DIR%"
set "CHOSEN_DATA_DIR=%DEFAULT_DATA_DIR%"
set "CHOSEN_PORT=8008"
set "CHOSEN_PASS=885588"

echo -------------------------------------------------------------------------------
echo KONFIGURASI PENEMPATAN APLIKASI ^& DATABASE:
echo -------------------------------------------------------------------------------
echo [1] Lokasi Aplikasi default : %DEFAULT_APP_DIR%
echo [2] Lokasi Database default : %DEFAULT_DATA_DIR% (Aman dari Install Ulang Windows)
echo [3] Port Web Server default : %CHOSEN_PORT%
echo -------------------------------------------------------------------------------
echo.

set /p USER_APP="Tentukan Folder Instalasi Aplikasi [%DEFAULT_APP_DIR%]: "
if not "%USER_APP%"=="" set "CHOSEN_APP_DIR=%USER_APP%"

set /p USER_DATA="Tentukan Folder Penyimpanan Database [%DEFAULT_DATA_DIR%]: "
if not "%USER_DATA%"=="" set "CHOSEN_DATA_DIR=%USER_DATA%"

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
if not exist "%CHOSEN_APP_DIR%" mkdir "%CHOSEN_APP_DIR%"
if not exist "%CHOSEN_DATA_DIR%" mkdir "%CHOSEN_DATA_DIR%"

:: 5. Salin Berkas Aplikasi ke Folder Tujuan
echo [1/6] Menyalin seluruh berkas aplikasi SIGMA ERP ^& POS...
robocopy "%~dp0." "%CHOSEN_APP_DIR%" /E /XD ".git" "node_modules" "storage\logs" "backups" /XF "SETUP.bat" "SETUP-STANDALONE.bat" "*.zip" >nul
echo [OK] Berkas aplikasi berhasil disalin.

:: 6. Konfigurasi File .env di Folder Tujuan
echo [2/6] Mengonfigurasi file environment sistem (.env)...
cd /d "%CHOSEN_APP_DIR%"

if not exist ".env" (
    if exist ".env.example" (
        copy .env.example .env >nul
    )
)

powershell -NoProfile -Command ^
    "$env = Get-Content '.env'; " ^
    "$env = $env -replace '^APP_PORT=.*', 'APP_PORT=%CHOSEN_PORT%'; " ^
    "$env = $env -replace '^APP_URL=.*', 'APP_URL=http://localhost:%CHOSEN_PORT%'; " ^
    "$env = $env -replace '^DB_PASSWORD=.*', 'DB_PASSWORD=%CHOSEN_PASS%'; " ^
    "$env | Set-Content '.env'"

echo %CHOSEN_DATA_DIR% > "%CHOSEN_APP_DIR%\database_storage_path.txt"
echo [OK] Konfigurasi tersimpan (.env ^& database_storage_path.txt).

:: 7. Generate Application Key jika diperlukan
echo [3/6] Mengamankan Application Security Key...
where php >nul 2>nul
if %errorlevel% equ 0 (
    php artisan key:generate --force >nul 2>nul
    echo [OK] Application Security Key aktif.
)

:: 8. Jalankan Migrasi Database
echo [4/6] Menginisialisasi Database ^& Schema PostgreSQL...
where php >nul 2>nul
if %errorlevel% equ 0 (
    php artisan migrate --force >nul 2>nul
    echo [OK] Struktur tabel database siap.
)

:: 9. Optimasi Cache Laravel
echo [5/6] Mengoptimalkan performa cache sistem...
where php >nul 2>nul
if %errorlevel% equ 0 (
    php artisan optimize:clear >nul 2>nul
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
    "& ie4uinit.exe -show" >nul 2>nul

echo.
echo ===============================================================================
echo                INSTALASI SIGMA ERP ^& POS PRO BERHASIL 100%!
echo ===============================================================================
echo.
echo Folder Aplikasi : %CHOSEN_APP_DIR%
echo Folder Database : %CHOSEN_DATA_DIR%
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
