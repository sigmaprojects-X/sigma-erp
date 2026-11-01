@echo off
setlocal enabledelayedexpansion
title SIGMA ERP - Sinkronisasi GitHub
color 0B

echo ======================================================
echo       SIGMA ERP ^& POS - AUTO SYNC GITHUB
echo ======================================================
echo.

cd /d "%~dp0"

echo [1/3] Memeriksa status perubahan file...
git status --porcelain > "%temp%\git_status.tmp"

for %%R in ("%temp%\git_status.tmp") do (
    if %%~zR gtr 0 (
        set HAS_CHANGES=1
    ) else (
        set HAS_CHANGES=0
    )
)
if exist "%temp%\git_status.tmp" del "%temp%\git_status.tmp"

if "!HAS_CHANGES!"=="1" (
    echo Menemukan file yang baru diubah. Menyiapkan commit...
    git add .
    
    :: Ambil waktu lokal Windows
    set TIMESTAMP=%date% %time:~0,5%
    git commit -m "Auto backup: !TIMESTAMP!"
    echo [2/3] Commit lokal berhasil disimpan.
) else (
    echo [2/3] Tidak ada file baru yang diubah. Memeriksa commit lokal...
)

echo.
echo [3/3] Mengunggah (Push) ke GitHub (branch: main)...
git push origin main

if %errorlevel% equ 0 (
    echo.
    echo ======================================================
    echo  [SUKSES] Seluruh kode berhasil di-upload ke GitHub!
    echo ======================================================
) else (
    echo.
    echo ======================================================
    echo  [PERINGATAN] Gagal push ke GitHub.
    echo  Pastikan koneksi internet Anda aktif.
    echo ======================================================
)

echo.
echo Jendela ini akan otomatis tertutup dalam 5 detik...
echo (atau tekan sembarang tombol untuk langsung menutup)
timeout /t 5 >nul 2>&1 || pause >nul
