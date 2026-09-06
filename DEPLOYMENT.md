# Panduan Deployment MAXPORT

> **Maximize Your Export, Minimize The Risk**

Stack: Laravel 13 · PHP 8.4 · MySQL 8.4 · Tailwind CSS 3 (Vite) · nginx + php-fpm.

| Skenario                | Status         | Bagian |
| ------------------------ | -------------- | ------ |
| Lokal tanpa Docker       | pengembangan   | §3     |
| Docker lokal / staging   | pengembangan   | §4     |
| **Railway**              | **produksi**   | §5     |

---

## 1. Arsitektur Container

Satu image, beberapa peran. Peran dipilih lewat argumen `command`:

| Perintah    | Fungsi                                                             |
| ----------- | ------------------------------------------------------------------ |
| `web`       | nginx + php-fpm (supervisord), melayani HTTP di `$PORT` (default 8080) |
| `worker`    | `php artisan queue:work`                                            |
| `scheduler` | `php artisan schedule:work`                                         |
| `setup`     | Tunggu DB → migrasi → `storage:link` → cache config/route/view      |
| `artisan …` | Menjalankan perintah artisan apa pun                                |

Pada Docker Compose lokal, `setup` berjalan sekali sampai selesai; `app` dan
`worker` menunggunya lewat `depends_on: service_completed_successfully`.
Railway tidak punya service `setup` terpisah, jadi migrasi dijalankan saat
start dengan `RUN_MIGRATIONS_ON_START=true`.

Build image bertahap:

```
node:22-alpine      →  npm ci + vite build        (public/build)
composer:2          →  composer install --no-dev  (vendor)
php:8.4-fpm-alpine  →  runtime: nginx + php-fpm + supervisord
```

`.env` **tidak** ikut masuk ke image. Konfigurasi disuntikkan lewat environment
saat container dijalankan, dan `config:cache` dibangun ulang tiap kali start.

---

## 2. Variabel Wajib

Di lingkungan mana pun, minimal ini harus terisi:

| Variabel                              | Catatan                                              |
| ------------------------------------- | ---------------------------------------------------- |
| `APP_KEY`                             | Container **menolak start** tanpa ini                |
| `APP_URL`                             | Harus `https://…` di produksi                        |
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Koneksi MySQL                       |
| `APP_ENV=production`, `APP_DEBUG=false` | Default image, jangan diubah di produksi           |

Membuat `APP_KEY`:

```bash
php artisan key:generate --show
# atau, tanpa PHP lokal:
docker run --rm maxport-app artisan key:generate --show
```

---

## 3. Menjalankan Lokal (tanpa Docker)

Prasyarat: PHP ≥ 8.3 (ekstensi `pdo_mysql`, `mbstring`, `fileinfo`, `zip`, `curl`,
`openssl`, `bcmath`), Composer, Node 20+, MySQL 8.

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

mysql -u root -p -e "CREATE DATABASE maxport_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate

npm run dev          # terminal 1
php artisan serve    # terminal 2  → http://localhost:8000
```

Test: `php artisan test` (memakai SQLite in-memory, jadi `pdo_sqlite` perlu aktif).

---

## 4. Docker — Lokal / Staging

```bash
cp .env.docker.example .env.docker
docker compose run --rm --entrypoint php app artisan key:generate --show   # tempel ke .env.docker
docker compose up -d --build
docker compose logs -f app
```

Aplikasi tersedia di `http://localhost:8080` (ubah lewat `APP_PORT`).

```bash
docker compose exec app php artisan migrate:status
docker compose run --rm app artisan tinker
docker compose down       # hentikan
docker compose down -v    # hentikan + hapus volume database
```

---

## 5. Produksi — Railway

Railway membangun langsung dari `Dockerfile` di root repo, jadi tidak ada
image atau pipeline terpisah yang perlu dirawat.

Berkas konfigurasi: [railway.json](railway.json) (service web) dan
[railway.worker.json](railway.worker.json) (service worker, opsional).
Referensi variabel environment lengkap: [.env.railway.example](.env.railway.example).

### 5.1 Menyiapkan Project

1. Buat project baru di Railway, pilih **Deploy from GitHub repo**.
2. Railway membaca `railway.json` → builder `DOCKERFILE`, start command `web`,
   healthcheck `/up`.
3. Tambahkan **MySQL** dari menu *+ New → Database → MySQL*.

### 5.2 Variabel Environment

Buka **Service web → Variables → Raw Editor**, lalu tempel isi
[.env.railway.example](.env.railway.example) dan isi setiap nilai kosong.
Ringkasannya:

```
APP_NAME=MAXPORT
APP_ENV=production
APP_KEY=base64:…            # hasil `php artisan key:generate --show`
APP_DEBUG=false
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}

APP_LOCALE=id
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

TRUSTED_PROXIES=*
RUN_MIGRATIONS_ON_START=true
```

Ditambah, sesuai fitur yang aktif:

| Fitur                        | Variabel                                                        |
| ----------------------------- | ----------------------------------------------------------------- |
| Login Google (opsional)       | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` — tombol "Google" hanya tampil bila `GOOGLE_CLIENT_ID` diisi |
| reCAPTCHA v2 di login/register (opsional) | `CAPTCHA_SITE_KEY`, `CAPTCHA_SECRET_KEY` — widget hanya tampil bila `CAPTCHA_SITE_KEY` diisi |
| Product Screening / compliance engine | `COMPLIANCE_ENGINE` (`backend_ai` default, offline, hanya USB-C Cable · `gemini` · `anthropic`), plus `GEMINI_API_KEY`/`ANTHROPIC_API_KEY` bila dipakai |
| Email                         | `MAIL_MAILER`, `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, dst. |

Catatan penting:

* **`RUN_MIGRATIONS_ON_START=true`** — Railway tidak punya service `setup`
  terpisah, jadi migrasi dijalankan saat container start.
* **`PORT`** diisi Railway secara otomatis; entrypoint menyesuaikan port
  nginx dari variabel itu. Jangan set manual.
* **`SESSION_DOMAIN` dikosongkan** pada domain `*.up.railway.app` — mengisinya
  dengan domain yang salah adalah penyebab paling umum `419 Page Expired`.

### 5.3 Deploy

```bash
npm i -g @railway/cli
railway login
railway link
railway up
railway logs
```

Atau cukup `git push` bila repo sudah tersambung — Railway build otomatis
setiap push ke branch yang di-deploy.

Setelah deploy pertama, buka **Settings → Networking → Generate Domain**, lalu
pastikan `APP_URL` memakai domain itu (atau custom domain bila dipasang).

### 5.4 Verifikasi

```bash
railway logs
curl -I https://<domain-railway-anda>/up   # harus 200
```

### 5.5 Worker (opsional)

Antrean baru diperlukan kalau ada job berat (mis. analisis dokumen). Belum ada
job seperti itu di kode saat ini — service web saja sudah cukup sampai
dibutuhkan.

1. *+ New → GitHub Repo* (repo yang sama) → beri nama `maxport-worker`.
2. **Settings → Config-as-code**: isi `railway.worker.json`.
3. Salin variabel environment dari service web, kecuali
   `RUN_MIGRATIONS_ON_START` (biarkan `false`/kosong agar migrasi tidak
   berjalan dua kali).

### 5.6 Update Versi & Rollback

Deploy baru berjalan otomatis tiap `git push` (atau `railway up` manual).
Migrasi baru ikut jalan lewat `RUN_MIGRATIONS_ON_START` sebelum service web
yang baru menerima trafik.

Rollback: **Deployments → pilih deployment sebelumnya → Redeploy** di
dashboard Railway, atau `git revert` lalu push. Migrasi skema tidak ikut
di-rollback otomatis — turunkan manual dengan
`railway run php artisan migrate:rollback --step=1` bila perlu.

### 5.7 Backup Database

Database MySQL Railway tidak punya backup otomatis di paket dasar —
jadwalkan sendiri, mis. lewat GitHub Actions cron yang menjalankan:

```bash
railway run mysqldump -h "$MYSQLHOST" -P "$MYSQLPORT" -u "$MYSQLUSER" -p"$MYSQLPASSWORD" \
    --single-transaction "$MYSQLDATABASE" | gzip > backup-$(date +%F).sql.gz
```

### 5.8 Batasan Railway

* Filesystem container **ephemeral** — file di `storage/app` hilang tiap
  redeploy. Pakai Railway Volume (mount ke `/app/storage/app`) atau S3
  bila nanti ada unggahan dokumen.
* Biaya mengikuti pemakaian (compute + database).

---

## 6. Login Google (opsional)

Tombol "Google" pada halaman login/daftar **hanya muncul** kalau
`GOOGLE_CLIENT_ID` terisi.

1. Buat OAuth Client ID (tipe *Web application*) di Google Cloud Console.
2. Authorized redirect URI: `https://<domain-anda>/auth/google/callback`
3. Isi `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`.
4. Redeploy / restart service.

Akun yang dibuat lewat Google tidak punya kata sandi lokal; halaman pengaturan
mengarahkan pengguna ke alur "Lupa kata sandi" untuk membuatnya.

---

## 7. Troubleshooting

| Gejala                                       | Penyebab & solusi                                                                                    |
| --------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| `FATAL: APP_KEY belum diisi`                 | Generate lalu isi `APP_KEY`.                                                                           |
| `FATAL: database tidak merespons`            | Service MySQL belum sehat atau `DB_HOST` salah. Cek log MySQL / variable reference `${{MySQL.MYSQLHOST}}`. |
| Halaman tampil tanpa CSS                     | Stage `assets` gagal build. Pastikan `public/build/manifest.json` ada di image.                          |
| Redirect loop / URL jadi `http://`           | Header proxy tidak dipercaya. Cek `TRUSTED_PROXIES=*` dan `APP_URL` (`https://`).                          |
| `419 Page Expired` setelah login             | `SESSION_DOMAIN` salah (harus kosong di `*.up.railway.app`), atau `SESSION_SECURE_COOKIE=true` diakses lewat HTTP. |
| Railway healthcheck gagal                    | Umumnya migrasi gagal saat start. Cek `railway logs`; pastikan variabel `DB_*` benar.                     |

Melihat log:

```bash
railway logs
```

---

## 8. Catatan Keamanan

* `APP_DEBUG=false` dan `APP_ENV=production` — sudah default di image.
* `.env.docker` dan `.env.railway` masuk `.gitignore`; jangan pernah di-commit
  versi terisinya.
* Ganti semua password contoh (`change-me`, `root-change-me`) di
  `.env.docker.example` sebelum dipakai di luar mesin lokal.
