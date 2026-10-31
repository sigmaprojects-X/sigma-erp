@echo off
setlocal enabledelayedexpansion
title SIGMA ERP ^& POS - Mode Development
color 0E

echo ===============================================================================
echo            SIGMA ERP ^& POS - AKTIFKAN MODE DEVELOPMENT (UJI COBA)
echo ===============================================================================
echo.
echo Mode Development digunakan untuk:
echo   - Memperbaiki bug dan menambah fitur baru
echo   - Menguji aplikasi (perubahan kode langsung aktif di browser)
echo   - Menampilkan detail error log untuk penelusuran kendala
echo.

cd /d "%~dp0"

where php >nul 2>nul
if %errorlevel% neq 0 (
    color 0C
    echo [ERROR] PHP tidak terdeteksi! Masukkan PHP ke PATH environment.
    pause
    exit /b 1
)

php artisan sigma:mode dev
echo.
echo ===============================================================================
echo [SELESAI] Sistem SIGMA ERP siap digunakan dalam Mode Development.
echo ===============================================================================
echo.
pause
