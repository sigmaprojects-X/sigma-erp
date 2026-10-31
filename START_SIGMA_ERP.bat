@echo off
title SIGMA ERP & POS Server
echo ======================================================
echo          MEMULAI APLIKASI SIGMA ERP & KASIR POS
echo ======================================================
echo.
cd /d "D:\PROJECTS SIGMA\SIGMA ERP"

:: Periksa apakah server sudah berjalan di port 8008
netstat -ano | findstr :8008 | findstr LISTENING >nul
if %errorlevel% equ 0 (
    echo [OK] Server SIGMA ERP sudah aktif di port 8008.
) else (
    echo [i] Menyalakan server aplikasi di http://localhost:8008...
    start /min "SIGMA_ERP_DAEMON" php artisan serve --host=0.0.0.0 --port=8008
    timeout /t 2 >nul
)

echo [OK] Membuka Layar Kasir di Google Chrome / Browser Utama...
start http://localhost:8008/pos

echo.
echo ======================================================
echo   Aplikasi SIGMA ERP & Kasir POS Berhasil Dibuka!
echo ======================================================
timeout /t 3 >nul
exit
