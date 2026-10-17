# SuperApp Namira (v2 Enterprise)

[![Laravel 12](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue 3.5](https://img.shields.io/badge/Vue.js-3.5-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-3.x-9553E9?style=for-the-badge&logo=inertia&logoColor=white)](https://inertiajs.com)
[![Tailwind CSS 4](https://img.shields.io/badge/Tailwind_CSS-4.x-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Vite 8](https://img.shields.io/badge/Vite-8.x-646CFF?style=for-the-badge&logo=vite&logoColor=white)](https://vitejs.dev)
[![PWA Ready](https://img.shields.io/badge/PWA-Enabled-5A0FC8?style=for-the-badge&logo=pwa&logoColor=white)](https://web.dev/progressive-web-apps/)

**SuperApp Namira** adalah platform terpadu sistem informasi manajemen pendidikan multi-unit terintegrasi di bawah naungan **Yayasan Pendidikan Fajar Diinul Islam**. Sistem ini mengintegrasikan seluruh jenjang pendidikan mulai dari Daycare, TK/PAUD, SD, SMP, hingga SMA ke dalam satu basis data bersama (*shared-database multi-tenant architecture*).

---

## 🏛️ 11 Modul Operasional Terintegrasi

1. **Pondasi Yayasan & Multi-Tenancy Core**: Tata kelola unit sekolah, konfigurasi pimpinan, master tahun akademik, dan pengawasan metrik konsolidasi eksekutif.
2. **Akademik & Kurikulum**: Manajemen rombel, dewan guru, master santri, jadwal pelajaran kelas mandiri/gabungan, capaian pembelajaran (CP/TP), jurnal mengajar KBM, dan kenaikan kelas dengan fitur rollback aman.
3. **Kepegawaian & Presensi Karyawan**: Direktori staf, presensi kehadiran dinas berbasis radius GPS (geofencing) dengan swafoto, dan kalkulasi otomatis menit keterlambatan.
4. **Keuangan & Pembukuan Kas (Finance)**: Master pos tarif biaya (SPP/DPP), penerbitan tagihan massal, diskon/beasiswa santri, konfirmasi bukti transfer online wali murid, dan pencatatan buku kas masuk/keluar.
5. **Bimbingan Konseling (BK) & Kesiswaan**: Buku pelanggaran tata tertib berbasis poin sanksi, pencatatan prestasi dan piagam juara santri, serta notula rahasia sesi konseling.
6. **Sarana & Prasarana (Sarpras / Asset Management)**: Inventarisasi aset fisik dengan kodifikasi barcode, master ruangan, peminjaman barang, buku servis pemeliharaan, mutasi antar-gedung, dan berita acara penghapusan aset afkir.
7. **E-Learning (LMS)**: Ruang kelas digital, materi ajar modul berkas multimedia, penugasan berbatas waktu (*due date*), submisi lembar kerja santri, penilaian guru (*gradebook*), dan pengumuman forum kelas.
8. **Keamanan Gerbang & Notifikasi (Gate Scanner)**: Pemindaian barcode kartu santri di pos satpam, siaran status kedatangan real-time monitor via Laravel Reverb (WebSocket), dan otomatisasi pengiriman pesan WhatsApp kepada orang tua.
9. **Manajemen Daycare (Penitipan Anak)**: Profil anak asuh, otorisasi penjemput sah dengan verifikasi foto identitas, absensi harian, log aktivitas (makan, tidur, popok, mood), dan grafik tumbuh kembang antropometri.
10. **SPMB Online (Penerimaan Siswa Baru)**: Konfigurasi gelombang pendaftaran mandiri per unit, formulir calon santri online, seleksi berkas, dan penerbitan SK kelulusan.
11. **Klub Ekstrakurikuler**: Pendaftaran klub bakat/minat, penugasan pelatih, presensi latihan mingguan, dan penilaian predikat rapor siswa.

---

## 🚀 Panduan Ringkas Memulai Proyek (Quick Start)

### Prasyarat:
- PHP >= 8.2 (dengan ekstensi `pdo_mysql`, `mbstring`, `bcmath`, `gd`, `zip`)
- Node.js >= 20.x & NPM >= 10.x
- Composer >= 2.6
- MySQL 8.0+

### Instalasi Langkah-demi-Langkah:
```bash
# 1. Kloning repositori
git clone https://github.com/namira/superapp-namira.git
cd superapp-namira

# 2. Instalasi dependensi backend
composer install

# 3. Konfigurasi berkas lingkungan
cp .env.example .env
php artisan key:generate

# 4. Migrasi basis data & seeder awal
php artisan migrate --seed

# 5. Buat tautan media storage publik
php artisan storage:link

# 6. Instalasi dependensi frontend & jalankan Vite dev server
npm install
npm run dev

# 7. Di terminal terpisah, jalankan server Laravel & background worker
php artisan serve
php artisan queue:listen
php artisan reverb:start
```

---

## 📚 Dokumentasi Arsitektur & Operasional Lengkap

Seluruh spesifikasi teknis dan panduan operasional tersedia secara lengkap di dalam direktori [`docs/`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/README.md):

| Kategori Dokumentasi | Tautan Berkas Induk | Isi & Ruang Lingkup |
| :--- | :--- | :--- |
| **00. Governance** | [`docs/00-governance/`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/00-governance/audit-scope.md) | Metodologi audit, register bukti `EV-*`, dan standar dokumentasi. |
| **01. Product Requirements** | [`docs/01-product/`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/01-product/01-prd-as-is.md) | PRD As-Is & To-Be, User Personas, User Stories, dan 44 FR. |
| **02. Inventories** | [`docs/02-inventory/`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/02-inventory/project-inventory.md) | Inventaris 11 modul, 481 rute, 75 model, 95 controller, 160 Vue pages. |
| **03. Architecture** | [`docs/03-architecture/`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/03-architecture/01-system-context.md) | C4 Context/Container, Dual-Shell strategy, backend service, runtime. |
| **04. Database** | [`docs/04-database/`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/04-database/01-database-overview.md) | ERD 11 domain, data dictionary 74 tabel, relasi ORM, 82 migrasi. |
| **05. Business Flows** | [`docs/05-business-flows/`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/05-business-flows/01-business-process-catalog.md) | 12 Proses bisnis utama, user journeys, sequence diagrams, state machines. |
| **06. Access Control** | [`docs/06-access-control/`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/06-access-control/01-role-permission-matrix.md) | Matriks CRUD 20 peran, pipeline otorisasi, dan isolasi Spatie Teams. |
| **07. REST API** | [`docs/07-api/`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/07-api/01-endpoint-catalog.md) | 72 Endpoint Sanctum, kontrak request/response JSON envelope. |
| **08. Quality & Testing** | [`docs/08-quality/`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/08-quality/01-test-inventory.md) | 21 Berkas uji Pest/PHPUnit, analisis cakupan uji, traceability matrix. |
| **09. Security** | [`docs/09-security/`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/09-security/01-threat-model.md) | STRIDE threat model, arsitektur defense-in-depth, audit OWASP Top 10. |
| **10. Operations** | [`docs/10-operations/`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/10-operations/01-prerequisites-and-setup.md) | SOP deployment VPS Ubuntu, SOP cPanel, Supervisor, backup & recovery. |
| **11. Validation** | [`docs/11-validation/`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/11-validation/06-final-audit-report.md) | Berita acara final sign-off, audit cakupan, audit 80 diagram Mermaid. |

---

## 🛡️ Standar Keamanan & Perlindungan Data

Aplikasi ini mengimplementasikan prinsip *Defense-in-Depth*:
- **Penyekatan Multi-Tenant Ketat**: Kolom `team_id` pada Spatie Permission menjamin staf satu unit tidak dapat membaca atau memanipulasi data santri di unit lain.
- **Kriptografi Standar Industri**: Password di-hash menggunakan `bcrypt` cost factor 12; sesi dilindungi cookie `HttpOnly`, `SameSite=Lax`, dan `Secure`.
- **Proteksi Celah Web**: 100% kueri database menggunakan PDO Parameter Binding (Anti-SQLi); form dilindungi validasi CSRF otomatis; dan masukan dikunci via whitelist `$fillable`.

---

## 📜 Lisensi & Kerangka Kerja

Aplikasi ini dikembangkan di atas fondasi [Laravel Framework](https://laravel.com) yang dilisensikan di bawah [MIT license](https://opensource.org/licenses/MIT).
Seluruh hak cipta modul bisnis dan identitas visual berada di bawah naungan **Yayasan Pendidikan Fajar Diinul Islam (Namira Islamic School)**.
