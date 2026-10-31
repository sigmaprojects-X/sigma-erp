; ==============================================================================
; SIGMA ERP & POS Pro - Professional Windows Installer Script (Inno Setup 6+)
; Copyright (c) 2026 PT SIGMA RETAIL INDONESIA. All rights reserved.
;
; CARA KOMPILASI:
;   1. Install Inno Setup 6 dari https://jrsoftware.org/isdl.php
;   2. Buka file ini di Inno Setup Compiler
;   3. Klik Compile (Ctrl+F9) atau jalankan BUILD-INSTALLER.bat
;
; OUTPUT: installer_output\SIGMA_ERP_POS_v1.0.2_Setup.exe (~26 MB)
; ==============================================================================

; ============== KONSTANTA VERSI & IDENTITAS APLIKASI ==========================
#define MyAppName        "SIGMA ERP & POS Pro"
#define MyAppVersion     "1.0.2"
#define MyAppPublisher   "PT SIGMA RETAIL INDONESIA"
#define MyAppURL         "https://sigmamart.co.id"
#define MyAppSupportURL  "https://sigmamart.co.id/support"
#define MyAppIcon        "..\public\sigma.ico"
#define MyAppExeName     "START-SIGMA-SERVER.bat"
#define MyAppId          "5A8E19B2-4E90-449D-9F12-88E4B0C77D20"

; ============== KONFIGURASI PAKET INSTALLER ===================================
[Setup]
; ID Unik Aplikasi (jangan ubah kecuali mengganti ke aplikasi berbeda)
AppId={{{#MyAppId}}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppVerName={#MyAppName} v{#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppSupportURL}
AppUpdatesURL={#MyAppURL}
AppCopyright=Copyright (C) 2026 {#MyAppPublisher}
AppComments=Sistem ERP & Kasir POS Terintegrasi untuk Bisnis Ritel Modern Indonesia

; Lokasi Instalasi Default
DefaultDirName={autopf}\SIGMA ERP
DefaultGroupName=SIGMA ERP & POS
DisableDirPage=no
AllowNoIcons=yes

; Lisensi & Gambar Wizard
LicenseFile=license.rtf
; WizardImageFile=assets\wizard_banner.bmp       ; 164x314 px (uncomment jika ada)
; WizardSmallImageFile=assets\wizard_icon.bmp    ; 55x58 px (uncomment jika ada)

; Output Setup.exe
OutputDir=..\installer_output
OutputBaseFilename=SIGMA_ERP_POS_v{#MyAppVersion}_Setup
SetupIconFile={#MyAppIcon}

; Kompresi LZMA2 Tertinggi untuk file sekecil mungkin
Compression=lzma2/ultra64
SolidCompression=yes
InternalCompressLevel=ultra64
LZMANumFastBytes=273
LZMAMatchFinder=BT4
LZMADictionarySize=16384

; Kebutuhan Sistem
PrivilegesRequired=admin
ArchitecturesInstallIn64BitMode=x64compatible
MinVersion=10.0.17763  ; Windows 10 1809 minimum
WizardStyle=modern
WizardResizable=yes

; Pengaturan Uninstall
UninstallDisplayIcon={app}\public\sigma.ico
UninstallDisplayName={#MyAppName}
CreateUninstallRegKey=yes

; Informasi Versi untuk Add/Remove Programs
VersionInfoVersion={#MyAppVersion}
VersionInfoCompany={#MyAppPublisher}
VersionInfoDescription={#MyAppName} Setup
VersionInfoCopyright=Copyright (C) 2026 {#MyAppPublisher}
VersionInfoProductName={#MyAppName}
VersionInfoProductVersion={#MyAppVersion}

; ============== BAHASA INSTALLER ==============================================
[Languages]
Name: "id"; MessagesFile: "compiler:Languages\Indonesian.isl"; \
  LicenseFile: "license.rtf"
Name: "en"; MessagesFile: "compiler:Default.isl"; \
  LicenseFile: "license.rtf"

; ============== TUGAS OPSIONAL SAAT INSTALASI =================================
[Tasks]
Name: "desktopicon_erp"; \
  Description: "Buat shortcut 'SIGMA ERP Back-Office' di Desktop"; \
  GroupDescription: "Shortcut Desktop:"; \
  Flags: unchecked
Name: "desktopicon_kasir"; \
  Description: "Buat shortcut 'SIGMA KASIR & POS' di Desktop"; \
  GroupDescription: "Shortcut Desktop:"
Name: "desktopicon_kiosk"; \
  Description: "Buat shortcut 'SIGMA POS Kiosk (Layar Kasir Penuh)' di Desktop"; \
  GroupDescription: "Shortcut Desktop:"; \
  Flags: unchecked
Name: "autostart"; \
  Description: "Jalankan Server SIGMA otomatis saat Windows menyala (Direkomendasikan untuk Komputer Kasir)"; \
  GroupDescription: "Opsi Startup:"
Name: "firewall"; \
  Description: "Izinkan akses Jaringan LAN/Wi-Fi (untuk akses multi-kasir dari perangkat lain)"; \
  GroupDescription: "Opsi Jaringan:"; \
  Flags: unchecked

; ============== FILE YANG DISALIN KE FOLDER INSTALASI ========================
[Files]
; --- File Utama Aplikasi Laravel ---
Source: "..\app\*"; DestDir: "{app}\app"; \
  Flags: ignoreversion recursesubdirs createallsubdirs; \
  Excludes: "*.log"

Source: "..\bootstrap\*"; DestDir: "{app}\bootstrap"; \
  Flags: ignoreversion recursesubdirs createallsubdirs

Source: "..\config\*"; DestDir: "{app}\config"; \
  Flags: ignoreversion recursesubdirs createallsubdirs

Source: "..\database\*"; DestDir: "{app}\database"; \
  Flags: ignoreversion recursesubdirs createallsubdirs

Source: "..\resources\*"; DestDir: "{app}\resources"; \
  Flags: ignoreversion recursesubdirs createallsubdirs

Source: "..\routes\*"; DestDir: "{app}\routes"; \
  Flags: ignoreversion recursesubdirs createallsubdirs

Source: "..\storage\*"; DestDir: "{app}\storage"; \
  Flags: ignoreversion recursesubdirs createallsubdirs; \
  Excludes: "*.log,logs\*"

; --- Aset Frontend (Vite Production Build) ---
Source: "..\public\*"; DestDir: "{app}\public"; \
  Flags: ignoreversion recursesubdirs createallsubdirs

; --- Vendor PHP (Composer Dependencies) ---
Source: "..\vendor\*"; DestDir: "{app}\vendor"; \
  Flags: ignoreversion recursesubdirs createallsubdirs

; --- File Konfigurasi Utama ---
Source: "..\.env.example"; DestDir: "{app}"; \
  Flags: ignoreversion onlyifdoesntexist; \
  DestName: ".env.example"
Source: "..\artisan"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\composer.json"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\composer.lock"; DestDir: "{app}"; Flags: ignoreversion

; --- Script Launcher & Utilitas ---
Source: "..\START-SIGMA-SERVER.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\START_SIGMA_ERP.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\START_SIGMA_KASIR.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\START-KASIR-KIOSK.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\STOP-SIGMA-SERVER.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\BACKUP-DATABASE.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\RESTORE-DATABASE.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\UPDATE-SIGMA-ERP.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\ONLINE-TUNNEL-CABANG.bat"; DestDir: "{app}"; \
  Flags: ignoreversion
Source: "..\SETUP-KOMPUTER-BARU.bat"; DestDir: "{app}"; \
  Flags: ignoreversion
Source: "..\start_server_silent.vbs"; DestDir: "{app}"; \
  Flags: ignoreversion
Source: "..\START_SIGMA_ERP.vbs"; DestDir: "{app}"; \
  Flags: ignoreversion
Source: "..\START_SIGMA_KASIR.vbs"; DestDir: "{app}"; \
  Flags: ignoreversion

; ============== SHORTCUT START MENU & DESKTOP =================================
[Icons]
; === Start Menu ===
Name: "{group}\SIGMA ERP Back-Office"; \
  Filename: "{app}\START_SIGMA_ERP.vbs"; \
  WorkingDir: "{app}"; \
  IconFilename: "{app}\public\sigma.ico"; \
  Comment: "Buka Dashboard Manajemen SIGMA ERP (Back-Office)"
Name: "{group}\SIGMA KASIR & POS"; \
  Filename: "{app}\START_SIGMA_KASIR.vbs"; \
  WorkingDir: "{app}"; \
  IconFilename: "{app}\public\sigma.ico"; \
  Comment: "Buka Layar Kasir SIGMA POS Pro"
Name: "{group}\SIGMA POS Kiosk (Layar Penuh)"; \
  Filename: "{app}\START-KASIR-KIOSK.bat"; \
  WorkingDir: "{app}"; \
  IconFilename: "{app}\public\sigma.ico"; \
  Comment: "Buka Kasir POS Mode Fullscreen Kiosk"
Name: "{group}\---"; \
  Filename: "{app}"; \
  Flags: foldershortcut; \
  Comment: "Folder Aplikasi"
Name: "{group}\Utilitas\Mulai Server Manual"; \
  Filename: "{app}\START-SIGMA-SERVER.bat"; \
  WorkingDir: "{app}"; \
  Comment: "Jalankan Server SIGMA ERP secara manual"
Name: "{group}\Utilitas\Hentikan Server"; \
  Filename: "{app}\STOP-SIGMA-SERVER.bat"; \
  WorkingDir: "{app}"; \
  Comment: "Matikan Server SIGMA ERP"
Name: "{group}\Utilitas\Backup Database Instan"; \
  Filename: "{app}\BACKUP-DATABASE.bat"; \
  WorkingDir: "{app}"; \
  Comment: "Backup database PostgreSQL SIGMA ERP"
Name: "{group}\Utilitas\Restore Database"; \
  Filename: "{app}\RESTORE-DATABASE.bat"; \
  WorkingDir: "{app}"; \
  Comment: "Restore database dari file backup"
Name: "{group}\Utilitas\Buka Terowongan Online (Cabang)"; \
  Filename: "{app}\ONLINE-TUNNEL-CABANG.bat"; \
  WorkingDir: "{app}"; \
  Comment: "Aktifkan Cloudflare Tunnel untuk akses kasir cabang dari internet"
Name: "{group}\Utilitas\Perbarui Aplikasi"; \
  Filename: "{app}\UPDATE-SIGMA-ERP.bat"; \
  WorkingDir: "{app}"; \
  Comment: "Perbarui aplikasi ke versi terbaru"
Name: "{group}\{cm:UninstallProgram,{#MyAppName}}"; \
  Filename: "{uninstallexe}"; \
  Comment: "Hapus instalasi SIGMA ERP & POS dari komputer ini"

; === Desktop Shortcuts (sesuai Task yang dipilih pengguna) ===
Name: "{autodesktop}\SIGMA ERP Back-Office"; \
  Filename: "{app}\START_SIGMA_ERP.vbs"; \
  WorkingDir: "{app}"; \
  IconFilename: "{app}\public\sigma.ico"; \
  Tasks: desktopicon_erp; \
  Comment: "Dashboard Manajemen SIGMA ERP"
Name: "{autodesktop}\SIGMA KASIR & POS"; \
  Filename: "{app}\START_SIGMA_KASIR.vbs"; \
  WorkingDir: "{app}"; \
  IconFilename: "{app}\public\sigma.ico"; \
  Tasks: desktopicon_kasir; \
  Comment: "Layar Kasir SIGMA POS Pro"
Name: "{autodesktop}\SIGMA POS Kiosk"; \
  Filename: "{app}\START-KASIR-KIOSK.bat"; \
  WorkingDir: "{app}"; \
  IconFilename: "{app}\public\sigma.ico"; \
  Tasks: desktopicon_kiosk; \
  Comment: "Kasir POS Mode Fullscreen"

; === Windows Startup (Auto-Start Server) ===
Name: "{commonstartup}\SIGMA ERP Server"; \
  Filename: "wscript.exe"; \
  Parameters: """{app}\start_server_silent.vbs"""; \
  WorkingDir: "{app}"; \
  Tasks: autostart; \
  Comment: "Server SIGMA ERP akan berjalan otomatis saat Windows menyala"

; ============== PERINTAH SETELAH INSTALASI ====================================
[Run]
; Buka aplikasi setelah install selesai
Filename: "{app}\START-SIGMA-SERVER.bat"; \
  Description: "Jalankan Server SIGMA ERP & buka Dashboard sekarang"; \
  WorkingDir: "{app}"; \
  Flags: shellexec postinstall skipifsilent nowait; \
  Check: ShouldRunApp

; ============== KODE PASCAL UNTUK WIZARD KUSTOM ===============================
[Code]
uses
  SysUtils, StrUtils;

var
  // Halaman-halaman wizard kustom
  DbConfigPage: TInputQueryWizardPage;
  DataDirPage: TInputDirWizardPage;
  PortPage: TInputQueryWizardPage;
  PrereqPage: TOutputMsgMemoWizardPage;

  // Variabel konfigurasi yang dikumpulkan dari pengguna
  SelectedDataDir: String;
  SelectedDbPass: String;
  SelectedPort: String;
  AppKeyGenerated: Boolean;

// ===========================================================================
// HELPER: Cek apakah sebuah program ada di PATH sistem
// ===========================================================================
function ProgramExists(const ProgramName: String): Boolean;
var
  ResultCode: Integer;
begin
  Result := Exec('cmd.exe', '/C where ' + ProgramName + ' >nul 2>&1',
    '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
  Result := Result and (ResultCode = 0);
end;

// ===========================================================================
// HELPER: Jalankan perintah artisan Laravel
// ===========================================================================
function RunArtisan(const ArtisanArgs: String): Boolean;
var
  ResultCode: Integer;
begin
  Result := Exec('cmd.exe',
    '/C cd /D "' + ExpandConstant('{app}') + '" && php artisan ' + ArtisanArgs + ' >nul 2>&1',
    ExpandConstant('{app}'), SW_HIDE, ewWaitUntilTerminated, ResultCode);
  Result := Result and (ResultCode = 0);
end;

// ===========================================================================
// HELPER: Validasi port: angka 1024-65535
// ===========================================================================
function IsValidPort(const PortStr: String): Boolean;
var
  PortNum: Integer;
begin
  Result := False;
  if TryStrToInt(PortStr, PortNum) then
    Result := (PortNum >= 1024) and (PortNum <= 65535);
end;

// ===========================================================================
// INISIALISASI WIZARD: Daftarkan semua halaman kustom
// ===========================================================================
procedure InitializeWizard;
var
  DbDefaultPass: String;
  PortDefault: String;
begin
  DbDefaultPass := '885588';
  PortDefault := '8008';

  // ---- Halaman 1: Cek Prasyarat Sistem -----
  PrereqPage := CreateOutputMsgMemoPage(
    wpWelcome,
    'Pemeriksaan Prasyarat Sistem',
    'SIGMA ERP memerlukan komponen berikut sudah terinstal di komputer Anda.',
    'Hasil pemeriksaan akan ditampilkan di bawah ini setelah Anda melanjutkan ke langkah berikutnya.',
    ''
  );

  // ---- Halaman 2: Konfigurasi Database -----
  DbConfigPage := CreateInputQueryPage(
    PrereqPage.ID,
    'Konfigurasi Koneksi Database',
    'Masukkan informasi koneksi database PostgreSQL Anda.',
    'SIGMA ERP menggunakan PostgreSQL sebagai database utama. Pastikan PostgreSQL 15+ sudah terinstall dan berjalan.'
  );
  DbConfigPage.Add('Host Database (default: 127.0.0.1):', False);
  DbConfigPage.Add('Port Database (default: 5432):', False);
  DbConfigPage.Add('Nama Database (default: sigma_erp):', False);
  DbConfigPage.Add('Username PostgreSQL (default: postgres):', False);
  DbConfigPage.Add('Password PostgreSQL:', True);  // Password field (masked)

  DbConfigPage.Values[0] := '127.0.0.1';
  DbConfigPage.Values[1] := '5432';
  DbConfigPage.Values[2] := 'sigma_erp';
  DbConfigPage.Values[3] := 'postgres';
  DbConfigPage.Values[4] := DbDefaultPass;

  // ---- Halaman 3: Lokasi Folder Data -----
  DataDirPage := CreateInputDirPage(
    DbConfigPage.ID,
    'Lokasi Penyimpanan Data & Backup',
    'Pilih folder untuk menyimpan data backup database SIGMA ERP.',
    'Untuk keamanan data maksimal jika Windows perlu di-install ulang, ' +
    'disarankan menyimpan data di Drive non-sistem (contoh: D:\SIGMA_DATA).' + #13#10 +
    #13#10 + 'Folder yang dipilih:',
    False,
    'SIGMA_DATA'
  );

  // Rekomendasikan drive non-sistem
  if DirExists('D:\') then
    DataDirPage.Add('D:\SIGMA_DATA')
  else if DirExists('E:\') then
    DataDirPage.Add('E:\SIGMA_DATA')
  else
    DataDirPage.Add(ExpandConstant('{commonappdata}\SIGMA_DATA'));

  // ---- Halaman 4: Port Web Server -----
  PortPage := CreateInputQueryPage(
    DataDirPage.ID,
    'Konfigurasi Port Web Server',
    'Tentukan port jaringan yang digunakan oleh Server SIGMA ERP.',
    'Port default adalah 8008. Ubah jika port tersebut sudah digunakan oleh aplikasi lain di komputer ini.' +
    #13#10 + 'Contoh URL akses: http://localhost:8008'
  );
  PortPage.Add('Port Server (1024-65535, default: 8008):', False);
  PortPage.Values[0] := PortDefault;
end;

// ===========================================================================
// TAMPILKAN HASIL CEK PRASYARAT SEBELUM HALAMAN PREREQ
// ===========================================================================
procedure CurPageChanged(CurPageID: Integer);
var
  Report: String;
  PhpOk, PsqlOk: Boolean;
  PhpVersion, PsqlVersion: String;
begin
  if CurPageID = PrereqPage.ID then
  begin
    PhpOk := ProgramExists('php');
    PsqlOk := ProgramExists('psql');

    Report := 'STATUS PEMERIKSAAN PRASYARAT SISTEM' + #13#10;
    Report := Report + StringOfChar('=', 50) + #13#10#13#10;

    // PHP
    if PhpOk then
      Report := Report + '[OK]  PHP 8.2+          : Terdeteksi di PATH sistem'
    else
      Report := Report + '[KRITIS] PHP 8.2+       : TIDAK DITEMUKAN!  ' +
        'Download: https://windows.php.net/download/';
    Report := Report + #13#10;

    // PostgreSQL
    if PsqlOk then
      Report := Report + '[OK]  PostgreSQL 15+    : Terdeteksi di PATH sistem'
    else
      Report := Report + '[PERINGATAN] PostgreSQL : Tidak ditemukan di PATH. ' +
        'Pastikan PostgreSQL sudah terinstall dan ditambahkan ke PATH.';
    Report := Report + #13#10;

    // Sistem Operasi
    Report := Report + '[OK]  Windows 10/11     : Sistem operasi kompatibel' + #13#10;
    Report := Report + '[OK]  Arsitektur 64-bit : Didukung' + #13#10;

    Report := Report + #13#10 + StringOfChar('-', 50) + #13#10;

    if not PhpOk then
    begin
      Report := Report + #13#10 +
        'PERHATIAN: PHP tidak ditemukan!' + #13#10 +
        'Anda dapat melanjutkan instalasi, namun aplikasi TIDAK AKAN BERJALAN' + #13#10 +
        'sebelum PHP 8.2+ diinstall dan ditambahkan ke PATH Windows.' + #13#10 +
        #13#10 +
        'Download PHP 8.2+ Thread-Safe (x64):' + #13#10 +
        '  https://windows.php.net/download/' + #13#10 +
        #13#10 +
        'Download PostgreSQL 16 untuk Windows:' + #13#10 +
        '  https://www.enterprisedb.com/downloads/postgres-postgresql-downloads';
    end
    else
    begin
      Report := Report + #13#10 +
        'Semua prasyarat utama terpenuhi.' + #13#10 +
        'Lanjutkan instalasi dengan mengklik tombol [Berikutnya >.].';
    end;

    PrereqPage.RichEditViewer.Lines.Text := Report;
  end;
end;

// ===========================================================================
// VALIDASI INPUT TIAP HALAMAN SEBELUM LANJUT
// ===========================================================================
function NextButtonClick(CurPageID: Integer): Boolean;
var
  DbPort: Integer;
begin
  Result := True;

  // Validasi halaman Konfigurasi Database
  if CurPageID = DbConfigPage.ID then
  begin
    if Trim(DbConfigPage.Values[0]) = '' then
    begin
      MsgBox('Host database tidak boleh kosong.', mbError, MB_OK);
      Result := False;
      Exit;
    end;
    if Trim(DbConfigPage.Values[4]) = '' then
    begin
      MsgBox('Password database tidak boleh kosong. Masukkan password PostgreSQL Anda.', mbError, MB_OK);
      Result := False;
      Exit;
    end;
    if Trim(DbConfigPage.Values[2]) = '' then
    begin
      DbConfigPage.Values[2] := 'sigma_erp';  // default
    end;
    SelectedDbPass := DbConfigPage.Values[4];
  end;

  // Validasi halaman Lokasi Data
  if CurPageID = DataDirPage.ID then
  begin
    SelectedDataDir := DataDirPage.Values[0];
    if SelectedDataDir = '' then
    begin
      MsgBox('Harap pilih atau ketikkan lokasi folder penyimpanan data.', mbError, MB_OK);
      Result := False;
      Exit;
    end;
    // Coba buat folder jika belum ada
    if not DirExists(SelectedDataDir) then
    begin
      if not ForceDirectories(SelectedDataDir) then
      begin
        MsgBox(
          'Gagal membuat folder: ' + SelectedDataDir + #13#10 +
          'Pastikan Anda memiliki izin administrator untuk folder tersebut.',
          mbError, MB_OK
        );
        Result := False;
        Exit;
      end;
    end;
  end;

  // Validasi halaman Port Server
  if CurPageID = PortPage.ID then
  begin
    if not IsValidPort(Trim(PortPage.Values[0])) then
    begin
      MsgBox(
        'Port tidak valid. Masukkan angka antara 1024 sampai 65535.' + #13#10 +
        'Contoh: 8008, 8080, 9000',
        mbError, MB_OK
      );
      Result := False;
      Exit;
    end;
    SelectedPort := Trim(PortPage.Values[0]);
  end;
end;

// ===========================================================================
// TENTUKAN APAKAH TOMBOL "BUKA APLIKASI" DITAMPILKAN DI AKHIR INSTALL
// ===========================================================================
function ShouldRunApp: Boolean;
begin
  Result := True;
end;

// ===========================================================================
// POST-INSTALL: Konfigurasi .env, migrasi DB, shortcut, firewall
// ===========================================================================
procedure CurStepChanged(CurStep: TSetupStep);
var
  AppDir, EnvFile, EnvExample: String;
  DbHost, DbPort, DbName, DbUser, AppUrl: String;
  EnvContent: TStringList;
  i: Integer;
  Line, LineKey, SetLine: String;
  ResultCode: Integer;
  FirewallName: String;
begin
  if CurStep = ssPostInstall then
  begin
    AppDir := ExpandConstant('{app}');
    EnvFile := AppDir + '\.env';
    EnvExample := AppDir + '\.env.example';

    // === Langkah 1: Buat .env dari .env.example jika belum ada ===
    if not FileExists(EnvFile) then
    begin
      if FileExists(EnvExample) then
        FileCopy(EnvExample, EnvFile, False);
    end;

    // === Langkah 2: Tulis konfigurasi ke .env ===
    if FileExists(EnvFile) then
    begin
      DbHost := Trim(DbConfigPage.Values[0]);
      DbPort := Trim(DbConfigPage.Values[1]);
      DbName := Trim(DbConfigPage.Values[2]);
      DbUser := Trim(DbConfigPage.Values[3]);
      AppUrl := 'http://localhost:' + SelectedPort;

      if DbHost = '' then DbHost := '127.0.0.1';
      if DbPort = '' then DbPort := '5432';
      if DbName = '' then DbName := 'sigma_erp';
      if DbUser = '' then DbUser := 'postgres';

      EnvContent := TStringList.Create;
      try
        EnvContent.LoadFromFile(EnvFile);
        for i := 0 to EnvContent.Count - 1 do
        begin
          Line := EnvContent[i];
          if Pos('=', Line) > 1 then
          begin
            LineKey := Copy(Line, 1, Pos('=', Line) - 1);
            SetLine := '';

            if LineKey = 'APP_URL'       then SetLine := 'APP_URL=' + AppUrl;
            if LineKey = 'APP_PORT'      then SetLine := 'APP_PORT=' + SelectedPort;
            if LineKey = 'APP_ENV'       then SetLine := 'APP_ENV=production';
            if LineKey = 'APP_DEBUG'     then SetLine := 'APP_DEBUG=false';
            if LineKey = 'DB_HOST'       then SetLine := 'DB_HOST=' + DbHost;
            if LineKey = 'DB_PORT'       then SetLine := 'DB_PORT=' + DbPort;
            if LineKey = 'DB_DATABASE'   then SetLine := 'DB_DATABASE=' + DbName;
            if LineKey = 'DB_USERNAME'   then SetLine := 'DB_USERNAME=' + DbUser;
            if LineKey = 'DB_PASSWORD'   then SetLine := 'DB_PASSWORD=' + SelectedDbPass;
            if LineKey = 'DB_SCHEMA'     then SetLine := 'DB_SCHEMA=sigma';

            if SetLine <> '' then
              EnvContent[i] := SetLine;
          end;
        end;
        EnvContent.SaveToFile(EnvFile);
      finally
        EnvContent.Free;
      end;
    end;

    // === Langkah 3: Simpan jalur data kustom ===
    SaveStringToFile(AppDir + '\database_storage_path.txt', SelectedDataDir, False);

    // === Langkah 4: Buat folder logs & cache yang dibutuhkan ===
    ForceDirectories(AppDir + '\storage\logs');
    ForceDirectories(AppDir + '\storage\framework\cache\data');
    ForceDirectories(AppDir + '\storage\framework\sessions');
    ForceDirectories(AppDir + '\storage\framework\views');
    ForceDirectories(AppDir + '\storage\app\public');
    ForceDirectories(AppDir + '\bootstrap\cache');

    // === Langkah 5: Generate APP_KEY jika PHP tersedia ===
    if ProgramExists('php') then
    begin
      // Generate Application Key
      RunArtisan('key:generate --force');

      // Buat symlink storage
      RunArtisan('storage:link --force');

      // Bersihkan cache konfigurasi
      RunArtisan('optimize:clear');

      // Cache konfigurasi untuk performa produksi
      RunArtisan('config:cache');
      RunArtisan('route:cache');
      RunArtisan('view:cache');

      // Jalankan Migrasi Database (jika PostgreSQL tersedia)
      if ProgramExists('psql') then
      begin
        // Coba buat database jika belum ada
        Exec('cmd.exe',
          '/C set PGPASSWORD=' + SelectedDbPass + ' && psql -U ' +
          DbConfigPage.Values[3] + ' -h ' + DbConfigPage.Values[0] +
          ' -p ' + DbConfigPage.Values[1] +
          ' -c "CREATE DATABASE ' + DbConfigPage.Values[2] + ';" >nul 2>&1',
          AppDir, SW_HIDE, ewWaitUntilTerminated, ResultCode);

        // Jalankan migrasi
        RunArtisan('migrate --force');
      end;
    end;

    // === Langkah 6: Tambahkan exception Firewall Windows (jika task dipilih) ===
    if IsTaskSelected('firewall') then
    begin
      FirewallName := 'SIGMA ERP Server (Port ' + SelectedPort + ')';
      Exec('netsh.exe',
        'advfirewall firewall add rule name="' + FirewallName + '" ' +
        'protocol=TCP dir=in localport=' + SelectedPort + ' action=allow',
        '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
    end;
  end;
end;

// ===========================================================================
// PESAN SELAMAT DATANG KUSTOM DI HALAMAN PERTAMA WIZARD
// ===========================================================================
function WelcomeLabel2Text(): String;
begin
  Result :=
    'Wizard ini akan memandu Anda memasang ' +
    '{#MyAppName} versi {#MyAppVersion} di komputer Anda.' +
    #13#10 + #13#10 +
    'SIGMA ERP & POS Pro adalah sistem manajemen bisnis ritel terintegrasi ' +
    'yang mencakup:' +
    #13#10 + '  * Kasir POS dengan pencarian cepat barcode & trigram' +
    #13#10 + '  * Manajemen inventaris & harga 3-dimensi otomatis' +
    #13#10 + '  * Akuntansi double-entry otomatis (jurnal, GL, laporan)' +
    #13#10 + '  * Multi-kasir LAN/Wi-Fi & akses cabang via tunnel' +
    #13#10 + #13#10 +
    'Disarankan untuk menutup semua aplikasi lain sebelum melanjutkan.';
end;

// ===========================================================================
// PESAN KONFIRMASI SEBELUM UNINSTALL
// ===========================================================================
function InitializeUninstall(): Boolean;
begin
  Result := MsgBox(
    'Apakah Anda yakin ingin menghapus {#MyAppName} dari komputer ini?' +
    #13#10 + #13#10 +
    'PERHATIAN: Proses uninstall TIDAK akan menghapus:' +
    #13#10 + '  - File database PostgreSQL (data transaksi Anda aman)' +
    #13#10 + '  - File .env (konfigurasi koneksi)' +
    #13#10 + '  - Folder storage/app (file upload & backup lokal)' +
    #13#10 + #13#10 +
    'Data Anda tetap aman dan dapat dipulihkan dengan menginstal ulang aplikasi.',
    mbConfirmation,
    MB_YESNO or MB_DEFBUTTON2
  ) = IDYES;
end;
