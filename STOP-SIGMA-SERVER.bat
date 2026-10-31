@echo off
setlocal enabledelayedexpansion
title Hentikan SIGMA ERP Server
color 0C

cd /d "%~dp0"

echo ===============================================================================
echo                MENGHENTIKAN SIGMA ERP ^& POS SERVER
echo ===============================================================================
echo.

echo [*] Mencari proses PHP server pada port 8008...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8008 ^| findstr LISTENING') do (
    echo [*] Menghentikan proses server PID: %%a
    taskkill /F /T /PID %%a >nul 2>nul
)

echo [*] Menghentikan proses background scheduler (php artisan schedule:work)...
if exist "storage\framework\scheduler.pid" (
    set /p SCHED_PID=<"storage\framework\scheduler.pid"
    if defined SCHED_PID (
        echo [*] Menghentikan proses scheduler PID: !SCHED_PID!
        taskkill /F /T /PID !SCHED_PID! >nul 2>nul
    )
    del /f /q "storage\framework\scheduler.pid" >nul 2>nul
)

for /f "tokens=*" %%p in ('powershell -NoProfile -Command "Get-CimInstance Win32_Process | Where-Object { $_.CommandLine -like '*artisan*schedule:work*' } | Select-Object -ExpandProperty ProcessId"') do (
    echo [*] Menghentikan proses scheduler PID: %%p
    taskkill /F /T /PID %%p >nul 2>nul
)

echo.
echo [OK] Server SIGMA ERP dan background scheduler telah berhasil dihentikan.
echo.
timeout /t 3 >nul
exit /b 0
