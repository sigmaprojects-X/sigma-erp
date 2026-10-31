@echo off
setlocal enabledelayedexpansion
title SIGMA ERP - Backup Database PostgreSQL
color 0B

echo ===============================================================================
echo                SIGMA ERP - 1-CLICK DATABASE BACKUP ENGINE
echo ===============================================================================
echo.

cd /d "%~dp0"

:: 1. Buat Folder Backups jika belum ada
if not exist "backups" (
    mkdir backups
)

:: 2. Format Timestamp YYYYMMDD_HHMMSS
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "YYYY=!dt:~0,4!"
set "MM=!dt:~4,2!"
set "DD=!dt:~6,2!"
set "HH=!dt:~8,2!"
set "Min=!dt:~10,2!"
set "Sec=!dt:~12,2!"
set "BACKUP_FILE=backups\sigma_erp_backup_!YYYY!!MM!!DD!_!HH!!Min!!Sec!.sql"

:: 3. Deteksi Binary pg_dump
set PG_DUMP=pg_dump
where pg_dump >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\Program Files\PostgreSQL\18\bin\pg_dump.exe" (
        set PG_DUMP="C:\Program Files\PostgreSQL\18\bin\pg_dump.exe"
    ) else if exist "C:\Program Files\PostgreSQL\16\bin\pg_dump.exe" (
        set PG_DUMP="C:\Program Files\PostgreSQL\16\bin\pg_dump.exe"
    ) else if exist "C:\Program Files\PostgreSQL\15\bin\pg_dump.exe" (
        set PG_DUMP="C:\Program Files\PostgreSQL\15\bin\pg_dump.exe"
    ) else (
        color 0C
        echo [ERROR] pg_dump.exe tidak ditemukan di PATH atau folder PostgreSQL default!
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

echo [*] Mengekspor database: %DB_DATABASE% ...
echo [*] Host: %DB_HOST%:%DB_PORT% (User: %DB_USERNAME%)
echo [*] Menyimpan ke: %BACKUP_FILE%
echo.

%PG_DUMP% -h %DB_HOST% -p %DB_PORT% -U %DB_USERNAME% -d %DB_DATABASE% -F p -b -v -f "%BACKUP_FILE%" >nul 2>&1

if %errorlevel% equ 0 (
    color 0A
    echo [SUKSES] Backup Database Berhasil Dibuat!
    echo File: %BACKUP_FILE%
    echo.
    echo Anda dapat memindahkan file backup ini ke komputer lain untuk restore.
) else (
    color 0C
    echo [ERROR] Gagal melakukan backup database.
    echo Periksa apakah database %DB_DATABASE% ada dan password di file .env sesuai.
)

echo.
pause
