# 02_ARCHITECTURE

## 1. Architecture Style
**Confidence Level: High**
(Berdasarkan pemetaan folder dan penelusuran *source code*)

*   **Modular Monolith:**
    *   *Status:* Active (Partial)
    *   *Evidence:* Kode program diorganisasikan ke dalam modul domain terpisah di bawah direktori `app/Modules/` (seperti Academic, Counseling, Finance, LMS, dll.) dan `resources/js/Pages/` yang merefleksikan struktur yang sama. Namun, routing dan registrasi service provider masih terpusat secara global.
    *   *Confidence:* High.
*   **Monolith:**
    *   *Status:* Active
    *   *Evidence:* Semua kode backend, frontend, konfigurasi, dan migrasi database berada dalam satu repositori tunggal yang berjalan pada satu server/aplikasi terpadu.
    *   *Confidence:* High.
*   **MVC (Model-View-Controller):**
    *   *Status:* Active
    *   *Evidence:* Menggunakan controller Laravel untuk memproses input HTTP, memanggil model Eloquent untuk interaksi database, dan mengembalikan render tampilan SPA (Vue) melalui Inertia.js.
    *   *Confidence:* High.
*   **Service Layer & Repository Pattern:**
    *   *Status:* Not Active / Not Found
    *   *Evidence:* Hanya ditemukan satu kelas generator kode inventaris di dalam direktori modul Sarpar (`InventoryCodeGenerator.php`). Tidak ditemukan folder atau kelas global untuk Service Layer maupun Repository.
    *   *Confidence:* High.
*   **Domain Driven Design (DDD) & Event-Driven:**
    *   *Status:* Not Active / Not Found
    *   *Evidence:* Pembagian folder modular didasarkan pada lingkup bisnis, tetapi tidak menerapkan taksonomi DDD (seperti Aggregates, Value Objects, Domain Events) atau arsitektur Event-Driven (tidak ada file Event/Listener).
    *   *Confidence:* High.

---

## 2. System Layers
**Confidence Level: High**
(Berdasarkan penelusuran alur pemrosesan request)

Sistem ini menerapkan pembagian layer sebagai berikut:

```
Presentation Layer (Vue 3, Inertia.js, TailwindCSS 4)
                      ↓
Routing Layer (routes/web.php, routes/auth.php)
                      ↓
Middleware Layer (CheckUnitScope, HandleInertiaRequests, Spatie)
                      ↓
Controller Layer (app/Modules/<ModuleName>/Controllers/)
                      ↓
Model Layer (app/Modules/<ModuleName>/Models/ & app/Models/)
                      ↓
Database Layer (MySQL & Migrations)
```

*   **Presentation Layer (Frontend):** Menggunakan Vue 3 Single File Components (SFC) dengan integrasi Inertia.js untuk mengelola state secara reaktif tanpa API endpoint terpisah.
*   **Routing Layer:** Menerima request HTTP dari browser dan mencocokkannya dengan rute terdaftar. Seluruh rute terpusat di direktori `routes/`.
*   **Middleware Layer:** Menangani autentikasi, otorisasi RBAC (Spatie), verifikasi multi-tenancy unit sekolah (`CheckUnitScope`), dan penanganan mode pemeliharaan (`CheckMaintenanceMode`).
*   **Controller Layer:** Mengatur alur logika HTTP. Controller berada di dalam modul-modul bisnis di bawah `app/Modules/<ModuleName>/Controllers/`.
*   **Model Layer:** Mengelola interaksi basis data dengan Eloquent ORM. Model diletakkan di dalam modul terkait (`app/Modules/<ModuleName>/Models/`) atau model inti bersama di `app/Models/`.
*   **Database Layer:** Penyimpanan fisik menggunakan MySQL dengan skema yang dikelola oleh migrasi Laravel.

---

## 3. Bootstrap Flow
**Confidence Level: High**
(Diverifikasi dari `public/index.php`, `bootstrap/app.php`, dan `bootstrap/providers.php`)

1.  **Entry Point (`public/index.php`):** Browser mengirimkan request yang ditangkap oleh `public/index.php`. File ini memuat autoloader Composer (`vendor/autoload.php`) dan mengambil instansi aplikasi Laravel dari `bootstrap/app.php`.
2.  **App Configuration (`bootstrap/app.php`):** Di sini Laravel 12 dikonfigurasi menggunakan antarmuka fluent:
    *   **Routing:** Menentukan file rute web (`routes/web.php`) dan perintah konsol (`routes/console.php`).
    *   **Middleware:** Menambahkan middleware kustom (`CheckUnitScope`, `HandleInertiaRequests`, `CheckMaintenanceMode`) ke dalam grup `web` dan mendaftarkan alias middleware (seperti `role`, `permission` milik Spatie, serta `feature`).
    *   **Exceptions:** Mendaftarkan penanganan exception kustom untuk `PostTooLargeException`.
3.  **Service Provider Bootstrapping:** Laravel memuat provider yang terdaftar di `bootstrap/providers.php` (`App\Providers\AppServiceProvider`). Pada `AppServiceProvider.php`, dilakukan konfigurasi prefetch aset Vite dan pendefinisian Gate otorisasi untuk Laravel Pulse (`viewPulse`).

---

## 4. Request Lifecycle
**Confidence Level: High**
(Diverifikasi dari alur request aplikasi Laravel-Inertia)

```
[Browser (Inertia Request)]
            ↓
    [public/index.php]
            ↓
  [bootstrap/app.php (App Setup)]
            ↓
   [routes/web.php (Matching)]
            ↓
 [Middleware (Auth/Role/Unit Scope)]
            ↓
[Controller (Module Controller Action)]
            ↓
    [Model (Eloquent ORM)]
            ↓
       [Database]
            ↓
[Inertia Response (HTML/JSON Payload)]
```

*   **Inisiasi:** Request dikirimkan melalui navigasi Inertia (atau request HTTP normal).
*   **Autoload & Boot:** `public/index.php` memproses boot awal melalui autoloader.
*   **Routing & Middleware:** Router mencocokkan URI. Rute yang cocok memicu middleware. Middleware memvalidasi sesi pengguna, hak akses peran (*roles*), dan ruang lingkup unit sekolah yang aktif (`CheckUnitScope`).
*   **Eksekusi Controller:** Jika valid, request diteruskan ke method Controller yang bersangkutan di dalam folder modul bisnis.
*   **Akses Data:** Controller memanggil Model Eloquent untuk mengambil atau memanipulasi data di database MySQL.
*   **Response Render:** Controller mengembalikan `Inertia::render()`. Jika request berupa navigasi Inertia, sistem mengembalikan response JSON yang berisi komponen Vue dan props data baru. Jika request berupa hit langsung, server merender Blade template (`resources/views/app.blade.php`) dengan payload awal.

---

## 5. Module Architecture
**Confidence Level: High**
(Berdasarkan pemetaan struktur direktori modul bisnis)

### 1. Academic
*   **Responsibility:** Mengelola struktur kelas, guru, mata pelajaran, jadwal, jurnal mengajar, kehadiran siswa, dan proses kenaikan kelas.
*   **Main Components:**
    *   *Controllers:* ClassroomController, ClassPromotionController, LearningObjectiveController, ScheduleController, StudentAttendanceController, StudentController, SubjectController, TeacherController, TeachingJournalController.
    *   *Models:* Chapter, ClassPromotion, Classroom, ClassSchedule, JournalAttendance, LearningObjective, Student, Subject, Teacher, TeachingJournal.
    *   *Pages/Views:* `resources/js/Pages/Academic/`

### 2. Counseling
*   **Responsibility:** Pencatatan konseling siswa, prestasi penghargaan, dan pencatatan pelanggaran siswa.
*   **Main Components:**
    *   *Controllers:* AchievementController, CounselingSessionController, ViolationCategoryController, ViolationController.
    *   *Models:* Achievement, CounselingSession, Violation, ViolationCategory.
    *   *Pages/Views:* `resources/js/Pages/Counseling/`

### 3. Employee
*   **Responsibility:** Pengelolaan data karyawan (staf) serta absensi/kehadiran karyawan.
*   **Main Components:**
    *   *Controllers:* AttendanceController, StaffController.
    *   *Models:* Staff.
    *   *Pages/Views:* `resources/js/Pages/Employee/`

### 4. Finance
*   **Responsibility:** Pengelolaan akun keuangan, pembuatan tagihan siswa, laporan tunggakan, impor transaksi bank, dan dashboard keuangan.
*   **Main Components:**
    *   *Controllers:* FinanceAccountController, FinanceDashboardController, FinanceReportController, FinanceTypeController, StudentBillController, TransactionController.
    *   *Models:* BillPayment, FinanceAccount, FinanceType, StudentBill, StudentDiscount, Transaction.
    *   *Pages/Views:* `resources/js/Pages/Finance/`

### 5. LMS
*   **Responsibility:** Platform pembelajaran daring yang memfasilitasi guru (kelola tugas, materi, penilaian) dan siswa (unggah tugas, lihat nilai).
*   **Main Components:**
    *   *Controllers:* Guru/LmsClassroomController, Siswa/LmsStudentController.
    *   *Models:* LmsAnnouncement, LmsAssignment, LmsClassroom, LmsMaterial, LmsMaterialFile, LmsSubmission, LmsSubmissionFile.
    *   *Pages/Views:* `resources/js/Pages/LMS/`

### 6. PublicRelations (Humas)
*   **Responsibility:** Manajemen postingan berita sekolah, publikasi agenda acara, dan kerja sama kemitraan.
*   **Main Components:**
    *   *Controllers:* EventController, NewsController, PartnerController.
    *   *Models:* Menggunakan core shared models (`News`, `Event`, `Partner` di `app/Models`).
    *   *Pages/Views:* `resources/js/Pages/PublicRelations/`

### 7. Sarpar (Sarana & Prasarana)
*   **Responsibility:** Pengelolaan aset sekolah, inventaris barang, ruang kelas/lab, log peminjaman barang, serta log perawatan/pemeliharaan sarana.
*   **Main Components:**
    *   *Controllers:* CategoryController, DashboardController, InventoryController, LoanController, MaintenanceController, RoomController, UsageLogController.
    *   *Models:* Category, Inventory, Loan, MaintenanceLog, Room, UsageLog.
    *   *Services:* `InventoryCodeGenerator.php`
    *   *Pages/Views:* `resources/js/Pages/Sarpar/`

### 8. Student
*   **Responsibility:** Portal dashboard khusus bagi siswa untuk melihat akademis, keuangan, bimbingan konseling, dan tugas mandiri.
*   **Main Components:**
    *   *Controllers:* Menggunakan core controllers (`StudentPortalController` dan `StudentTaskController`).
    *   *Models:* Tidak memiliki model internal (membaca data dari modul Academic).
    *   *Pages/Views:* `resources/js/Pages/Student/`

### 9. Yayasan
*   **Responsibility:** Konfigurasi tingkat yayasan meliputi unit sekolah, tahun ajaran aktif, data pengguna global, hari libur kalender, lokasi absensi GPS, dan monitoring sistem.
*   **Main Components:**
    *   *Controllers:* AcademicYearController, AttendanceApprovalController, AttendanceDataController, AttendanceLocationController, HolidayController, MonitoringController, SettingController, UnitController, UserController.
    *   *Models:* AcademicYear, Holiday, SystemSetting, Unit.
    *   *Pages/Views:* `resources/js/Pages/Yayasan/`

---

## 6. Shared Components
**Confidence Level: High**
(Diverifikasi dari dependensi impor di backend dan frontend)

*   **Shared Models (Core):** `User`, `Event`, `News`, `Partner`, `AttendanceLocation`, `StudentAttendance`, `EmployeeAttendance`, `StudentTask` (diletakkan di `app/Models` dan diimpor oleh berbagai modul bisnis).
*   **Shared Authentication & Authorization:** RBAC berbasis Spatie Permission. Hak akses tingkat tinggi (`super_admin_yayasan`, `admin_yayasan`, dll.) dibagikan secara global ke semua rute modul.
*   **Shared Helpers:** `WhatsAppHelper.php` di `app/Helpers` untuk mengirimkan notifikasi WA dari modul-modul terkait.
*   **Shared Layouts:** `AuthenticatedLayout.vue` (dashboard umum staf/guru), `StudentLayout.vue` (dashboard siswa), `GuestLayout.vue` (autentikasi Breeze), dan `MobileAppShell.vue`.
*   **Shared Vue Components:** Tombol, input label, loader khusus (`NamiraLoader.vue`), pagination, dan komponen modal reaktif yang berada di `resources/js/Components/`.

---

## 7. Backend Architecture
**Confidence Level: High**
(Berdasarkan pemetaan pola backend)

*   **HTTP Layer:** Menggunakan middleware bawaan Laravel ditambah middleware spesifik unit (`CheckUnitScope`) untuk memastikan transaksi data terisolasi berdasarkan unit sekolah yang dipilih oleh pengguna saat itu.
*   **Controllers:** Bertindak sebagai orkestrator tipis (*thin controllers*). Controller menangkap request, melakukan validasi, memanggil operasi database via model, dan mengirimkan respons render Inertia.
*   **Models:** Model Eloquent berisi definisi relasi basis data. Sebagian besar logika bisnis diletakkan di dalam model ini (Active Record Pattern).
*   **Console & Notifications:** Aplikasi memiliki konsol command scheduler untuk mengubah status absen siswa secara otomatis (`AutoAlphaAttendance.php`) serta notifikasi reset sandi (`ResetPasswordNotification.php`).

---

## 8. Frontend Architecture
**Confidence Level: High**
(Berdasarkan pemetaan file konfigurasi Vite dan Vue)

*   **Vite Pipeline:** Menggunakan `vite` 6.0.0 dengan plugin `@tailwindcss/vite` untuk kompilasi stylesheet TailwindCSS v4 secara instan.
*   **Inertia.js Integration:** Resolusi komponen diatur secara dinamis dengan pencocokan pola `import.meta.glob('./Pages/**/*.vue')`. Ini menghilangkan kebutuhan konfigurasi router terpisah di frontend (Vue Router).
*   **Routing Strategy:** Menggunakan pustaka `Ziggy` untuk merender fungsi pembantu rute Laravel (`route()`) langsung di dalam komponen Vue.
*   **State Management:** State dikelola secara lokal pada masing-masing halaman Vue menggunakan Reactivity API bawaan Vue 3. Tidak ada pustaka state global (seperti Pinia atau Vuex) yang diimplementasikan secara global.

---

## 9. Dependency Direction
**Confidence Level: High**
(Berdasarkan arah impor file)

*   **Flow Aliran Data:** 
    ```
    Route → Controller → Model → Database
    ```
*   **Dependency Antar Modul:** Terdapat dependensi silang yang erat. Modul sekunder (seperti *Academic*, *Finance*, dan *Sarpar*) mengimpor model dari modul *Yayasan* (misalnya model `Unit` dan `AcademicYear`) untuk keperluan relasi data. Pengguna (*User*) diimpor dari core model (`App\Models\User`) untuk melakukan autentikasi di semua modul.

---

## 10. Architectural Findings
**Confidence Level: High**
(Fakta arsitektural yang terverifikasi)

1.  **Arsitektur Modular Monolith Parsial:**
    *   *Evidence:* File program terbagi rapi berdasarkan domain fungsional dalam folder modul (`app/Modules/`), namun seluruh skema rute dikelola terpusat di `routes/web.php`.
    *   *Confidence:* High.
2.  **Duplikasi Kode Akibat Jalur File Misplaced:**
    *   *Evidence:* Ditemukan file model dan controller yang salah diletakkan di bawah `app/Http/Controllers/Modules/...` dan `app/Models/Modules/...`. Hal ini memicu kebingungan struktural karena ada file serupa di dalam namespace modul yang benar.
    *   *Confidence:* High.
3.  **Minimnya Abstraksi Logika Bisnis:**
    *   *Evidence:* Sistem tidak menerapkan pola Repository atau Service Layer global. Controller berinteraksi langsung dengan model Eloquent (fat models/active records).
    *   *Confidence:* High.
4.  **Integrasi Tailwind CSS v4 Baru:**
    *   *Evidence:* Penggunaan `@tailwindcss/vite` pada `vite.config.js` menunjukkan transisi ke Tailwind CSS v4, namun masih mempertahankan file konfigurasi lama `tailwind.config.js` di root.
    *   *Confidence:* High.

---

## 11. Unknown Architecture
**Confidence Level: High**

*   *Potential Pattern:* Registrasi Rute Otomatis per Modul.
    *   *Evidence:* Ditemukan folder `app/Modules/Finance/Routes` yang kosong. Ada kemungkinan arsitektur ini awalnya direncanakan memiliki pendaftaran rute lokal di masing-masing modul, namun belum selesai diimplementasikan.
    *   *Need Further Verification:* Diperlukan pemeriksaan terhadap log Git historis atau dokumentasi rencana pengembangan (ADR) untuk mengetahui apakah folder ini merupakan bekas implementasi yang dihentikan atau rencana masa depan.

---

## 12. Architecture Summary
**High-Level Summary:**
Sistem ini menggunakan arsitektur **Modular Monolith** berbasis framework **Laravel 12** dan **Vue 3 SFC** yang diintegrasikan melalui **Inertia.js** (tanpa REST API terpisah di sisi frontend). Logika aplikasi dikelompokkan ke dalam modul-modul domain independen di folder `app/Modules`, sementara konfigurasi sistem, migrasi database, dan manajemen rute dikendalikan secara terpusat di direktori global aplikasi. Autentikasi dan otorisasi menggunakan Breeze dan Spatie Permission RBAC, dikombinasikan dengan sistem multi-tenancy unit sekolah menggunakan middleware unit scope.

---

## 13. Stage Readiness
**Confidence Level: High**

**Kesimpulan:** Proyek ini **SIAP** untuk memasuki **Stage 04 — Domain & Module Discovery**.
**Alasan:** Arsitektur tingkat tinggi, alur bootstrapping, siklus request, relasi dependensi, dan model pembagian modul bisnis telah dipetakan secara terperinci. Fakta-fakta ketidakrapian folder (*misplaced files*) dan tidak adanya layer abstraksi tambahan telah dicatat dengan jelas, sehingga analisis mendalam per modul bisnis dapat dilakukan pada tahap berikutnya dengan basis data pemahaman arsitektur yang kuat.
