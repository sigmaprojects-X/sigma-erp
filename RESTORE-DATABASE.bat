@echo off
setlocal enabledelayedexpansion
title SIGMA ERP - Restore Database PostgreSQL
color 0E

echo ===============================================================================
echo                SIGMA ERP - 1-CLICK DATABASE RESTORE ENGINE
echo ===============================================================================
echo.

cd /d "%~dp0"

:: 1. Ambil Parameter dari .env (Koneksi & Folder Backup)
set DB_HOST=127.0.0.1
set DB_PORT=5432
set DB_DATABASE=sigma_erp
set DB_USERNAME=postgres
set DB_PASSWORD=885588
set SIGMA_BACKUP_DIR=

if exist ".env" (
    for /f "tokens=1,2 delims==" %%a in (.env) do (
        if "%%a"=="DB_HOST" set DB_HOST=%%b
        if "%%a"=="DB_PORT" set DB_PORT=%%b
        if "%%a"=="DB_DATABASE" set DB_DATABASE=%%b
        if "%%a"=="DB_USERNAME" set DB_USERNAME=%%b
        if "%%a"=="DB_PASSWORD" set DB_PASSWORD=%%b
        if "%%a"=="SIGMA_BACKUP_DIR" set SIGMA_BACKUP_DIR=%%b
    )
)

:: Lokasi Folder Backup Terpadu (bisa diatur via SIGMA_BACKUP_DIR)
set "BACKUP_DIR=storage\app\backups"
if defined SIGMA_BACKUP_DIR if not "!SIGMA_BACKUP_DIR!"=="" (
    set "BACKUP_DIR=!SIGMA_BACKUP_DIR!"
)
if not exist "%BACKUP_DIR%" (
    color 0C
    echo [ERROR] Folder '%BACKUP_DIR%' tidak ditemukan!
    pause
    exit /b 1
)

:: 2. Cari File Backup Terbaru (abaikan file safety backup otomatis)
set "LATEST_BACKUP="
for /f "tokens=*" %%a in ('dir /b /o-d "%BACKUP_DIR%\*.sql" 2^>nul') do (
    set "CANDIDATE=%%a"
    if /i not "!CANDIDATE:~0,19!"=="pre_restore_safety_" (
        if not defined LATEST_BACKUP set "LATEST_BACKUP=%BACKUP_DIR%\%%a"
    )
)

if not defined LATEST_BACKUP (
    color 0C
    echo [ERROR] Tidak ada file .sql di folder '%BACKUP_DIR%'!
    echo Silakan buat atau masukkan file backup database (.sql) ke folder '%BACKUP_DIR%'.
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

:: 3. Deteksi Binary psql & pg_dump
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
        echo [ERROR] psql.exe tidak ditemukan di PATH atau folder PostgreSQL default!
        pause
        exit /b 1
    )
)

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
        echo [ERROR] pg_dump.exe tidak ditemukan untuk membuat backup pengaman sebelum restore!
        pause
        exit /b 1
    )
)

set PGPASSWORD=%DB_PASSWORD%

:: 5. Buat Backup Pengaman (Safety Backup) Sebelum Restore memakai PowerShell Get-Date
set "TIMESTAMP="
for /f "usebackq delims=" %%a in (`powershell -NoProfile -Command "Get-Date -Format 'yyyyMMdd_HHmmss'"`) do set "TIMESTAMP=%%a"
if not defined TIMESTAMP (
    color 0C
    echo [ERROR] Gagal mendapatkan timestamp dari PowerShell Get-Date!
    pause
    exit /b 1
)

set "SAFETY_BACKUP=%BACKUP_DIR%\pre_restore_safety_!TIMESTAMP!.sql"
echo.
echo [*] Membuat backup pengaman sebelum restore ke: !SAFETY_BACKUP! ...
%PG_DUMP% --clean --if-exists --no-owner -h %DB_HOST% -p %DB_PORT% -U %DB_USERNAME% -d %DB_DATABASE% -f "!SAFETY_BACKUP!"
set "SAFETY_EXIT=!errorlevel!"

if !SAFETY_EXIT! neq 0 (
    if exist "!SAFETY_BACKUP!" del /f /q "!SAFETY_BACKUP!"
    color 0C
    echo [ERROR] Gagal membuat backup pengaman (Exit Code: !SAFETY_EXIT!). Proses restore dibatalkan demi keamanan data!
    pause
    exit /b !SAFETY_EXIT!
)

set "SAFETY_SIZE=0"
for %%F in ("!SAFETY_BACKUP!") do set "SAFETY_SIZE=%%~zF"
if "!SAFETY_SIZE!"=="0" (
    if exist "!SAFETY_BACKUP!" del /f /q "!SAFETY_BACKUP!"
    color 0C
    echo [ERROR] File backup pengaman kosong (0 byte). Proses restore dibatalkan demi keamanan data!
    pause
    exit /b 1
)

:: 6. Eksekusi Restore Database dengan ON_ERROR_STOP=1 tanpa membuang stderr ke nul
echo.
echo [*] Memulihkan database '%DB_DATABASE%' dari %LATEST_BACKUP% ...
%PSQL_BIN% -v ON_ERROR_STOP=1 -h %DB_HOST% -p %DB_PORT% -U %DB_USERNAME% -d %DB_DATABASE% -f "%LATEST_BACKUP%"
set "RESTORE_EXIT=!errorlevel!"

if !RESTORE_EXIT! equ 0 (
    color 0A
    echo.
    echo [SUKSES] Database SIGMA ERP Telah Berhasil Dipulihkan!
    echo Backup pengaman sebelum restore tersimpan di: !SAFETY_BACKUP!
) else (
    color 0C
    echo.
    echo [ERROR] Gagal memulihkan database (Exit Code: !RESTORE_EXIT!).
    echo Anda dapat memulihkan kondisi sebelumnya dari file backup pengaman: !SAFETY_BACKUP!
    pause
    exit /b !RESTORE_EXIT!
)

echo.
pause
