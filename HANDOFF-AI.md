# HANDOFF-AI.md - Serah Terima untuk AI Lain (ChatGPT / Codex / dll.)

> Baca `AGENTS.md` terlebih dahulu. Semua aturan bisnis di sana WAJIB dipatuhi.

## Stack
- Laravel 11/12 (Modular Monolith), PostgreSQL schema `sigma`, UUID PK, `snake_case`.
- Frontend: Tailwind CSS + Alpine.js.
- Multi-tenant: semua query wajib difilter `company_id` (dan `store_id` bila relevan).
- Semua write DB wajib `DB::transaction`.

## Deploy
- Repo GitHub: `sigmaprojects-X/sigma-erp`, branch `main`.
- Setiap `git push origin main` memicu GitHub Actions (`.github/workflows/deploy.yml`) yang otomatis:
  `git reset --hard origin/main` -> `php artisan migrate --force` -> `optimize:clear` -> reload php-fpm di VPS Biznet Gio (`/var/www/sigma-erp`).
- Tidak perlu akses manual ke VPS. Secrets (`VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY`) ada di GitHub.
- Situs: http://erp.sigmajayanusantara.biz.id

## Alur Kerja
```bash
git pull origin main
# ... ubah kode ...
php artisan test
git add .
git commit -m "fix: deskripsi singkat"
git push origin main
```

## Status Terakhir
- Multi-tenancy scoping sudah diterapkan di POS, purchasing, warehouse, receivables.
- Dashboard Penjualan (`SalesController`) sudah difilter `company_id` (commit `827ef4c`).
- Perusahaan: `PT SIGMA JAYA NUSANTARA` (pusat/demo) dan `CV OCTA MAKMUR JAYA`.

## Prompt Pembuka
```text
Saya mengembangkan SIGMA ERP & POS (Laravel 11/12, PostgreSQL schema sigma, multi-tenant).
Baca AGENTS.md dan HANDOFF-AI.md di root repo sebelum mengubah apa pun.
Deploy otomatis via GitHub Actions saat push ke main.
Tugas: [isi di sini]
```
