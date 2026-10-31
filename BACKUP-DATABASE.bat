@echo off
setlocal enabledelayedexpansion
title SIGMA ERP - Backup Database PostgreSQL
color 0B

echo ===============================================================================
echo                SIGMA ERP - 1-CLICK DATABASE BACKUP ENGINE
echo ===============================================================================
echo.

cd /d "%~dp0"

:: 1. Lokasi Folder Backup Terpadu (sama dengan UI MaintenanceController)
set "BACKUP_DIR=storage\app\backups"
if not exist "%BACKUP_DIR%" (
    mkdir "%BACKUP_DIR%"
)

:: 2. Format Timestamp YYYYMMDD_HHMMSS via PowerShell Get-Date
set "TIMESTAMP="
for /f "usebackq delims=" %%a in (`powershell -NoProfile -Command "Get-Date -Format 'yyyyMMdd_HHmmss'"`) do set "TIMESTAMP=%%a"
if not defined TIMESTAMP (
    color 0C
    echo [ERROR] Gagal mendapatkan timestamp dari PowerShell Get-Date!
    pause
    exit /b 1
)
set "BACKUP_FILE=%BACKUP_DIR%\sigma_erp_backup_!TIMESTAMP!.sql"

:: 3. Deteksi Binary pg_dump
set PG_DUMP=pg_dump
where pg_dump >nul 2>nul
if !errorlevel! neq 0 (
    if exist "C:\Program Files\PostgreSQL\18\bin\pg_dump.exe" (
        set PG_DUMP="C:\Program Files\PostgreSQL\18\bin\pg_dump.exe"
    ) else if exist "C:\Program Files\PostgreSQL\17\bin\pg_dump.exe" (
        set PG_DUMP="C:\Program Files\PostgreSQL\17\bin\pg_dump.exe"
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

if exist ".env" (
    for /f "tokens=1,2 delims==" %%a in (.env) do (
        if "%%a"=="DB_HOST" set DB_HOST=%%b
        if "%%a"=="DB_PORT" set DB_PORT=%%b
        if "%%a"=="DB_DATABASE" set DB_DATABASE=%%b
        if "%%a"=="DB_USERNAME" set DB_USERNAME=%%b
        if "%%a"=="DB_PASSWORD" set DB_PASSWORD=%%b
    )
)

set PGPASSWORD=%DB_PASSWORD%

echo [*] Mengekspor database: %DB_DATABASE% ...
echo [*] Host: %DB_HOST%:%DB_PORT% (User: %DB_USERNAME%)
echo [*] Menyimpan ke: %BACKUP_FILE%
echo.

%PG_DUMP% --clean --if-exists --no-owner -h %DB_HOST% -p %DB_PORT% -U %DB_USERNAME% -d %DB_DATABASE% -f "%BACKUP_FILE%"
set "DUMP_EXIT=!errorlevel!"

if !DUMP_EXIT! neq 0 (
    if exist "%BACKUP_FILE%" del /f /q "%BACKUP_FILE%"
    color 0C
    echo.
    echo [ERROR] Gagal melakukan backup database (Exit Code: !DUMP_EXIT!).
    echo Periksa apakah database %DB_DATABASE% ada dan password di file .env sesuai.
    pause
    exit /b !DUMP_EXIT!
)

set "FILE_SIZE=0"
for %%F in ("%BACKUP_FILE%") do set "FILE_SIZE=%%~zF"
if "!FILE_SIZE!"=="0" (
    if exist "%BACKUP_FILE%" del /f /q "%BACKUP_FILE%"
    color 0C
    echo.
    echo [ERROR] File backup kosong (0 byte).
    pause
    exit /b 1
)

color 0A
echo.
echo [SUKSES] Backup Database Berhasil Dibuat!
echo File: %BACKUP_FILE%
echo.
echo Anda dapat mengunduh atau memindahkan file backup ini ke komputer lain untuk restore.
echo.
pause
