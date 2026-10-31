@echo off
setlocal enabledelayedexpansion
title SIGMA ERP - Online Tunnel Cabang (Cloudflare Secure Tunnel)
color 0A

echo ===============================================================================
echo          SIGMA ERP ^& POS - SECURE ONLINE TUNNEL UNTUK CABANG
echo ===============================================================================
echo.
echo Modul ini menghubungkan server SIGMA ERP lokal ke internet publik secara GRATIS,
echo AMAN (Enkripsi SSL/HTTPS), dan TANPA PERLU IP PUBLIK STATIS / PORT FORWARDING!
echo Cabang di luar toko atau HP staff dapat langsung mengakses ERP ^& POS dari link ini.
echo.

cd /d "%~dp0"

:: 1. Cek Apakah Server SIGMA Lokal Aktif
netstat -aon | findstr :8008 | findstr LISTENING >nul 2>nul
if %errorlevel% neq 0 (
    color 0E
    echo [PERINGATAN] Server SIGMA ERP pada port 8008 belum terdeteksi aktif!
    echo Pastikan file 'START-SIGMA-SERVER.bat' sudah dijalankan terlebih dahulu.
    echo.
    echo Menunggu 5 detik sebelum melanjutkan...
    timeout /t 5 >nul
)

:: 2. Cek Eksekusi Cloudflared
set CLOUDFLARED_BIN=cloudflared
where cloudflared >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\Program Files (x86)\cloudflared\cloudflared.exe" (
        set CLOUDFLARED_BIN="C:\Program Files (x86)\cloudflared\cloudflared.exe"
    ) else if exist "cloudflared.exe" (
        set CLOUDFLARED_BIN=".\cloudflared.exe"
    ) else (
        echo [*] Mengunduh binary cloudflared.exe otomatis...
        curl.exe -fSLo "cloudflared.exe" "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe"
        if %errorlevel% neq 0 (
            color 0C
            echo [ERROR] Gagal mengunduh cloudflared.exe!
            echo Pastikan koneksi internet aktif.
            pause
            exit /b 1
        )
        set CLOUDFLARED_BIN=".\cloudflared.exe"
    )
)

echo.
echo ===============================================================================
echo   MEMBUKA TUNNEL ONLINE HTTPS...
echo ===============================================================================
echo.
echo PERHATIKAN: Salin URL HTTPS yang muncul di bawah (misal: https://xxxx.trycloudflare.com)
echo dan bagikan ke Kasir Cabang / Warehouse PWA / Owner di luar toko.
echo.
echo Tekan CTRL + C untuk menutup koneksi online ini kapan saja.
echo ===============================================================================
echo.

%CLOUDFLARED_BIN% tunnel --url http://127.0.0.1:8008
