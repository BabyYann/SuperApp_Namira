# 🧠 Memory — SuperApp Namira v2
> **Dokumen Referensi Lengkap & Permanen**  
> Terakhir diperbarui: Mei 2026  
> Tujuan: Panduan konteks untuk sesi pengembangan di masa mendatang.

---

## 1. IDENTITAS PROYEK

| Atribut | Detail |
|---|---|
| **Nama Proyek** | SuperApp Namira v2 |
| **Deskripsi** | Platform SaaS manajemen sekolah terpadu untuk Yayasan Namira |
| **Lingkup** | Multi-unit (SD, SMP, SMA dalam satu yayasan) |
| **Stack** | Laravel 12, Vue 3 (Inertia.js), Tailwind CSS, MySQL |
| **Direktori** | `C:\xampp\htdocs\SuperApp_Namirav2` |
| **PHP Version** | 8.4.13 |
| **Dev Server** | `php artisan serve` + `npm run dev` |
| **URL Lokal** | `http://127.0.0.1:8000` |

---

## 2. ARSITEKTUR SISTEM

### 2.1 Stack Teknologi
```
Backend  : Laravel 12 (PHP 8.4)
Frontend : Vue 3 + Inertia.js (SPA tanpa API JSON terpisah)
Styling  : Tailwind CSS (Glassmorphism + Dark support)
Auth     : Laravel Breeze + Spatie Laravel Permission (RBAC)
DB       : MySQL (database: superapp_namira)
Storage  : Laravel Storage (public/settings, public/avatars, dll)
Icons    : Heroicons v2 (@heroicons/vue)
Alerts   : SweetAlert2 (Swal)
```

### 2.2 Struktur Modul Backend
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── ProfileController.php       # Edit profil + upload foto
│   │   ├── StudentPortalController.php # Dashboard khusus siswa
│   │   └── StudentTaskController.php   # Tugas/Produktivitas siswa
│   └── Middleware/
│       ├── CheckMaintenanceMode.php    # ⚡ Blokir semua user saat maintenance
│       ├── CheckUnitScope.php          # Scope filter per unit
│       ├── EnsureStudentAccess.php     # Guard khusus portal siswa
│       └── HandleInertiaRequests.php   # Inject shared props ke Vue
├── Modules/
│   ├── Yayasan/    # Master data yayasan & pengaturan global
│   ├── Academic/   # Akademik (kelas, guru, siswa, jadwal, dll)
│   ├── Finance/    # Keuangan (tagihan, transaksi, laporan)
│   ├── Employee/   # Kepegawaian (staf, absensi)
│   ├── Counseling/ # BK (pelanggaran, prestasi, sesi konseling)
│   ├── Sarpar/     # Sarana Prasarana (inventaris, ruangan, pinjaman)
│   └── Student/    # (Model & helper portal siswa)
```

### 2.3 Struktur Frontend (Vue)
```
resources/js/
├── Layouts/
│   └── AuthenticatedLayout.vue   # Layout utama (Sidebar + TopBar)
├── Components/
│   └── Dashboard/
│       ├── Sidebar.vue            # Navigasi kiri (dynamic per role)
│       └── TopBar.vue             # Header atas (avatar, notif, switch unit)
└── Pages/
    ├── Dashboard.vue
    ├── Yayasan/
    │   ├── Settings/Index.vue     # 🔑 Pusat Kontrol Super Admin
    │   ├── Units/...
    │   ├── Users/...
    │   └── AcademicYears/...
    ├── Academic/
    │   ├── Classrooms/...
    │   ├── Students/...
    │   ├── Teachers/...
    │   ├── Schedules/...
    │   ├── TeachingJournal/...
    │   └── StudentAttendance/...
    ├── Finance/...
    ├── Employee/...
    ├── Counseling/...
    ├── Sarpar/...
    └── Student/                   # Portal khusus siswa
```

---

## 3. SISTEM AUTENTIKASI & HAK AKSES (RBAC)

### 3.1 Roles yang Tersedia (via Spatie Permission)

#### Level Yayasan (Global)
| Role | Deskripsi |
|---|---|
| `super_admin_yayasan` | Akses penuh ke semua fitur + Pusat Kontrol |
| `admin_yayasan` | Manajemen data yayasan (tanpa Settings Global) |
| `staff_yayasan` | Operasional yayasan terbatas |

#### Level Unit (Per-Sekolah)
| Role | Deskripsi |
|---|---|
| `admin_unit` | Admin kepala sekolah |
| `staff_unit` | Staff administrasi sekolah |
| `teacher` | Guru (akses jurnal, absensi siswa, BK) |
| `koordinator_kurikulum` | Koordinator kurikulum |
| `wali_kelas` | Wali kelas |
| `bk` | Guru Bimbingan & Konseling |
| `staff_admin_keuangan` / `finance` | Staf keuangan |
| `koordinator_sarpar` | Koordinator Sarana Prasarana |
| `siswa` | Siswa (hanya akses portal siswa) |

### 3.2 Middleware Penting
| Middleware | Fungsi |
|---|---|
| `CheckMaintenanceMode` | Blokir semua user (kecuali `super_admin_yayasan`) saat maintenance |
| `CheckUnitScope` | Filter data berdasarkan `unit_id` aktif user |
| `EnsureStudentAccess` | Pastikan user ber-role `siswa` yang bisa akses portal siswa |
| `HandleInertiaRequests` | Inject data global ke props Vue (`auth.user`, `app_settings`, dll) |

### 3.3 Shared Inertia Props
Data ini otomatis tersedia di **semua** komponen Vue via `$page.props`:
```js
$page.props.auth.user           // User login saat ini (beserta roles)
$page.props.app_settings        // Pengaturan sistem global (dari cache)
$page.props.app_settings.app_name
$page.props.app_settings.app_logo
$page.props.app_settings.maintenance_mode
$page.props.app_settings.feature_sarpar  // '1' atau '0'
// ... semua feature flags
```

---

## 4. DATABASE — SKEMA LENGKAP

### 4.1 Tabel Inti (Core)
```
users             -- Semua pengguna sistem (extends Laravel default + profile_photo)
units             -- Data sekolah/unit dalam yayasan (SD Namira, SMP Namira, dll)
academic_years    -- Tahun ajaran (2024/2025, aktif = 1)
roles             -- Role Spatie Permission
model_has_roles   -- Pivot user <-> role
permissions       -- Permission Spatie
model_has_permissions
cache             -- Laravel cache (digunakan untuk system_settings)
system_settings   -- [BARU] Pengaturan global aplikasi
```

### 4.2 system_settings (Pusat Kontrol)
```sql
id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
key         VARCHAR(255) UNIQUE,   -- 'app_name', 'feature_sarpar', dll
value       TEXT,                  -- Nilai setting
type        VARCHAR(255),          -- 'text', 'image', 'boolean'
group       VARCHAR(255),          -- 'general', 'contact', 'system', 'features'
```

**Keys yang Tersedia:**
| Key | Grup | Default | Fungsi |
|---|---|---|---|
| `app_name` | general | SuperApp Namira | Nama aplikasi (tampil di sidebar) |
| `app_logo` | general | '' | Path logo (storage/settings/) |
| `app_favicon` | general | '' | Path favicon |
| `contact_email` | contact | info@namira.school | Email resmi |
| `contact_phone` | contact | +62 811-... | Telepon resmi |
| `address` | contact | Jl. Pendidikan... | Alamat |
| `maintenance_mode` | system | 0 | ON/OFF maintenance |
| `maintenance_message` | system | Sistem sedang... | Pesan maintenance |
| `feature_finance` | features | 1 | ON/OFF Modul Keuangan |
| `feature_sarpar` | features | 1 | ON/OFF Modul Sarpar |
| `feature_counseling` | features | 1 | ON/OFF Modul BK |
| `feature_academic` | features | 1 | ON/OFF Modul Akademik |
| `feature_employee` | features | 1 | ON/OFF Modul Kepegawaian |
| `feature_student_login` | features | 1 | ON/OFF Login Siswa |

### 4.3 Modul Akademik
```
teachers          -- Data guru (user_id, unit_id, nip, full_name, gender, photo)
classrooms        -- Kelas (unit_id, academic_year_id, name, level, homeroom_teacher_id)
students          -- Siswa (user_id, unit_id, classroom_id, nis, nisn, va_number, photo)
subjects          -- Mata pelajaran
chapters          -- Bab/tema per mapel & level
learning_objectives -- Tujuan Pembelajaran (TP) per bab
class_schedules   -- Jadwal pelajaran per kelas
teaching_journals -- Jurnal mengajar harian guru
student_attendances -- Presensi harian siswa per kelas
class_promotions  -- Riwayat kenaikan kelas
student_tasks     -- Tugas produktivitas siswa (portal siswa)
```

**Relasi Kritis Akademik:**
```
Unit        1---n  Classroom (per tahun ajaran)
Classroom   n---n  Student   (via classroom_id di students)
Teacher     1---1  Classroom (homeroom_teacher_id, wali kelas)
Teacher     1---n  TeachingJournal
Teacher     1---n  ClassSchedule
Subject     1---n  Chapter
Chapter     1---n  LearningObjective
```

### 4.4 Modul Keuangan
```
finance_types      -- Jenis tagihan (SPP, Gedung, dll) per unit
finance_accounts   -- Rekening bank yayasan
student_discounts  -- Beasiswa/potongan khusus per siswa
student_bills      -- Tagihan per siswa (bill_code INV/xxx)
transactions       -- Pembayaran masuk (transaction_code TRX/xxx)
bill_payments      -- PIVOT: transaksi melunasi tagihan mana saja
```

**Alur Keuangan:**
```
FinanceType --> StudentBill --> BillPayment <-- Transaction <-- Student (via VA)
```
- Transaksi bisa diimport dari file bank (Bank Jatim)
- Satu transaksi bisa melunasi beberapa tagihan sekaligus
- Sisa dana tersimpan sebagai `excess_amount` (deposit)

### 4.5 Modul Kepegawaian
```
staff                  -- Data staf (user_id, unit_id, nip, jabatan, foto)
employee_attendances   -- Presensi harian staf (GPS check-in/check-out)
attendance_locations   -- Titik koordinat GPS yang valid per unit
holidays               -- Libur nasional & sekolah (kalender akademik)
```

### 4.6 Modul BK (Konseling)
```
violation_categories  -- Kategori pelanggaran (ringan/sedang/berat + poin default)
violations            -- Catatan pelanggaran siswa (dengan poin, bukti foto)
achievements          -- Catatan prestasi siswa (level: sekolah s.d. internasional)
counseling_sessions   -- Sesi konseling privat siswa dengan guru BK
```

### 4.7 Modul Sarana Prasarana
```
sarpar_categories     -- Kategori inventaris
sarpar_rooms          -- Data ruangan sekolah
sarpar_inventories    -- Daftar inventaris (aset/consumable)
sarpar_maintenance_logs -- Log kerusakan & perbaikan
sarpar_loans          -- Peminjaman barang
sarpar_usage_logs     -- Log pemakaian barang habis pakai
```

---

## 5. ROUTING — PETA URL LENGKAP

### 5.1 Modul Yayasan (/yayasan/...)
| URL | Role | Fungsi |
|---|---|---|
| /yayasan/dashboard | Semua staff | Dashboard utama |
| /yayasan/monitoring | Admin+ | Monitoring seluruh unit |
| /yayasan/units | Admin+ | CRUD unit/sekolah |
| /yayasan/academic-years | Admin+ | CRUD tahun ajaran |
| /yayasan/users | Admin+ | Manajemen pengguna global |
| /yayasan/attendance-locations | Admin+ | Titik GPS presensi |
| **/yayasan/settings** | **super_admin_yayasan** | **Pusat Kontrol** |
| /yayasan/holidays | Admin+ | Kalender libur |
| /yayasan/attendance-approvals | Admin+ | Persetujuan presensi |
| /yayasan/attendance-data | Admin+ | Data presensi pegawai |

### 5.2 Modul Akademik (/yayasan/...)
| URL | Fungsi |
|---|---|
| /yayasan/classrooms | CRUD kelas |
| /yayasan/students | CRUD siswa + import Excel/VA |
| /yayasan/teachers | CRUD guru |
| /yayasan/subjects | CRUD mata pelajaran |
| /yayasan/schedules | Jadwal pelajaran + clone + export PDF |
| /yayasan/teaching-journal | Jurnal mengajar harian |
| /yayasan/student-attendance | Presensi harian siswa |
| /yayasan/learning-objectives | Tujuan Pembelajaran (TP) |
| /yayasan/promotion | Kenaikan kelas (preview + eksekusi) |

### 5.3 Modul Keuangan (/yayasan/finance/...)
| URL | Fungsi |
|---|---|
| /yayasan/finance | Dashboard keuangan |
| /yayasan/finance/types | Jenis tagihan |
| /yayasan/finance/accounts | Rekening bank |
| /yayasan/finance/bills | Tagihan siswa |
| /yayasan/finance/transactions | Transaksi + import bank |
| /yayasan/finance/reports/arrears | Laporan tunggakan |

### 5.4 Modul Kepegawaian & Lainnya
| URL | Fungsi |
|---|---|
| /employee/attendance | Check-in / Check-out GPS |
| /yayasan/staff | CRUD data staf |
| /counseling/violations | Catat pelanggaran |
| /counseling/achievements | Catat prestasi |
| /counseling/sessions | Sesi konseling |
| /sarpar | Dashboard Sarpar |
| /sarpar/inventories | Inventaris + export |
| /sarpar/maintenance | Log kerusakan |
| /sarpar/loans | Peminjaman barang |

### 5.5 Portal Siswa (/student/...)
| URL | Fungsi |
|---|---|
| /student/dashboard | Dashboard siswa |
| /student/academic | Info akademik |
| /student/finance | Info tagihan |
| /student/counseling | Info BK |
| /student/productivity | Manajemen tugas to-do |

---

## 6. PUSAT KONTROL — DETAIL TEKNIS

- **URL:** `/yayasan/settings`
- **Route Name:** `yayasan.settings.index`
- **Controller:** `App\Modules\Yayasan\Controllers\SettingController`
- **View:** `resources/js/Pages/Yayasan/Settings/Index.vue`
- **Akses:** Hanya `super_admin_yayasan`
- **Sidebar:** Tombol ikon gear di bagian bawah sidebar (hanya untuk super admin)

### Cara Kerja Cache Settings
```php
// HandleInertiaRequests.php
$settings = Cache::rememberForever('system_settings', function () {
    return SystemSetting::all()->pluck('value', 'key')->toArray();
});

// Saat ada perubahan:
Cache::forget('system_settings');
```

### Mode Maintenance
- Middleware `CheckMaintenanceMode` dijalankan di setiap request web
- Jika `maintenance_mode = '1'` dan user bukan `super_admin_yayasan`, render halaman maintenance
- Super Admin selalu bisa masuk normal walau maintenance aktif

---

## 7. INTEGRASI ANTAR MODUL

```
Yayasan (Master Data)
  |-- Units: filter data semua modul berdasarkan unit_id
  |-- AcademicYears: basis classrooms, schedules
  |-- Users: basis semua user
  |-- SystemSettings: kontrol global (logo, maintenance, feature flags)
  |-- Holidays: kalender akademik, pengaruhi jadwal

Academic
  |-- Teachers: TeachingJournals, Schedules
  |-- Classrooms: Students, StudentAttendance, Schedules
  |-- Students: StudentBills(Finance), Violations(BK), Portal Siswa
  |-- Subjects: Chapters -> LearningObjectives -> TeachingJournal
  |-- ClassPromotion: update classroom_id di students

Finance
  |-- Student (via VA Number): auto-mapping transaksi bank
  |-- FinanceType -> StudentBill <- BillPayment <- Transaction

Employee
  |-- Staff/Teacher: EmployeeAttendance (GPS)
  |-- AttendanceLocation: validasi radius GPS

Counseling
  |-- Student: Violations, Achievements, CounselingSessions

Sarpar
  |-- Room: lokasi inventaris
  |-- Inventory: MaintenanceLogs, Loans, UsageLogs

Student Portal
  |-- Student: data dari Academic, Finance, Counseling
  |-- StudentTask: to-do pribadi siswa
```

---

## 8. CATATAN PENTING & JEBAKAN

### Unit Scope (Kritis!)
- Semua query data wajib difilter per `unit_id`
- Middleware `CheckUnitScope` mengelola ini via session `active_unit_id`
- Super Admin bisa switch antar unit via tombol di TopBar

### Storage Link
Wajib dijalankan sekali setelah install agar upload file bisa diakses:
```bash
php artisan storage:link
```

### Cache Settings
Jika perubahan settings tidak terlihat:
```bash
php artisan cache:clear
```

### Seeder Yang Harus Dijalankan
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=SystemSettingSeeder
```

### Fitur Yang Direncanakan (Belum Dibangun)
- CBT (Computer Based Test / Ujian Online)
- Rapor Digital
- PPDB (Pendaftaran Peserta Didik Baru)
- Penggajian (Payroll)
- Notifikasi Real-time (WebSocket/Pusher)

---

## 9. PERINTAH UMUM

```bash
# Development
php artisan serve
npm run dev

# Database
php artisan migrate
php artisan migrate:fresh --seed   # HATI-HATI: hapus semua data!
php artisan db:seed --class=SystemSettingSeeder

# Cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Storage
php artisan storage:link

# Lihat route
php artisan route:list --path=yayasan
```

---

*Dokumen ini dibuat dari analisis kode sumber lengkap. Perbarui setiap kali ada perubahan arsitektur besar.*
