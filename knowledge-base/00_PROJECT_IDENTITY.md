# 00_PROJECT_IDENTITY

## 1. Executive Summary
**Confidence Level: High**
(Berdasarkan konfigurasi `.env` dan `composer.json`)

Sistem ini adalah sebuah aplikasi web ("SuperApp") yang dibangun menggunakan framework Laravel dan Vue.js. Berdasarkan nama database (`superapp_namira`) dan konfigurasi email (`Namira School Foundation`), sistem ini ditujukan untuk memusatkan operasional dan manajemen pendidikan pada Namira School Foundation. Aplikasi ini menggunakan arsitektur Single Page Application (SPA) melalui Inertia.js dan memiliki sistem Role-Based Access Control (RBAC) yang komprehensif.

---

## 2. Project Identity
**Confidence Level: Medium**
(Beberapa informasi didapat dari konfigurasi teknis, sisanya belum dapat diverifikasi secara eksplisit)

* **Project Name:** SuperApp Namira v2
* **System Name:** SuperApp Namira
* **Description:** Platform terpusat untuk manajemen dan operasional Namira School Foundation.
* **Vision:** Unknown (Need Further Inspection)
* **Mission:** Unknown (Need Further Inspection)
* **Primary Objectives:** Unknown (Need Further Inspection)
* **Current Development Status:** In Development / Upgrade (terlihat dari adanya skrip debug di root folder seperti `debug_roles.php` dan `fix_roles.php`).

---

## 3. Business Overview
**Confidence Level: Medium**
(Berdasarkan penamaan pada environment variable)

* **Business Domain:** Education Management / School Administration
* **Problem Statement:** Unknown (Need Further Inspection)
* **Target Users:** Unknown (Need Further Inspection)
* **Organization Type:** Educational Institution (School Foundation)
* **Core Business Goals:** Unknown (Need Further Inspection)

---

## 4. Stakeholders
**Confidence Level: Unknown**
(Belum ada data eksplisit mengenai daftar pengguna dalam ruang lingkup Stage 01. Terdapat indikasi adanya sistem *Role* dari file `fix_roles.php`, namun detail peran belum dianalisis)

* Unknown (Need Further Inspection)

---

## 5. Technology Overview
**Confidence Level: High**
(Diverifikasi dari `composer.json`, `package.json`, dan `.env`)

* **Backend:** PHP 8.2+, Laravel Framework 12.0
* **Frontend:** Vue.js 3, Inertia.js, TailwindCSS 4, Vite
* **Database:** MySQL
* **Authentication:** Laravel Sanctum
* **Authorization:** Spatie Laravel Permission
* **Queue:** Database
* **Cache:** Database
* **Storage:** Local
* **Build Tools:** Vite, npm, Composer
* **Deployment:** Unknown (Need Further Inspection)
* **Version Control:** Git
* **Testing Framework:** Pest, PHPUnit

---

## 6. High-Level Project Scope
**Confidence Level: High**
(Berdasarkan dependensi teknis)

Sistem merupakan aplikasi full-stack yang mendukung manajemen pengguna dengan hak akses yang kompleks (RBAC). Ruang lingkup teknisnya mencakup antarmuka Single Page Application (SPA) yang reaktif, kemampuan untuk menghasilkan laporan dokumen (PDF), visualisasi data (Chart.js), pemetaan (Leaflet), serta pencatatan jejak audit aktivitas (Activity Log).

---

## 7. Initial Folder Overview
**Confidence Level: High**
(Diverifikasi dari observasi `c:\xampp\htdocs\SuperApp_Namirav2`)

* `app/`: Logika inti aplikasi (Controllers, Models, Services, dll).
* `bootstrap/`: Konfigurasi bootstrap framework dan cache.
* `config/`: File konfigurasi aplikasi.
* `database/`: Migrations, seeders, dan factories untuk struktur database.
* `public/`: Akses publik dan aset yang telah dikompilasi.
* `resources/`: Aset mentah (Vue components, CSS, file JS).
* `routes/`: Definisi routing aplikasi (web, api).
* `storage/`: Penyimpanan lokal, log, cache aplikasi, dan session.
* `tests/`: Skrip pengujian otomatis (Pest/PHPUnit).
* `vendor/` & `node_modules/`: Folder dependensi backend dan frontend.

---

## 8. External Dependencies
**Confidence Level: High**
(Diverifikasi dari `composer.json` dan `package.json`)

**Backend Utama:**
* `barryvdh/laravel-dompdf`: Pembuatan dokumen PDF.
* `inertiajs/inertia-laravel`: Integrasi backend untuk SPA.
* `laravel/sanctum`: Autentikasi API.
* `spatie/laravel-activitylog`: Pencatatan log aktivitas pengguna.
* `spatie/laravel-permission`: Manajemen peran dan izin (RBAC).
* `tightenco/ziggy`: Penggunaan route Laravel di sisi frontend (JavaScript).

**Frontend Utama:**
* `@inertiajs/vue3` & `vue`: Framework antarmuka SPA.
* `tailwindcss`: Framework styling/CSS.
* `chart.js` & `vue-chartjs`: Visualisasi grafik dan data.
* `sweetalert2`: Komponen notifikasi pop-up.
* `leaflet`: Komponen pemetaan interaktif.
* `@vueup/vue-quill`: Rich text editor.

---

## 9. Existing Documentation Inventory
**Confidence Level: High**
(Berdasarkan file yang ditemukan di direktori utama)

* `README.md` (Dokumentasi bawaan Laravel)
* `ADR.md` (Architecture Decision Records)
* `PROJECT_MEMORY.md`
* `memory.md`
* `audit_review.md`
* `structure.txt` & `structure_utf8.txt`

*(Catatan: Sesuai aturan, isi dari dokumen-dokumen ini tidak digunakan sebagai dasar analisis pada tahap ini.)*

---

## 10. Known Facts
**Confidence Level: High**

* Aplikasi dibangun di atas framework Laravel versi 12 dengan frontend Vue 3.
* Proyek ini ditujukan untuk **Namira School Foundation**.
* Terdapat skrip kustom di *root* direktori (`debug_roles.php`, `fix_roles.php`, `debug_classroom_data.php`) yang mengindikasikan adanya perbaikan atau pengembangan aktif terkait manajemen pengguna/kelas.
* Basis data menggunakan MySQL dan menerapkan sistem *Role-Based Access Control* (Spatie).

---

## 11. Unknown Facts
**Confidence Level: High**

* Alur bisnis (Business Flow) dan modul spesifik yang tersedia.
* Daftar lengkap peran (*roles*) dan *stakeholders* dalam yayasan.
* Arsitektur *deployment* dan spesifikasi server produksi.
* Detail relasi database (akan dianalisis pada tahap Database Mapping).

---

## 12. Risks & Observation
**Confidence Level: Medium**

* **Risiko Dokumen Usang:** Terdapat banyak file dokumentasi internal (`memory.md`, `audit_review.md`, dll) yang mungkin sudah tidak sinkron dengan *source code* saat ini. Mengacu pada dokumentasi ini tanpa verifikasi kode dapat menyesatkan.
* **Observasi Pengembangan:** Keberadaan file *script* PHP mentah di luar direktori `app` atau `tests` (seperti `fix_roles.php`) mengindikasikan adanya penanganan *hotfix* atau proses pengujian *ad-hoc*. File-file ini berpotensi tertinggal dan menjadi *technical debt*.

---

## 13. Readiness Assessment
**Confidence Level: High**

**Kesimpulan:** Proyek ini SIAP untuk memasuki **Stage 02 — Project Discovery & Coverage Audit**.
**Alasan:** Identitas dasar teknologi, tujuan sistem secara garis besar, dan struktur direktori telah dapat dipetakan secara jelas melalui konfigurasi bawaan. Karena aplikasi mengikuti struktur standar Laravel yang kuat, audit lanjutan ke tingkat modul, *routing*, dan database dapat dilakukan tanpa kendala.
