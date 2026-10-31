@echo off
setlocal enabledelayedexpansion
title SIGMA ERP ^& POS - Mode Production
color 0A

echo ===============================================================================
echo           SIGMA ERP ^& POS - AKTIFKAN MODE PRODUCTION (KASIR TOKO)
echo ===============================================================================
echo.
echo Mode Production digunakan untuk:
echo   - Komputer kasir harian dan cabang toko yang siap operasional
echo   - Transaksi kasir kilat (rute, config, dan tampilan Blade di-cache di RAM)
echo   - Keamanan maksimal (password DB ^& informasi sistem terkunci dari kebocoran)
echo.

cd /d "%~dp0"

where php >nul 2>nul
if %errorlevel% neq 0 (
    color 0C
    echo [ERROR] PHP tidak terdeteksi! Masukkan PHP ke PATH environment.
    pause
    exit /b 1
)

php artisan sigma:mode prod
echo.
echo ===============================================================================
echo [SELESAI] Sistem SIGMA ERP siap digunakan dalam Mode Production di Kasir Toko.
echo ===============================================================================
echo.
pause
