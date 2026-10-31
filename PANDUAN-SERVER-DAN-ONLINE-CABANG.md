# 📖 PANDUAN LENGKAP: MENJALANKAN SERVER & AKSES ONLINE CABANG SIGMA ERP

Dokumen ini berisi panduan praktis untuk menjalankan aplikasi **SIGMA ERP & POS** seperti software komersial siap pakai: tanpa setting rumit, mudah dijalankan, mudah dipindahkan ke komputer lain, dan siap diakses secara online oleh cabang luar toko.

---

## 🚀 DAFTAR SCRIPT 1-KLIK (FILE BATCH TERSEDIA)

Di dalam folder utama proyek `SIGMA ERP`, telah disediakan file otomasi:

| Nama File | Fungsi Utama | Cara Menjalankan |
|---|---|---|
| `START-SIGMA-SERVER.bat` | Menyalakan server ERP & POS, auto-cek DB, dan membuka browser | Klik 2x (Double Click) |
| `STOP-SIGMA-SERVER.bat` | Menghentikan server lokal yang sedang berjalan | Klik 2x |
| `ONLINE-TUNNEL-CABANG.bat` | Membuka akses online internet (HTTPS) aman untuk cabang luar kota | Klik 2x |
| `UPDATE-SIGMA-ERP.bat` | Memperbarui sistem, migrasi fitur baru, dan refresh cache (Aman) | Klik 2x |
| `BACKUP-DATABASE.bat` | Menyimpan seluruh database ke file `.sql` di folder `backups/` | Klik 2x |
| `RESTORE-DATABASE.bat` | Memulihkan data dari file backup `.sql` | Klik 2x |
| `SETUP-KOMPUTER-BARU.bat` | Otomasi instalasi saat pindah ke komputer server baru | Klik 2x |

---

## 1. MENJALANKAN SERVER DI KOMPUTER UTAMA (SERVER TOKO)

1. Cukup klik 2x file **`START-SIGMA-SERVER.bat`**.
2. Script otomatis akan:
   - Memeriksa PHP & koneksi database PostgreSQL.
   - Menjalankan sinkronisasi tabel database.
   - Menyalakan server pada port `8008`.
   - Menampilkan alamat IP lokal komputer Anda.
   - Membuka browser secara otomatis ke alamat `http://localhost:8008`.
3. Jendela hitam server ini harus **tetap dibiarkan terbuka** selama toko beroperasi.

---

## 2. CARA MENGHUBUNGKAN KASIR LAIN DI TOKO (JARINGAN LAN / WI-FI)

Jika Anda memiliki beberapa komputer kasir atau tablet di dalam toko fisik yang sama (terhubung ke router Wi-Fi yang sama):

1. Buka file `START-SIGMA-SERVER.bat` di komputer server utama.
2. Perhatikan baris bertuliskan:
   ```text
   [2] Akses Kasir Lain / Komputer Toko (Jaringan LAN / Wi-Fi):
       http://192.168.1.19:8008
   ```
   *(Nomor IP akan otomatis mendeteksi IP komputer server Anda)*.
3. Di komputer kasir 2 atau tablet kasir, buka browser (Chrome / Edge) lalu ketikkan alamat tersebut:
   `http://192.168.1.19:8008` (sesuaikan dengan IP server Anda).
4. Komputer kasir langsung bisa login dan bertransaksi tanpa perlu instalasi apa pun!

---

## 3. CARA MENGHUBUNGKAN CABANG LUAR TOKO (ONLINE TANPA IP PUBLIK)

Jika Anda memiliki **cabang di luar toko, staff gudang luar, atau Owner yang ingin memantau omzet dari rumah / HP**:

1. Pastikan server utama toko sedang berjalan via `START-SIGMA-SERVER.bat`.
2. Klik 2x file **`ONLINE-TUNNEL-CABANG.bat`**.
3. Sistem akan membuat terowongan aman terenkripsi SSL (**Cloudflare Tunnel**).
4. Di jendela terminal akan muncul link publik HTTPS gratis, contohnya:
   ```text
   Your quick Tunnel has been created! Visit it at:
   https://random-subdomain-anda.trycloudflare.com
   ```
5. **Salin link tersebut** dan bagikan ke kasir cabang luar toko atau buka dari browser smartphone Anda.
6. **Keunggulan:**
   - 100% Aman (Terkoneksi lewat Cloudflare SSL/HTTPS).
   - Tidak perlu membayar sewa IP Publik Statis ke penyedia internet (Telkom/Indihome/FirstMedia).
   - Tidak perlu setting port forwarding router.

---

## 4. CARA MEMINDAHKAN APLIKASI KE KOMPUTER LAIN / SERVER BARU (ZERO RIBET)

Jika komputer server lama rusak atau Anda ingin memindahkan SIGMA ERP ke komputer server yang lebih cepat:

### Langkah A: Di Komputer Lama
1. Klik 2x file **`BACKUP-DATABASE.bat`**.
2. File database akan tersimpan di dalam folder `backups/sigma_erp_backup_YYYYMMDD_HHMMSS.sql`.
3. Salin (copy) seluruh folder `SIGMA ERP` (atau salin file backup tadi) ke Flashdisk / Harddisk Eksternal.

### Langkah B: Di Komputer Baru
1. Pasang aplikasi pendukung di komputer baru:
   - **PHP 8.3+** (centang *Add to PATH*).
   - **PostgreSQL 16+** (dengan password yang Anda ingat).
2. Pindahkan folder `SIGMA ERP` dari Flashdisk ke drive `D:\` atau `C:\`.
3. Klik 2x file **`SETUP-KOMPUTER-BARU.bat`**.
   - Masukkan password PostgreSQL komputer baru saat ditanya.
   - Script akan otomatis membuat database `sigma_erp`, migrasi tabel, dan membuat **Shortcut SIGMA ERP Server di Desktop Windows**.
4. Klik 2x file **`RESTORE-DATABASE.bat`** untuk memulihkan seluruh data transaksi dan barang dari komputer lama.
5. Selesai! Klik shortcut di desktop atau klik `START-SIGMA-SERVER.bat`, aplikasi langsung berjalan 100% normal.

---

## 5. KEAMANAN & PRAKTIK TERBAIK
* Lakukan backup database harian atau mingguan menggunakan `BACKUP-DATABASE.bat`.
* File backup di folder `backups/` dapat disimpan ke Google Drive untuk perlindungan maksimal.
* Untuk mematikan server dengan bersih di akhir hari kerja, klik 2x file **`STOP-SIGMA-SERVER.bat`**.

---

## 6. CARA UPDATE & PERBAIKAN APLIKASI (AMAN & DATA TETAP UTUH)

Jika di kemudian hari ada pembaruan fitur, perbaikan bug, atau penambahan modul baru:

1. **Apakah data transaksi, stok, dan akun akan hilang?**
   **TIDAK AKAN HILANG SAMA SEKALI.** Data tersimpan terpisah secara aman di database PostgreSQL.
2. **Cara Menerapkan Update:**
   - Masukkan file pembaruan (copy paste file baru / patch / `git pull`) ke dalam folder `SIGMA ERP`.
   - Klik 2x file **`UPDATE-SIGMA-ERP.bat`**.
3. **Apa yang dilakukan oleh `UPDATE-SIGMA-ERP.bat` secara otomatis:**
   - **Langkah 1 (Auto-Backup):** Otomatis membuat salinan backup database terbaru ke folder `backups/` sebagai pengaman data sebelum disentuh.
   - **Langkah 2 (Auto-Migration):** Menjalankan `php artisan migrate --force` untuk menambahkan kolom atau tabel baru tanpa menghapus data lama yang sudah ada.
   - **Langkah 3 (Auto-Refresh Cache):** Membersihkan cache tampilan, route, dan konfigurasi agar fitur baru langsung muncul.
   - **Langkah 4 (Auto-Asset Build):** Mengompilasi antarmuka terbaru.
4. Setelah update selesai, cukup jalankan kembali **`START-SIGMA-SERVER.bat`** seperti biasa!

