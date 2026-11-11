# Cutover: API-Kei-ichiman + kei-ichiman/dashboard → kei-v2

Website publik (flat7 / joycal / keigayasui / gyauto di `kei-ichiman`) memanggil
**`https://api.kei-ichiman.com/api/...`** dan memuat gambar dari
**`https://api.kei-ichiman.com/storage/{car,maker,customer}/...`** (hardcoded di `assets/*/js/*.js`).
Jadi cutover = arahkan domain `api.kei-ichiman.com` ke `kei-v2/public`. JS website publik **tidak perlu diubah**:
URL dan format JSON `/api/*` sama dengan API lama.

## Persiapan (sebelum hari-H)

- [ ] `git init` + commit di kei-v2 (sekarang belum ada version control).
- [ ] Server: PHP ≥ 8.3 dengan ekstensi `gd`, `pdo_mysql`, `mbstring`; MySQL/MariaDB.
- [ ] `.env` production:
  - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://api.kei-ichiman.com`
  - `DB_*` → database baru (`kei_v2`), `LEGACY_DB_*` → database lama (`ichiman`)
  - `MAIL_*` → SMTP asli (**di lokal masih `MAIL_MAILER=log`**)
  - `SESSION_DRIVER=database`
- [ ] Deploy:
  ```bash
  composer install --no-dev --optimize-autoloader
  npm ci && npm run build
  php artisan migrate --force
  php artisan db:seed --class=RoleSeeder --force
  php artisan storage:link
  php artisan config:cache && php artisan route:cache && php artisan view:cache
  ```
- [ ] Gladi: `php artisan legacy:import --dry-run` dengan salinan database production. Semua status harus `OK`.
- [ ] Cek UI di browser (desktop + HP): login, 6 halaman admin, modal detail, export CSV, upload gambar mobil/foto.

## Hari-H

1. [ ] `php artisan down` di **API-Kei-ichiman** (form website publik berhenti sebentar, usahakan jendela singkat).
2. [ ] Backup: `mysqldump ichiman > ichiman_YYYYMMDD.sql`.
3. [ ] `php artisan legacy:import` di kei-v2 → tabel verifikasi harus semua `OK`.
4. [ ] Salin **seluruh** `API-Kei-ichiman/storage/app/public/` → `kei-v2/storage/app/public/`
       (`car/`, `customer/`, dan **`maker/`** (logo maker, tidak dikelola admin tapi dipakai website)).
5. [ ] Arahkan `api.kei-ichiman.com` ke `kei-v2/public`.
6. [ ] Smoke test production:
   - [ ] website publik: slide & daftar mobil tampil, gambar muncul, galeri foto 納車式 tampil
   - [ ] kirim reservasi uji dengan email `ghobedku@gmail.com` (jalur test: tidak dikirim ke staf toko) → muncul di admin + email diterima
   - [ ] login admin (akun superadmin lama, password sama), buka semua halaman
7. [ ] Matikan admin lama: hapus atau blokir `kei-ichiman/dashboard/` dan `kei-ichiman/login.php`
       (backup dulu). Website publik lainnya di `kei-ichiman` **jangan** disentuh.

## Setelah cutover

- [ ] Simpan database `ichiman` (read-only) beberapa minggu untuk rollback/referensi.
- [ ] Rollback jika perlu: arahkan domain kembali ke API-Kei-ichiman + `php artisan up` di sana.
- [ ] Keputusan tertunda: BCC email untuk store keigayasui (API lama tidak pernah mengirim BCC
      karena bug kolom `bcc`; perilaku itu dipertahankan di `App\Services\ReservationMailer`).
