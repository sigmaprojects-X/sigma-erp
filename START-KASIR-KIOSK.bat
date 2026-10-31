@echo off
setlocal enabledelayedexpansion
title SIGMA POS PRO - Layar Kasir Dedicated (Kiosk Silent Print)
color 0A

echo ===============================================================================
echo                SIGMA POS PRO - DEDICATED CASHIER KIOSK LAUNCHER
echo ===============================================================================
echo Mode ini membuka Layar Kasir tanpa bilah browser (App Window Mode) dan
echo mengaktifkan Cetak Struk Instan Otomatis (Silent Printing tanpa dialog print).
echo ===============================================================================
echo.

cd /d "%~dp0"

:: 1. Cek Apakah Server SIGMA Port 8008 Sedang Berjalan
netstat -ano | findstr ":8008" | findstr "LISTENING" >nul 2>nul
if %errorlevel% neq 0 (
    echo [*] Server SIGMA belum aktif di port 8008.
    echo [*] Menyalakan Server SIGMA di latar belakang...
    start "" "START-SIGMA-SERVER.bat"
    echo Menunggu server siap...
    timeout /t 3 >nul
) else (
    echo [OK] Server SIGMA port 8008 aktif.
)

:: 2. Deteksi Lokasi Browser Chrome atau Edge
set "BROWSER_EXE="

if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    set "BROWSER_EXE=C:\Program Files\Google\Chrome\Application\chrome.exe"
) else if exist "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" (
    set "BROWSER_EXE=C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
) else if exist "%LOCALAPPDATA%\Google\Chrome\Application\chrome.exe" (
    set "BROWSER_EXE=%LOCALAPPDATA%\Google\Chrome\Application\chrome.exe"
) else if exist "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" (
    set "BROWSER_EXE=C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
) else if exist "C:\Program Files\Microsoft\Edge\Application\msedge.exe" (
    set "BROWSER_EXE=C:\Program Files\Microsoft\Edge\Application\msedge.exe"
) else if exist "%LOCALAPPDATA%\Microsoft\Edge\Application\msedge.exe" (
    set "BROWSER_EXE=%LOCALAPPDATA%\Microsoft\Edge\Application\msedge.exe"
)

if defined BROWSER_EXE (
    echo [OK] Menggunakan browser: "!BROWSER_EXE!"
    echo.
    echo Membuka Layar Kasir POS dalam mode Dedicated App dengan Silent Printing...
    start "" "!BROWSER_EXE!" --app="http://localhost:8008/pos" --kiosk-printing
) else (
    echo [!] Browser Chrome/Edge tidak ditemukan di folder standar.
    echo Membuka menggunakan browser default Windows...
    start "" "http://localhost:8008/pos"
)

echo.
echo ===============================================================================
echo                     LAYAR KASIR POS SIAP DIGUNAKAN!
echo ===============================================================================
echo Tips:
echo 1. Pastikan printer thermal kasir (58mm/80mm) sudah dijadikan 'Default Printer' di Windows.
echo 2. Setiap kali kasir menekan tombol selesai bayar, struk akan langsung tercetak
echo    otomatis tanpa memunculkan dialog pop-up print.
echo 3. Laci uang (Cash Drawer) otomatis terbuka saat pembayaran tunai (CASH).
echo ===============================================================================
timeout /t 5 >nul
exit /b 0
