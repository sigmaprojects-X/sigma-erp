@echo off
title Hentikan SIGMA ERP Server
color 0C

echo ===============================================================================
echo                MENGHENTIKAN SIGMA ERP ^& POS SERVER
echo ===============================================================================
echo.

echo [*] Mencari proses PHP server pada port 8008...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8008 ^| findstr LISTENING') do (
    echo [*] Menghentikan proses PID: %%a
    taskkill /F /PID %%a >nul 2>nul
)

echo.
echo [OK] Server SIGMA ERP telah berhasil dihentikan.
echo.
timeout /t 3 >nul
exit /b 0
