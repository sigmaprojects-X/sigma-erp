# PANDUAN LENGKAP DEPLOYMENT SIGMA ERP & POS KE VPS LOKAL
### Target Hosting: Biznet GIO, Jagoan Hosting, IDCloudHost, KilatVM

Dokumen ini memandu Anda langkah demi langkah untuk mengonlinekan sistem **SIGMA ERP & POS (Multi-Company Holding)** ke server VPS cloud lokal Indonesia agar bisa diakses 24 jam nonstop dari komputer kasir toko, tablet, dan smartphone dari mana saja.

---

## 1. Rekomendasi Spesifikasi & Provider VPS Lokal

### A. Provider VPS Indonesia Terbaik:
1. **Biznet GIO (NEO Virtual Compute / Cloud)**:
   * Data center: Midplaza Jakarta & Technovillage Bogor / Banten.
   * Jaringan fiber optic Biznet kecepatan tinggi dengan latensi terendah untuk pulau Jawa, Bali, dan Sumatera.
2. **Jagoan Hosting (Cloud VPS Indonesia)**:
   * Data center: Surabaya & Jakarta (Tier-3).
   * Sangat bersahabat untuk pemula, dukungan pembayaran QRIS/Transfer Bank lokal instan.
3. **IDCloudHost (Cloud VPS KVM)**:
   * Data center: Equinix Cyber 1 Jakarta.
   * Mendukung sistem bayar per jam/bulan (Hourly/Monthly) dengan penyimpanan SSD NVMe super cepat.

### B. Spesifikasi Server yang Disarankan:
* **Sistem Operasi**: `Ubuntu 24.04 LTS` (atau `Ubuntu 22.04 LTS`)
* **CPU Core**: 2 vCPU
* **RAM / Memori**: 2 GB – 4 GB RAM
* **Penyimpanan**: 30 GB – 50 GB SSD / NVMe
* **Estimasi Biaya**: Rp 75.000 – Rp 150.000 / bulan.

---

## 2. Hubungkan Domain / Subdomain ke VPS

1. Beli domain di penyedia domain Anda (contoh: `perusahaananda.com` seharga ~Rp 120rb/tahun).
2. Masuk ke menu **DNS Management** domain Anda.
3. Buat **A Record**:
   * **Host / Name**: `erp` (atau `pos` / `@` jika domain utama)
   * **Type**: `A`
   * **Value / IP Address**: Masukkan **IP Publik VPS** Anda (misal: `103.xxx.xxx.xxx`)
   * **TTL**: `300` (atau Automatic)
4. Domain Anda sekarang siap mengarah ke: `erp.perusahaananda.com`.

---

## 3. Langkah Instalasi di Server VPS (Hanya 10 Menit)

Setelah Anda menerima akses VPS (IP Address, Username `root`, dan Password/SSH Key):

### Langkah 3.1: Login ke Server VPS via Terminal / PowerShell
Di komputer Anda, buka PowerShell dan ketik:
```powershell
ssh root@IP_VPS_ANDA
```
*(Masukkan password VPS saat diminta)*.

---

### Langkah 3.2: Clone Repository SIGMA dari GitHub
Unduh source code proyek Anda dari GitHub ke folder `/var/www/sigma-erp`:
```bash
sudo mkdir -p /var/www/sigma-erp
sudo git clone https://github.com/sigmaprojects-X/sigma-erp.git /var/www/sigma-erp
cd /var/www/sigma-erp
```

---

### Langkah 3.3: Jalankan Skrip Otomatisasi Installer
Jalankan skrip instalasi yang sudah disediakan:
```bash
chmod +x deploy/setup_vps_ubuntu.sh
./deploy/setup_vps_ubuntu.sh
```
Skrip ini akan secara otomatis menginstall Nginx, PHP 8.3-FPM, PostgreSQL 16, Composer, dan membuat database `sigma_erp`.

---

### Langkah 3.4: Siapkan Konfigurasi Lingkungan (`.env`)
Salin file template `.env`:
```bash
cp .env.example .env
nano .env
```
Sesuaikan parameter database di file `.env`:
```env
APP_NAME="SIGMA ERP PRO"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://erp.perusahaananda.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sigma_erp
DB_USERNAME=sigma_user
DB_PASSWORD=SigmaPassword8855
DB_SCHEMA=sigma,public
```
Simpan file (`Ctrl + O` lalu `Enter`, lalu `Ctrl + X`).

---

### Langkah 3.5: Install Dependency & Migrasi Database
```bash
# Install paket backend tanpa dev tools
composer install --no-dev --optimize-autoloader

# Generate kunci enkripsi aplikasi
php artisan key:generate

# Jalankan migrasi seluruh tabel & 3 perusahaan holding
php artisan migrate --force

# Optimasi performa cache Laravel di server
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

### Langkah 3.6: Aktifkan Konfigurasi Web Server Nginx
```bash
# Salin konfigurasi Nginx
sudo cp deploy/nginx_sigma.conf /etc/nginx/sites-available/sigma-erp

# Sesuaikan nama domain Anda di konfigurasi
sudo sed -i 's/erp.perusahaananda.com/erp.domainanda.com/g' /etc/nginx/sites-available/sigma-erp

# Aktifkan situs
sudo ln -s /etc/nginx/sites-available/sigma-erp /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Periksa konfigurasi & reload Nginx
sudo nginx -t
sudo systemctl reload nginx
```

---

### Langkah 3.7: Pasang Sertifikat SSL HTTPS Gratis (Let's Encrypt)
Pasang gembok hijau keamanan HTTPS agar komunikasi kasir terenkripsi aman:
```bash
sudo certbot --nginx -d erp.domainanda.com
```
Pilih opsi **Redirect HTTP to HTTPS (Otomatis)**.

---

## 4. Selesai! Sistem Anda Sudah Online 24 Jam

Buka browser di HP / Laptop Anda:
👉 **`https://erp.domainanda.com`**

* Login dengan akun Owner:
  * **Email**: `owner@sigma.test`
  * **Password**: `password123`
* Di pojok atas, Anda sudah bisa langsung menggunakan **Company Switcher** untuk mengelola:
  1. **PT SIGMA JAYA NUSANTARA**
  2. **CV OCTA MAKMUR JAYA**
  3. **ENDOGAN**
* Komputer kasir toko fisik cukup membuka link tersebut untuk mulai melayani transaksi penjualan!
