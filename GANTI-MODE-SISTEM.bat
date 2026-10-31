@echo off
setlocal enabledelayedexpansion
title SIGMA ERP ^& POS - Pengalih Mode Sistem
color 0B

:MENU
cls
echo ===============================================================================
echo                SIGMA ERP ^& POS - PENGALIH MODE OPERASIONAL
echo ===============================================================================
echo.
cd /d "%~dp0"

where php >nul 2>nul
if %errorlevel% neq 0 (
    color 0C
    echo [ERROR] PHP tidak terdeteksi! Masukkan PHP ke PATH environment.
    pause
    exit /b 1
)

php artisan sigma:mode status
echo.
echo ===============================================================================
echo PILIH MODE SISTEM YANG INGIN DIAKTIFKAN:
echo ===============================================================================
echo  [1] Aktifkan MODE DEVELOPMENT (Uji coba, perbaikan bug, debug aktif, tanpa cache)
echo  [2] Aktifkan MODE PRODUCTION  (Kasir toko, transaksi super cepat, cache aktif)
echo  [3] Keluar
echo.
set /p PILIHAN="Masukkan pilihan [1/2/3]: "

if "%PILIHAN%"=="1" (
    cls
    color 0E
    php artisan sigma:mode dev
    echo.
    pause
    goto MENU
)

if "%PILIHAN%"=="2" (
    cls
    color 0A
    php artisan sigma:mode prod
    echo.
    pause
    goto MENU
)

if "%PILIHAN%"=="3" (
    exit /b 0
)

echo.
echo [!] Pilihan tidak valid, silakan coba lagi.
timeout /t 2 >nul
goto MENU
