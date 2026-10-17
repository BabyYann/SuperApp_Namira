# Project Inventory (Inventaris Teknis Proyek)

Dokumen ini menyajikan inventaris lengkap komponen teknis, tumpukan teknologi (*technology stack*), struktur direktori, dan dependensi SuperApp Namira berdasarkan audit kode sumber aktual pada commit `971a22b9`.

---

## 1. Metadata Proyek

| Parameter | Nilai Faktual | Keterangan / Sumber Bukti |
|---|---|---|
| **Nama Aplikasi** | SuperApp Namira | Konfigurasi `.env` & `app.name` (EV-CORE-001) |
| **Versi Aplikasi** | v2.7.0 (Enterprise SaaS Multi-Unit) | Identitas sistem pada layout desktop/mobile |
| **Organisasi Pengembang** | Yayasan Namira School (Namira School Foundation) | Domain dan konfigurasi unit sekolah |
| **Arsitektur Utama** | Monolith Modular SPA (Single Page Application) | Laravel 12 + Inertia.js 3 + Vue 3 |
| **Baseline Git Commit** | `971a22b9` | Branch `main` |
| **Tanggal Audit** | 16 Oktober 2026 | Audit komprehensif independen |

---

## 2. Technology Stack & Runtime Dependencies

### 2.1 Backend Runtime & Framework
- **Bahasa Pemrograman:** PHP 8.2+ (Platform Composer: `8.4.0`)
- **Web Framework:** Laravel Framework versi `12.0.*`
- **Arsitektur Antarmuka:** Inertia.js Laravel Adapter `^3.0`
- **Autentikasi & Session:** Laravel Breeze (Session Web Guard) & Laravel Sanctum `^4.0` (API Token Guard)
- **Role & Access Control:** Spatie Laravel Permission `^6.24` (dengan dukungan multi-tenancy Spatie Teams `team_id`)
- **Pencatatan Audit Trail:** Spatie Laravel Activitylog
- **Real-time Engine:** Laravel Reverb (WebSocket server terintegrasi)
- **Monitoring & Telemetri:** Laravel Pulse
- **Manipulasi PDF:** Barryvdh Laravel DomPDF `^3.1`
- **Manipulasi Spreadsheet:** PhpOffice PhpSpreadsheet `^3.0`
- **Testing Framework:** Pest PHP `^4.1` & PHPUnit

### 2.2 Frontend Runtime & UI Libraries
- **Framework Antarmuka:** Vue.js `^3.5.40` (Composition API `<script setup>`)
- **Inertia Client:** `@inertiajs/vue3` `^3.6.1`
- **CSS Engine:** Tailwind CSS `^4.3.3` (menggunakan bundler resmi `@tailwindcss/vite` `^4.3.3`)
- **Build Tool & Bundler:** Vite `^8.2.0` & `@vitejs/plugin-vue` `^6.0.8`
- **Progressive Web App:** `vite-plugin-pwa` `^1.3.0` (Offline precache & Service Worker)
- **Komponen Ikon:** `@heroicons/vue` `^2.2.0` (Heroicons Outline & Solid)
- **Visualisasi Data / Grafik:** `chart.js` `^4.5.1` & `vue-chartjs` `^5.3.4`
- **Pemetaan Interaktif (Geofence):** `leaflet` `^1.9.4`
- **Rich Text Editor:** `@vueup/vue-quill` `^1.5.5`
- **Scanner Barcode / QR:** `html5-qrcode` `^2.3.8`, `@zxing/library` `^0.23.0`, `@ericblade/quagga2` `^1.12.1`, `jsqr` `^1.4.0`
- **Dialog & Notifikasi:** `sweetalert2` `^11.26.25`
- **Manipulasi Waktu & Kalender:** `dayjs` `^1.11.21` (Locale Indonesia `id`)

---

## 3. Struktur Direktori Proyek

```
SuperApp_Namira/
├── app/
│   ├── Console/Commands/        # Custom Artisan CLI commands
│   ├── Events/                  # Real-time WebSocket domain events
│   ├── Helpers/                 # Global utility helpers (ImageHelper, WhatsAppHelper)
│   ├── Http/
│   │   ├── Controllers/         # Core & API Controllers (95 controllers total)
│   │   │   └── Api/             # RESTful API endpoints (Mobile/PWA)
│   │   └── Middleware/          # Scoping, Unit, Feature flag, Maintenance middleware
│   ├── Models/                  # Core Eloquent models (User, AttendanceLocation, dll)
│   ├── Modules/                 # 11 Domain Modules (Academic, Finance, Sarpar, dll)
│   ├── Notifications/           # Database & Mail notifications
│   ├── Providers/               # Service Providers (App, Event, Broadcast)
│   ├── Services/                # Service layer (FcmService, NotificationDispatcher, dll)
│   └── Support/                 # Resolvers & custom extensions
├── bootstrap/                   # Laravel 12 application bootstrap (app.php, providers)
├── config/                      # Application configurations (auth, permission, reverb, pulse)
├── database/
│   ├── factories/               # Model factories for testing
│   ├── migrations/              # 82 Database migration schema files
│   └── seeders/                 # 36 Database seeders (Units, Roles, Academic, dll)
├── docs/                        # Complete Reverse-Engineered Enterprise Documentation
├── knowledge-base/              # Historical design references (Pre-audit docs)
├── public/                      # Entrypoint public web, compiled Vite assets, manifest.webmanifest
├── resources/
│   ├── css/                     # Tailwind CSS v4 stylesheets
│   └── js/
│       ├── Components/          # Shared Vue components (Modal, Dropdown, Button, Input)
│       ├── Layouts/             # App shells (AuthenticatedLayout, MobileAppShell, StudentLayout)
│       └── Pages/               # 160 Inertia Vue pages (Academic, Employee, Finance, dll)
├── routes/                      # 18 Modular route files (web, api, yayasan, employee, dll)
├── storage/                     # App cache, session, private logs, public symlink uploads
└── tests/                       # Automated test suite (Pest feature, unit & security tests)
```

---

## 4. Statistik Metrik Berkas & Aset Sistem

| Kategori Berkas | Jumlah Terhitung | Deskripsi Singkat |
|---|---|---|
| **Total Model Eloquent** | **75 Model** | 16 Model Core (`app/Models/`) + 59 Model Modul (`app/Modules/*/Models/`) |
| **Total Controller** | **95 Controller** | Controller web Inertia & controller API Sanctum |
| **Total Komponen Vue 3** | **160 Berkas** | Tersebar di 15 direktori halaman dan shared components |
| **Total Skema Migrasi DB** | **82 Berkas** | Dari inisialisasi user 2025 hingga modul ekskul 2026 |
| **Total Rute Terdaftar** | **481 Rute** | Mencakup rute Web, API, Auth, dan Dashboard (artisan route:list) |
| **Total Test Scripts** | **23 Berkas** | Pest Feature tests, Unit tests, dan Security Regression suites |
| **Total Unit Sekolah Terdata** | **9 Unit** | Yayasan, KB Kraksaan, KB Dringu, TK Kraksaan, TK Dringu, SD, SMP, Pavlov, Daycare |
