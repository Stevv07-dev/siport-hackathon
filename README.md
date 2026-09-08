<div align="center">
  
  # SIPORT
  ### Smart Export Platform — Maksimalkan ekspor Anda, minimalkan risikonya
  
  [![Live Demo](https://siport-hackathon-production.up.railway.app)]
  [![GitHub](https://github.com/Stevv07-dev/siport-hackathon)]
  
  **Submission for ITECHNO CUP 2026 - Web Development**
  
  **By MANDIRI**
  
</div>

---

## 📋 Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Fitur Unggulan](#-fitur-unggulan)
- [Demo & Screenshot](#-demo--screenshot)
- [Teknologi](#-teknologi)
- [Arsitektur Sistem](#-arsitektur-sistem)
- [Instalasi & Setup](#-instalasi--setup)
- [Penggunaan](#-penggunaan)
- [API Documentation](#-api-documentation)
- [Testing](#-testing)
- [Tim Developer](#-tim-pengembang)
- [Lisensi](#-lisensi)

---

## 👥 Tim Developer

| Nama | Peran | GitHub |
|------|-------|--------|
| **Steven Marcell Samosir** | Project Lead, UI/UX Designer, Full Stack Developer | [GitHub](https://github.com/Stevv07-dev) |

---

## 🎯 Tentang Proyek

### Latar Belakang

Bagi eksportir pemula dan UMKM di Indonesia, proses ekspor produk (seperti produk elektronik ke Singapura) sering menghadapi hambatan besar karena kompleksitas birokrasi, informasi regulasi yang tidak terpusat, dan ketidaksesuaian spesifikasi produk dengan standar negara tujuan. Kesalahan spesifikasi atau kurangnya dokumen pendukung dapat menyebabkan barang tertahan di bea cukai, mengalami penolakan, hingga menimbulkan kerugian finansial yang signifikan.

### Solusi yang Ditawarkan

**MAXPORT (SIPORT)** hadir sebagai *Smart Export Platform* yang membantu eksportir pemula dan UMKM melakukan persiapan ekspor secara terstruktur melalui pendekatan **Screen → Verify → Guide**:
1. **Product Screening**: Memeriksa spesifikasi teknis produk secara otomatis terhadap regulasi dan standar negara tujuan (seperti standar keselamatan listrik & regulasi Impor Singapura).
2. **Compliance Feedback**: Memberikan masukan mendalam jika terdapat parameter produk yang belum sesuai.
3. **Document Preparation**: Menghasilkan daftar otomatis dokumen ekspor yang wajib dipersiapkan berdasarkan kelompok produk.

### Tujuan Proyek

- 🎯 **Tujuan Utama**: Menyederhanakan alur kesiapan ekspor bagi eksportir pemula dan UMKM melalui screening otomatis dan panduan dokumen terpusat.
- 📊 **Target Pengguna**: Entrepreneur pemula, UMKM Indonesia, produsen elektrik/elektronik, dan *first-time exporters* yang membidik pasar ekspor Singapura.
- 💡 **Value Proposition**: Asistensi kesiapan ekspor instan dengan verifikasi spesifikasi berbasis AI & Rule Engine serta pembuatan checklist dokumen ekspor terintegrasi.

---

## ✨ Fitur Unggulan

### Fitur Utama

| Fitur | Deskripsi | Keunggulan |
|----------|--------------|---------------|
| **Interactive Product Screening** | Kuesioner dinamis untuk memeriksa spesifikasi teknis produk elektronik. | Verifikasi instan dengan umpan balik ketidaksesuaian yang presisi. |
| **Multi-Engine Compliance Verification** | Verifikasi kesesuaian regulasi menggunakan Rule Engine bawaan (Offline/USB-C) atau AI (Gemini / Claude). | Fleksibel, cepat, dan memberikan rekomendasi perbaikan spesifikasi secara ilmiah & berbasis standar regulasi. |
| **Automated Export Document Checklist** | Menghasilkan daftar dokumen ekspor yang wajib disiapkan sesuai kelompok produk. | Mengeliminasi pencarian dokumen secara manual di berbagai institusi. |
| **Authentication & Guest Session Finalizer** | Dukungan akun eksportir (Email/Password & Google OAuth) dengan kemampuan mengklaim hasil screening tamu (*guest session*) saat mendaftar. | Pengalaman pengguna (*UX*) yang seamless tanpa kehilangan data screening awal. |

### Fitur Tambahan

- **Guest Export Screening**: Pengunjung dapat langsung mencoba screening tanpa perlu mendaftar terlebih dahulu.
- **Export Session Claiming**: Sesi screening pengguna tamu otomatis ditautkan ke akun pengguna setelah mendaftar atau login.
- **Google OAuth Integration**: Login cepat dan aman menggunakan akun Google.
- **reCAPTCHA Protection**: Perlindungan keamanan formulir registrasi dan login dari bot (opsional).

---

## 📸 Demo & Screenshot

### Live Demo

🔗 **[Kunjungi Website](https://siport-hackathon-production.up.railway.app)** 

### Screenshot Aplikasi

<div align="center">
  <img src="public/logo/maxport-lockup-tagline.png" alt="Homepage" width="800"/>
  <p><em>MAXPORT - Smart Export Platform</em></p>
</div>

---

## 🛠️ Teknologi

### Tech Stack

#### Frontend
```
Framework    : Blade Templating (Laravel 13)
UI Library   : Tailwind CSS v3, Alpine.js
Icons        : Lucide Icons
Build Tool   : Vite
```

#### Backend
```
Runtime      : PHP 8.4
Framework    : Laravel 13
Database     : MySQL 8.4 (Production), SQLite (Testing)
Auth         : Laravel Breeze / Auth & Laravel Socialite (Google OAuth)
Security     : Google reCAPTCHA v2
AI Engine    : Google Gemini API / Anthropic Claude API / Rule-based Local Engine
```

#### DevOps & Tools
```
Deployment   : Railway / Docker (nginx + php-fpm + supervisord)
CI/CD        : GitHub Auto-Deploy
Testing      : PHPUnit (Feature & Unit Tests)
Environment  : Docker Compose
```

### Alasan Pemilihan Teknologi

| Teknologi | Alasan Pemilihan |
|-----------|------------------|
| **Laravel 13 & PHP 8.4** | Performa tinggi, keamanan out-of-the-box, ekosistem otentikasi & ORM (Eloquent) yang sangat stabil untuk aplikasi enterprise. |
| **Tailwind CSS & Vite** | Memungkinkan pembuatan UI modern yang responsif, cepat, dan terstruktur dengan aset terkompilasi secara optimal. |
| **MySQL 8.4 & Docker** | Penyimpanan data relational yang andal, portabel, dan siap di-deploy dalam container produksi di Railway. |

---

## 🏗️ Arsitektur Sistem

### System Architecture

```text
┌─────────────────────────────────────────────────────────┐
│                    User / Client                        │
└────────────────────────────┬────────────────────────────┘
                             │ HTTP / HTTPS
                             ▼
┌─────────────────────────────────────────────────────────┐
│                 Nginx (Port 8080 / 80)                  │
└────────────────────────────┬────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────┐
│                Laravel 13 App (PHP-FPM)                 │
│ ┌──────────────────────┐     ┌────────────────────────┐ │
│ │ Auth & Session Mgmt  │     │ Product Screening      │ │
│ └──────────────────────┘     └───────────┬────────────┘ │
│                                          │              │
│                                          ▼              │
│                              ┌────────────────────────┐ │
│                              │  Compliance Engine     │ │
│                              │ (Rule-based / Gemini)  │ │
│                              └────────────────────────┘ │
└──────────────┬───────────────────────────┬──────────────┘
               │                           │
               ▼                           ▼
┌────────────────────────┐    ┌───────────────────────────┐
│     MySQL Database     │    │  Google OAuth & reCAPTCHA │
└────────────────────────┘    └───────────────────────────┘
```

### Folder Structure

```
siport-hackathon/
├── app/
│   ├── Http/Controllers/   # RegisteredUser, AuthenticatedSession, dll.
│   ├── Models/             # Model User, ExportSession
│   ├── Rules/              # Recaptcha Rule
│   └── Services/           # ExportSessionFinalizer
├── config/                 # Konfigurasi Laravel (services, database, dll)
├── database/
│   ├── migrations/         # Tabel users, export_sessions, jobs, cache
│   └── seeders/            # DatabaseSeeder
├── docker/                 # Konfigurasi Docker (nginx, php.ini, supervisord, entrypoint)
├── public/                 # Static assets & logo
├── resources/
│   ├── css/                # Style Tailwind
│   ├── js/                 # Javascript / Vite assets
│   └── views/              # Template Blade (welcome, auth, dashboard, components)
├── routes/                 # Web & Auth routes
├── tests/                  # Unit & Feature tests (PHPUnit)
├── Dockerfile              # Multi-stage Docker build
├── railway.json            # Konfigurasi deployment Railway
└── vite.config.js          # Konfigurasi Vite
```

---

## ⚙️ Instalasi & Setup

### Prerequisites

Pastikan Anda telah menginstal:
- **PHP** (v8.3 atau lebih tinggi dengan ekstensi `pdo_mysql`, `mbstring`, `zip`, `curl`)
- **Composer**
- **Node.js** (v20.x atau lebih tinggi) & **npm**
- **MySQL** (v8.0 atau lebih tinggi)
- **Git**

### Langkah Instalasi

#### 1️⃣ Clone Repository

```bash
git clone https://github.com/Stevv07-dev/siport-hackathon.git
cd siport-hackathon
```

#### 2️⃣ Install Dependencies

```bash
# Dependencies PHP
composer install

# Dependencies JavaScript / Asset Frontend
npm install
```

#### 3️⃣ Setup Environment Variables

Buat berkas `.env` dari contoh `.env.example`:

```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan variabel koneksi database di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siport_db
DB_USERNAME=root
DB_PASSWORD=
```

#### 4️⃣ Setup Database

Buat database `siport_db` di MySQL Anda, lalu jalankan migrasi dan seeder:

```bash
php artisan migrate --seed
```

#### 5️⃣ Run Development Server

Jalankan server aplikasi dan Vite (Asset Bundler):

```bash
# Terminal 1 - Laravel Server
php artisan serve

# Terminal 2 - Vite Development Server
npm run dev
```

Aplikasi akan berjalan di `http://localhost:8000`.

---

## 🚀 Penggunaan

### Menjalankan Aplikasi

```bash
# Development mode
php artisan serve
npm run dev

# Run tests
php artisan test

# Clear cache
php artisan config:clear
php artisan view:clear
```

### User Guide

#### Untuk Pengguna / Eksportir

1. **Product Screening**: Buka halaman utama, pilih kelompok produk elektronik, lalu isi kuesioner spesifikasi teknis produk Anda.
2. **Compliance Result**: Dapatkan hasil verifikasi kesesuaian produk secara instan beserta feedback perbaikan jika ada parameter yang tidak sesuai.
3. **Document Checklist**: Lihat daftar dokumen ekspor yang wajib disiapkan berdasarkan spesifikasi produk Anda.
4. **Registrasi / Login**: Daftarkan akun untuk menyimpan hasil sesi screening dan mengakses fitur dashboard eksportir.

---

## 🧪 Testing

### Running Tests

Aplikasi dilengkapi dengan pengujian otomatis menggunakan PHPUnit:

```bash
# Menjalankan seluruh test suite
php artisan test

# Menjalankan test spesifik
php artisan test --filter=RecaptchaIntegrationTest
```

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE) - lihat file LICENSE untuk detail lebih lanjut.

---

<div align="center">

  **Made with ❤️ by MANDIRI for ITECHNO CUP 2026**

</div>
