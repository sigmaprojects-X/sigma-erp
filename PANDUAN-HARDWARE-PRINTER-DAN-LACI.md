# 🖨️ Panduan Konfigurasi Hardware POS, Printer Thermal & Laci Kasir
### SIGMA ERP & POS Pro — Sistem Terintegrasi Komersil

Dokumen ini berisi panduan teknis operasional untuk menghubungkan perangkat keras kasir (Hardware POS): **Printer Struk Thermal**, **Laci Uang (Cash Drawer)**, dan **Barcode Scanner** ke aplikasi SIGMA POS.

---

## 1. ⚙️ Pengaturan Printer Struk Thermal (58mm & 80mm)

SIGMA POS dirancang fleksibel untuk mendukung seluruh merek printer thermal populer di Indonesia:
* **Ukuran 58mm (Mini/Bluetooth/USB):** Panda PRJ-58, Panda Mobile, Iware 58, VSC, Eppos, GOOJPRT PT-210, RPP02N.
* **Ukuran 80mm (Auto-Cutter POS Desktop):** Epson TM-T82, Xprinter XP-N160II / XP-Q200, Bixolon SRP-330, Janz, Star Micronics.

### Cara Menjadikan Default Printer di Windows:
1. Pasang kabel USB printer ke komputer kasir atau hubungkan Bluetooth printer ke Windows.
2. Buka **Control Panel Windows -> Devices and Printers** (atau *Settings -> Bluetooth & devices -> Printers & scanners*).
3. Klik kanan pada printer kasir Anda, lalu pilih **"Set as default printer"**.
4. Masuk ke **Printing Preferences -> Paper / Quality**:
   * Jika menggunakan roll 58mm: Pilih kertas `58mm x Receipt` (atau lebar 48mm).
   * Jika menggunakan roll 80mm: Pilih kertas `80mm x Receipt` (atau lebar 72mm).

### Pengaturan di Dalam Layar Kasir SIGMA POS:
1. Buka layar kasir di `http://localhost:8008/pos`.
2. Di baris header atas kasir, klik tombol **🖨️ Mini Printer: 58mm**.
3. Di modal pengaturan yang muncul:
   * **Pilih Ukuran Kertas:** `58mm` atau `80mm`.
   * **Centang "Auto Print Struk Setelah Checkout":** Struk langsung dicetak begitu kasir selesai memproses pembayaran.
   * **Centang "Buka Laci Kasir Otomatis (Drawer Kick)":** Mengirim sinyal pulsa untuk membuka laci kasir secara otomatis saat pembayaran tunai.
   * **Klik tombol "🖨️ Cetak Uji Coba":** Untuk memastikan kertas keluar rapi dan pisau auto-cutter berfungsi.

---

## 2. ⚡ Koneksi & Cara Kerja Laci Uang Kasir (Cash Drawer Kick)

Laci kasir (Cash Drawer) di sistem POS modern **tidak dicolok ke komputer**, melainkan **dicolok ke bagian belakang printer thermal** menggunakan kabel telepon **RJ-11 / RJ-12 (6-pin)**.

### Alur Kerja:
1. Colokkan kabel RJ-11 dari laci kasir ke soket bertanda **DK (Drawer Kick)** di bagian belakang printer thermal.
2. Saat kasir menekan tombol selesai transaksi:
   * **Transaksi Tunai (CASH) atau SPLIT Tunai:** Sistem otomatis mengirimkan kode pulsa ESC/POS (`ESC p 0 25 250`).
   * Printer thermal menerima sinyal tersebut dan langsung mengalirkan tegangan 12V/24V ke solenoid laci, sehingga **laci kasir langsung melompat terbuka secara otomatis (Auto-Kick)**.
3. **Buka Laci Manual (Tanpa Transaksi):**
   * Kasir dapat menekan tombol pintas keyboard **`F2`** di layar POS kasir untuk membuka laci kapan saja (misal untuk menukar uang kecil).

---

## 3. 🚀 Mode Kiosk Silent Printing (Cetak Struk Instan Tanpa Pop-up)

Agar kasir bekerja secepat kasir minimarket modern (Indomaret/Alfamart) di mana **struk langsung keluar instan tanpa perlu menekan tombol konfirmasi print di browser**:

### Cara Menjalankan Mode Kiosk:
1. Cukup dobel klik file **`START-KASIR-KIOSK.bat`** di folder SIGMA ERP.
2. Aplikasi kasir akan terbuka dalam jendela khusus (*Dedicated App Window*) tanpa URL bar dan tanpa tab browser.
3. Fitur **`--kiosk-printing`** otomatis aktif:
   * Setiap kali transaksi kasir selesai, struk langsung tercetak di printer kasir dalam hitungan 0.5 detik tanpa memunculkan kotak dialog browser sama sekali!
   * Kasir langsung siap melayani pelanggan berikutnya tanpa jeda.

---

## 4. 🔍 Barcode Scanner (USB / Wireless)

SIGMA POS mendukung 100% semua jenis barcode scanner:
* **Tipe:** Barcode Scanner Laser 1D, Scanner 2D (QR Code), Scanner Duduk Omni-directional.
* **Instalasi:** **Plug and Play** tanpa perlu driver khusus (scanner terdeteksi sebagai keyboard input berkecepatan tinggi).
* **Fitur Cerdas SIGMA POS:**
  * Kursor kasir otomatis selalu siap di kolom pencarian/jumlah.
  * Begitu barcode produk ditembak, produk otomatis masuk ke keranjang dalam waktu `< 100 milidetik`.
  * Jika kasir menembak produk yang sama berulang kali, jumlah item di keranjang bertambah secara otomatis tanpa mengulang input manual.

---

## 5. 🛠️ Ringkasan Tombol Pintas Kasir (Hardware Keyboard Shortcuts)

| Tombol Keyboard | Fungsi Hardware & Operasional |
| :--- | :--- |
| **`F1`** | Fokus langsung ke kolom cari barang / tembak barcode |
| **`F2`** | **Buka Laci Kasir (Cash Drawer Kick)** |
| **`F7`** | Tahan Transaksi / Buka Antrean Tertunda (*Pending Cart*) |
| **`F8` / `F12`** | **Buka Modal Pembayaran & Checkout Kasir** |
| **`Enter`** | Proses Selesai & Cetak Struk Instan |
| **`Del`** | Hapus baris item dari keranjang kasir |
| **`ESC`** | Batalkan modal / Kembali ke fokus penjualan kasir |

---
*© 2026 PT SIGMA RETAIL INDONESIA — Arsitektur POS Komersil Teruji.*
