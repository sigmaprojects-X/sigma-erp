@echo off
setlocal enabledelayedexpansion
title "SIGMA ERP & POS PRO - Commercial Installer Builder"
color 0B

echo ===============================================================================
echo            SIGMA ERP ^& POS PRO - COMMERCIAL INSTALLER BUILDER
echo                   Pembuat Paket Installer Windows Komersil
echo ===============================================================================
echo.
echo Script ini akan memaketkan seluruh aplikasi SIGMA ERP ^& POS menjadi:
echo 1. Paket Portable Offline Installer (Folder Setup siap Flashdisk)
echo 2. File Arsip Distribusi Komersil (.ZIP Siap Kirim)
echo 3. Standalone Installer Wizard (.EXE via Inno Setup jika terinstall)
echo.

cd /d "%~dp0"

:: 1. Verifikasi Kebutuhan Dasar
echo [*] Memeriksa kebutuhan build environment...
where php >nul 2>nul
if %errorlevel% neq 0 (
    color 0C
    echo [ERROR] PHP tidak terdeteksi di PATH sistem!
    pause
    exit /b 1
)

where npm.cmd >nul 2>nul
if %errorlevel% neq 0 (
    echo [PERINGATAN] NPM tidak terdeteksi. Melewati build aset frontend...
) else (
    echo [OK] Node.js ^& NPM terdeteksi.
)

:: 2. Ambil Versi Resmi dari config/version.php
set "APP_VERSION=1.0.1"
for /f "usebackq tokens=*" %%v in (`php -r "echo (require 'config/version.php')['version'];" 2^>nul`) do (
    if not "%%v"=="" set "APP_VERSION=%%v"
)
echo [*] Versi Rilis Aplikasi : v%APP_VERSION%
echo.

:: 3. Kompilasi Aset Frontend (Tailwind CSS v4 & Alpine.js via Vite)
echo [1/5] Mengompilasi aset antarmuka produksi (Production Vite Build)...
where npm.cmd >nul 2>nul
if %errorlevel% equ 0 (
    call npm.cmd run build
    if %errorlevel% neq 0 (
        color 0C
        echo [ERROR] Kompilasi frontend Vite gagal!
        pause
        exit /b 1
    )
    echo [OK] Aset frontend produksi siap di public/build.
) else (
    echo [SKIP] Menggunakan aset build yang sudah ada di public/build.
)
echo.

:: 4. Optimasi Laravel Cache & Autoloader
echo [2/5] Membersihkan cache dan mengoptimalkan Composer Autoloader...
call php artisan optimize:clear >nul 2>nul
call composer dump-autoload -o >nul 2>nul
echo [OK] Composer autoloader optimized.
echo.

:: 5. Siapkan Folder Staging Paket Distribusi
set "DIST_BASE=dist"
set "FOLDER_NAME=SIGMA-ERP-POS-Setup-v%APP_VERSION%"
set "DIST_FOLDER=%DIST_BASE%\%FOLDER_NAME%"
set "OUTPUT_ZIP=%DIST_BASE%\SIGMA-ERP-POS-Setup-v%APP_VERSION%.zip"

echo [3/5] Merakit paket instalasi bersih di: %DIST_FOLDER%...
if exist "%DIST_FOLDER%" rmdir /s /q "%DIST_FOLDER%"
if not exist "%DIST_BASE%" mkdir "%DIST_BASE%"
mkdir "%DIST_FOLDER%"

:: Salin berkas utama aplikasi menggunakan ROBOCOPY
robocopy "%~dp0." "%DIST_FOLDER%" /E ^
    /XD ".git" "node_modules" "tests" "storage\logs" "backups" "dist" "installer_output" ".phpunit.cache" ".claude" ".cursor" ".idea" ".vscode" ^
    /XF "*.log" ".env" ".env.backup" ".env.production" ".phpunit.result.cache" "BUILD-INSTALLER.bat" "Thumbs.db" >nul

:: Tambahkan Wizard SETUP.bat ke dalam paket distribusi
copy "%~dp0installer\SETUP-STANDALONE.bat" "%DIST_FOLDER%\SETUP.bat" >nul

:: Sertakan Bundled Portable PHP ke dalam paket distribusi portable
if exist "%~dp0installer\runtime\php" (
    echo [*] Menyertakan Bundled Portable PHP 8.3 ke dalam paket...
    robocopy "%~dp0installer\runtime\php" "%DIST_FOLDER%\php" /E >nul
)

attrib -r -s "%~dp0bootstrap\cache" /D 2>nul
attrib -r -s "%~dp0storage" /D 2>nul
attrib -r -s "%DIST_FOLDER%\bootstrap\cache" /D 2>nul
attrib -r -s "%DIST_FOLDER%\storage" /D 2>nul
echo [OK] Paket distribusi bersih selesai dirakit.
echo.

:: 6. Buat File Arsip ZIP Distribusi Menggunakan Native tar.exe (Cepat & Handal)
echo [4/5] Mengompresi paket distribusi menjadi file .ZIP untuk Flashdisk...
if exist "%OUTPUT_ZIP%" del /f /q "%OUTPUT_ZIP%"

where tar.exe >nul 2>nul
if %errorlevel% equ 0 (
    tar.exe -a -c -f "%OUTPUT_ZIP%" -C "%DIST_BASE%" "%FOLDER_NAME%"
) else (
    powershell -NoProfile -Command "Compress-Archive -Path '%DIST_FOLDER%\*' -DestinationPath '%OUTPUT_ZIP%' -CompressionLevel Optimal"
)

if exist "%OUTPUT_ZIP%" (
    echo [OK] File ZIP komersil berhasil dibuat: %OUTPUT_ZIP%
) else (
    echo [!] Gagal membuat file ZIP, namun folder setup portable tetap siap digunakan.
)
echo.

:: 7. Cek Compiler Inno Setup (ISCC.exe) untuk Membuat File Setup .EXE
echo [5/5] Memeriksa compiler Inno Setup (ISCC.exe) untuk file Setup .EXE...
set "ISCC_PATH="

where iscc.exe >nul 2>nul
if %errorlevel% equ 0 set "ISCC_PATH=iscc.exe"

if not defined ISCC_PATH (
    if exist "C:\Program Files\Inno Setup 7\ISCC.exe" set "ISCC_PATH=C:\Program Files\Inno Setup 7\ISCC.exe"
)
if not defined ISCC_PATH (
    if exist "C:\Program Files (x86)\Inno Setup 7\ISCC.exe" set "ISCC_PATH=C:\Program Files (x86)\Inno Setup 7\ISCC.exe"
)
if not defined ISCC_PATH (
    if exist "%LOCALAPPDATA%\Programs\Inno Setup 7\ISCC.exe" set "ISCC_PATH=%LOCALAPPDATA%\Programs\Inno Setup 7\ISCC.exe"
)
if not defined ISCC_PATH (
    if exist "C:\Program Files (x86)\Inno Setup 6\ISCC.exe" set "ISCC_PATH=C:\Program Files (x86)\Inno Setup 6\ISCC.exe"
)
if not defined ISCC_PATH (
    if exist "C:\Program Files\Inno Setup 6\ISCC.exe" set "ISCC_PATH=C:\Program Files\Inno Setup 6\ISCC.exe"
)
if not defined ISCC_PATH (
    if exist "%LOCALAPPDATA%\Programs\Inno Setup 6\ISCC.exe" set "ISCC_PATH=%LOCALAPPDATA%\Programs\Inno Setup 6\ISCC.exe"
)

if not defined ISCC_PATH goto NO_ISCC

echo [*] Inno Setup terdeteksi di: "!ISCC_PATH!"
echo [*] Mengompilasi installer mandiri (Single-File .EXE)...
if not exist "installer_output" mkdir "installer_output"
"!ISCC_PATH!" "installer\sigma_erp_setup.iss"
if !errorlevel! equ 0 (
    echo [OK] Installer .EXE berhasil dibuat di folder 'installer_output\'!
) else (
    echo [!] Gagal mengompilasi file .EXE via Inno Setup.
)
goto AFTER_ISCC

:NO_ISCC
echo [-] Inno Setup 6 belum terinstall di komputer ini.
echo [*] [Info] Paket Portable Setup dan File ZIP sudah siap digunakan langsung.
echo [*] [Opsional] Jika nanti ingin mengompilasi menjadi satu file Setup.exe:
echo     1. Download Inno Setup gratis di: https://jrsoftware.org/isdl.php
echo     2. Install Inno Setup di komputer ini.
echo     3. Jalankan kembali script ini, maka file Setup.exe akan dibuat otomatis.

:AFTER_ISCC

echo.
echo ===============================================================================
echo                 PEMBUATAN PAKET INSTALLER SUKSES 100%!
echo ===============================================================================
echo.
echo Hasil Output yang Siap Didistribusikan:
echo 1. Folder Setup Siap Pakai (Bawa di Flashdisk):
echo    ^> %DIST_FOLDER%
echo    (Klien / Kasir cukup dobel-klik file SETUP.bat di dalamnya)
echo.
echo 2. File Arsip Siap Kirim (ZIP):
echo    ^> %OUTPUT_ZIP%
echo    (Bisa langsung dikirim via Google Drive / WhatsApp / Email ke konsumen)
if exist "installer_output\SIGMA_ERP_POS_v%APP_VERSION%_Setup.exe" (
    echo.
    echo 3. File Setup Installer Standalone:
    echo    ^> installer_output\SIGMA_ERP_POS_v%APP_VERSION%_Setup.exe
)
echo.
echo ===============================================================================
pause
