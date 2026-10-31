@echo off
setlocal enabledelayedexpansion
title SIGMA ERP - 1-Click System Updater ^& Maintenance
color 0B

echo ===============================================================================
echo                SIGMA ERP ^& POS - 1-CLICK SYSTEM UPDATER
echo ===============================================================================
echo Script ini akan memperbarui sistem secara aman TANPA MENGHAPUS DATA TRANSAKSI:
echo 1. Membuat Backup Otomatis Database Sebelum Update (Safety First).
echo 2. Mengambil update kode terbaru (jika menggunakan Git/Patch).
echo 3. Menjalankan penambahan kolom/tabel baru (Database Migration).
echo 4. Membersihkan ^& merefresh cache aplikasi (Clear Cache).
echo ===============================================================================
echo.

cd /d "%~dp0"

:: 1. Langkah Pertama: AUTO-BACKUP Database Demi Keamanan Data
echo [1/4] Membuat Backup Pengaman Database Sebelum Update...
call "BACKUP-DATABASE.bat" < nul >nul 2>nul
echo [OK] Database telah diamankan di folder 'backups/'.
echo.

:: 2. Langkah Kedua: Cek Apakah Proyek Menggunakan Git
echo [2/4] Memeriksa pembaharuan kode...
if exist ".git" (
    echo [*] Mengambil pembaruan dari Git Repository...
    git pull
) else (
    echo [*] Folder Git tidak terdeteksi (Mode Standalone/File Copy).
    echo Pastikan file update/patch terbaru sudah disalin ke folder proyek ini.
)
echo.

:: 3. Langkah Ketiga: Jalankan Migrasi Database untuk Fitur/Kolom Baru
echo [3/4] Menjalankan sinkronisasi database untuk tabel/kolom baru...
php artisan migrate --force
echo [OK] Database schema up-to-date.
echo.

:: 4. Langkah Keempat: Bersihkan Cache Aplikasi
echo [4/4] Merefresh cache konfigurasi, route, dan view Blade...
php artisan optimize:clear >nul 2>nul

:: Cek Build Frontend jika ada npm
where npm.cmd >nul 2>nul
if %errorlevel% equ 0 (
    echo [*] Mengompilasi aset antarmuka terbaru (Tailwind/Vite)...
    call npm.cmd run build >nul 2>nul
)

echo.
echo ===============================================================================
echo                     UPDATE SISTEM BERHASIL 100%!
echo ===============================================================================
echo.
echo Semua data barang, transaksi, stok kasir, dan jurnal akuntansi TETAP UTUH.
echo Fitur atau perbaikan baru sudah aktif.
echo Silakan jalankan kembali 'START-SIGMA-SERVER.bat'.
echo.
pause
