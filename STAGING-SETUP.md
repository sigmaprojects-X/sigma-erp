# Staging SIGMA ERP

Staging memakai URL `https://test.erp.sigmajayanusantara.biz.id`, folder aplikasi `/var/www/sigma-erp-staging`, dan database PostgreSQL terpisah. Jangan menyalin database production ke staging.

## Prasyarat satu kali

1. Buat DNS A record `test.erp.sigmajayanusantara.biz.id` ke IP VPS.
2. Konfigurasikan virtual host dan TLS agar subdomain tersebut mengarah ke `/var/www/sigma-erp-staging/public`.
3. Buat database PostgreSQL kosong khusus staging dan role yang hanya punya akses ke database itu.
4. Siapkan file `.env.staging` berisi `APP_ENV=staging`, `APP_DEBUG=false`, `APP_URL=https://test.erp.sigmajayanusantara.biz.id`, `APP_KEY` unik, serta kredensial database staging. Pastikan `DB_DATABASE` berbeda dari production.
5. Simpan isi file sebagai GitHub Actions secret `STAGING_ENV_B64`. Di PowerShell, base64 dapat dibuat tanpa menampilkan isi file dengan:
   `[Convert]::ToBase64String([IO.File]::ReadAllBytes('.env.staging'))`
6. Buat branch `staging` dari `main`. Push ke branch itu akan men-deploy hanya ke folder staging. GitHub Actions memakai secrets VPS yang sudah ada dan secret `STAGING_ENV_B64`.

Workflow menolak APP_ENV selain staging, APP_DEBUG selain false, URL yang berbeda, dan database yang sama dengan production. Migrasi dijalankan terhadap database staging saja.

## Setelah deploy pertama

Login sebagai owner pada URL staging, buat perusahaan `SIGMA DEMO`, lalu buat user kasir yang ditetapkan hanya ke toko SIGMA DEMO. Jangan membuat akun percobaan pada production.
