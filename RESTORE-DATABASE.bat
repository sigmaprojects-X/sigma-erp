@echo off
setlocal enabledelayedexpansion
title SIGMA ERP - Restore Database PostgreSQL
color 0E

echo ===============================================================================
echo                SIGMA ERP - 1-CLICK DATABASE RESTORE ENGINE
echo ===============================================================================
echo.

cd /d "%~dp0"

:: 1. Cek Folder Backups
if not exist "backups" (
    color 0C
    echo [ERROR] Folder 'backups' tidak ditemukan!
    pause
    exit /b 1
)

:: 2. Cari File Backup Terbaru
set LATEST_BACKUP=
for /f "tokens=*" %%a in ('dir /b /o-d "backups\*.sql" 2^>nul') do (
    if not defined LATEST_BACKUP set "LATEST_BACKUP=backups\%%a"
)

if not defined LATEST_BACKUP (
    color 0C
    echo [ERROR] Tidak ada file .sql di folder 'backups'!
    echo Silakan masukkan file backup database (.sql) ke folder 'backups'.
    pause
    exit /b 1
)

echo File backup terbaru yang ditemukan:
echo [!] %LATEST_BACKUP%
echo.
echo PERINGATAN: Memulihkan database akan menimpa data yang saat ini ada di database!
set /p CONFIRM="Ketik 'RESTORE' lalu tekan Enter untuk melanjutkan: "
if /i not "%CONFIRM%"=="RESTORE" (
    echo [BATAL] Pemulihan database dibatalkan.
    pause
    exit /b 0
)

:: 3. Deteksi Binary psql
set PSQL_BIN=psql
where psql >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\Program Files\PostgreSQL\18\bin\psql.exe" (
        set PSQL_BIN="C:\Program Files\PostgreSQL\18\bin\psql.exe"
    ) else if exist "C:\Program Files\PostgreSQL\16\bin\psql.exe" (
        set PSQL_BIN="C:\Program Files\PostgreSQL\16\bin\psql.exe"
    ) else if exist "C:\Program Files\PostgreSQL\15\bin\psql.exe" (
        set PSQL_BIN="C:\Program Files\PostgreSQL\15\bin\psql.exe"
    ) else (
        color 0C
        echo [ERROR] psql.exe tidak ditemukan di PATH atau folder PostgreSQL default!
        pause
        exit /b 1
    )
)

:: 4. Ambil Parameter dari .env
set DB_HOST=127.0.0.1
set DB_PORT=5432
set DB_DATABASE=sigma_erp
set DB_USERNAME=postgres
set DB_PASSWORD=885588

for /f "tokens=1,2 delims==" %%a in (.env) do (
    if "%%a"=="DB_HOST" set DB_HOST=%%b
    if "%%a"=="DB_PORT" set DB_PORT=%%b
    if "%%a"=="DB_DATABASE" set DB_DATABASE=%%b
    if "%%a"=="DB_USERNAME" set DB_USERNAME=%%b
    if "%%a"=="DB_PASSWORD" set DB_PASSWORD=%%b
)

set PGPASSWORD=%DB_PASSWORD%

echo.
echo [*] Memulihkan database '%DB_DATABASE%' dari %LATEST_BACKUP% ...
%PSQL_BIN% -h %DB_HOST% -p %DB_PORT% -U %DB_USERNAME% -d %DB_DATABASE% -f "%LATEST_BACKUP%" >nul 2>&1

if %errorlevel% equ 0 (
    color 0A
    echo.
    echo [SUKSES] Database SIGMA ERP Telah Berhasil Dipulihkan!
    echo Seluruh master data, transaksi, stok, dan akun telah aktif kembali.
) else (
    color 0C
    echo.
    echo [ERROR] Gagal memulihkan database. Periksa hak akses user PostgreSQL.
)

echo.
pause
