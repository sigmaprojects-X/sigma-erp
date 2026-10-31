# SIGMA ERP & POS Enterprise

Sistem Enterprise Resource Planning (ERP) dan Point of Sale (POS) terintegrasi berbasis Laravel untuk operasional ritel, grosir, pergudangan multi-cabang, dan pembukuan akuntansi double-entry otomatis.

---

## 1. Kebutuhan Sistem (System Requirements)

Pastikan komputer server/kasir memenuhi spesifikasi minimum berikut sebelum instalasi:

- **PHP:** `PHP 8.3` atau lebih baru (dengan ekstensi `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `bcmath`, `intl`, `gd`, `zip`, `curl`)
- **Database:** `PostgreSQL 15+` (direkomendasikan PostgreSQL 16+ dengan ekstensi `pgcrypto` dan `pg_trgm`)
- **Dependency Manager:** Composer 2.x
- **Sistem Operasi:** Windows 10 / 11 (64-bit) atau Linux Server
- **Hardware Minimum:** RAM 4 GB (rekomendasi 8 GB+), penyimpanan kosong minimal 2 GB

---

## 2. Panduan Instalasi

### A. Instalasi Otomatis (Windows)
1. Pastikan layanan **PostgreSQL** sudah aktif dan berjalan di port `5432`.
2. Jalankan salah satu skrip instalasi berikut sebagai Administrator:
   - `SETUP-KOMPUTER-BARU.bat` — untuk penyiapan awal di komputer baru (memeriksa PHP 8.3+, PostgreSQL, menginstal dependensi Composer, membuat file `.env`, membuat database `sigma_erp`, menjalankan migrasi, dan seeder awal).
   - `SETUP.bat` — untuk instalasi/konfigurasi cepat pada lingkungan yang sudah memiliki paket vendor.
3. Setelah instalasi selesai 100%, jalankan server menggunakan:
   - `START-SIGMA-SERVER.bat` (menjalankan web server di `http://127.0.0.1:8000` beserta background scheduler `php artisan schedule:work`).
   - Untuk menghentikan server dan scheduler, jalankan `STOP-SIGMA-SERVER.bat`.

### B. Instalasi Manual (Command Line)
```bash
# 1. Salin konfigurasi environment
cp .env.example .env

# 2. Sesuaikan konfigurasi DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD di .env
# Pastikan PostgreSQL sudah berjalan dan database sigma_erp sudah dibuat

# 3. Instal dependensi PHP
composer install --no-interaction --prefer-dist --optimize-autoloader

# 4. Generate Application Key & Storage Link
php artisan key:generate
php artisan storage:link

# 5. Jalankan migrasi dan seeder awal
php artisan migrate --force
php artisan db:seed --force

# 6. Jalankan server aplikasi dan scheduler latar
php artisan serve --host=127.0.0.1 --port=8000
php artisan schedule:work
```

---

## 3. Cara Backup & Restore Database

Seluruh file cadangan database disimpan secara terpusat di direktori `backups/` pada root proyek dalam format `.sql` (`pg_dump` dengan opsi `--clean --if-exists --no-owner`).

### A. Backup Database
1. **Melalui Antarmuka Web (Back-Office):**
   - Masuk sebagai **Owner** atau **Manager**.
   - Buka menu **Pengaturan -> Pemeliharaan & Backup Database** (`/settings/maintenance`).
   - Klik tombol **Buat Backup Sekarang** untuk menghasilkan file `.sql` di folder `backups/`, atau unduh langsung ke komputer lokal.
2. **Melalui Skrip Windows (`BACKUP-DATABASE.bat`):**
   - Klik dua kali file `BACKUP-DATABASE.bat` di folder utama aplikasi.
   - Skrip akan membaca konfigurasi `.env`, menjalankan `pg_dump`, memverifikasi ukuran file hasil backup tidak kosong, dan menyimpannya ke `backups/BACKUP_SIGMA_ERP_<TIMESTAMP>.sql`.

### B. Restore Database
1. **Melalui Skrip Windows (`RESTORE-DATABASE.bat`):**
   - Klik dua kali file `RESTORE-DATABASE.bat`.
   - Pilih nomor file backup `.sql` dari daftar yang ditampilkan di layar.
   - Ketik `YA` saat diminta konfirmasi.
   - Sebelum menimpa data, skrip secara otomatis membuat **backup pengaman** (`PRE_RESTORE_<TIMESTAMP>.sql`) di folder `backups/`.
   - Skrip menjalankan `psql -v ON_ERROR_STOP=1` sehingga proses berhenti seketika dan melaporkan error dengan jelas apabila terjadi kegagalan SQL.
2. **Melalui Command Line Manual:**
   ```bash
   psql -h 127.0.0.1 -p 5432 -U postgres -d sigma_erp -v ON_ERROR_STOP=1 -f backups/NAMA_FILE_BACKUP.sql
   ```
