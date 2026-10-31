@echo off
title Menjalankan SIGMA ERP & POS...
cd /d "d:\PROJECTS SIGMA\SIGMA ERP"

:: Cek apakah port 8008 sudah aktif
netstat -ano | findstr :8008 | findstr LISTENING >nul
if %errorlevel% equ 0 (
    echo [INFO] Server SIGMA ERP sudah aktif di port 8008.
) else (
    echo [INFO] Menyalakan server SIGMA ERP di port 8008...
    start /b php artisan serve --host=0.0.0.0 --port=8008 >nul 2>&1
    timeout /t 2 /nobreak >nul
)

:: Buka browser otomatis ke layar Kasir / Login
start http://localhost:8008/login
exit
