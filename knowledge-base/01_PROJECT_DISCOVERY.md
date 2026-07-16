# 01_PROJECT_DISCOVERY

## 1. Project Statistics
**Confidence Level: High**
(Berdasarkan scan direktori dan perhitungan file riil)

*   **Total Folder:** 185 (tidak termasuk `node_modules` dan `vendor`)
*   **Total PHP Files:** 288 (termasuk folder source, tests, config, public, scratch, storage, dan bootstrap)
*   **Total Vue Files:** 120
*   **Total JavaScript Files:** 175 (termasuk 171 compiled/asset files di `public`, 2 di `resources`, dan 2 konfigurasi root)
*   **Total TypeScript Files:** Not Found
*   **Total Migration:** 53
*   **Total Seeder:** 16
*   **Total Factory:** 5 (3 core factory dan 2 misplaced factory)
*   **Total Models:** 47 (8 core models, 1 misplaced model, 38 module-specific models)
*   **Total Controllers:** 56 (13 core controllers, 1 misplaced controller, 42 module-specific controllers)
*   **Total Middleware:** 5 (seluruhnya di `app/Http/Middleware`)
*   **Total Policies:** Not Found
*   **Total Requests:** 2 (seluruhnya di `app/Http/Requests`)
*   **Total Resources (API Resource):** Not Found
*   **Total Services:** 1 (`app/Modules/Sarpar/Services/InventoryCodeGenerator.php`)
*   **Total Actions:** Not Found
*   **Total Jobs:** Not Found
*   **Total Events:** Not Found
*   **Total Listeners:** Not Found
*   **Total Notifications:** 1 (`app/Notifications/ResetPasswordNotification.php`)
*   **Total Mail:** Not Found
*   **Total Commands:** 1 (`app/Console/Commands/AutoAlphaAttendance.php`)
*   **Total Traits:** Not Found
*   **Total Enums:** Not Found
*   **Total Tests:** 15

---

## 2. Directory Coverage
**Confidence Level: High**
(Berdasarkan pemetaan struktur direktori root)

*   **`app/`**
    *   *Purpose:* Menyimpan logika utama aplikasi backend.
    *   *Estimated Responsibility:* Controller, Model, Middleware, Console Command, Notification, Service, dan Modul bisnis.
    *   *Main Contents:* Core Http logic, Console commands, Helpers, dan modul domain bisnis (`app/Modules`).
*   **`bootstrap/`**
    *   *Purpose:* Inisialisasi awal aplikasi Laravel.
    *   *Estimated Responsibility:* Bootstrapping framework, registrasi middleware global, penanganan exception, dan caching.
    *   *Main Contents:* `app.php`, `providers.php`.
*   **`config/`**
    *   *Purpose:* Konfigurasi aplikasi backend.
    *   *Estimated Responsibility:* Mengatur koneksi database, autentikasi, mail, logging, antrean, sesi, dll.
    *   *Main Contents:* File PHP konfigurasi Laravel standar dan paket pihak ketiga (Spatie, Activitylog).
*   **`database/`**
    *   *Purpose:* Pengelolaan skema dan data database.
    *   *Estimated Responsibility:* Migrasi skema, data seeding, dan data factory.
    *   *Main Contents:* Subdirektori `migrations`, `seeders`, dan `factories`.
*   **`public/`**
    *   *Purpose:* Direktori publik web server.
    *   *Estimated Responsibility:* Entry point aplikasi (`index.php`) dan penampung aset frontend terkompilasi.
    *   *Main Contents:* `index.php`, `.htaccess`, favicon, dan aset JS/CSS terkompilasi.
*   **`resources/`**
    *   *Purpose:* Sumber daya frontend mentah.
    *   *Estimated Responsibility:* Tampilan server (Blade) dan komponen SPA (Vue 3 / Inertia).
    *   *Main Contents:* `views/` (Blade template), `js/` (Vue pages, components, layouts, app.js).
*   **`routes/`**
    *   *Purpose:* Definisi endpoint / routing.
    *   *Estimated Responsibility:* Mendaftarkan rute aplikasi web, autentikasi, dan perintah konsol.
    *   *Main Contents:* `web.php`, `auth.php`, `console.php`.
*   **`storage/`**
    *   *Purpose:* Penyimpanan internal framework.
    *   *Estimated Responsibility:* Menyimpan log, session, file unggahan lokal, cache framework, dan Blade views yang telah dikompilasi.
    *   *Main Contents:* `app/`, `framework/`, `logs/`.
*   **`tests/`**
    *   *Purpose:* Logika pengujian otomatis.
    *   *Estimated Responsibility:* Unit test, feature test, dan security test.
    *   *Main Contents:* `TestCase.php`, `Pest.php`, folder `Feature`, `Unit`, dan `Security`.

---

## 3. Application Layer Discovery
**Confidence Level: High**
(Berdasarkan penelusuran struktur file riil di dalam `app/` dan `database/`)

### Controllers (56)
*   **Lokasi Core:** `app/Http/Controllers/`
*   **Lokasi Modul:** `app/Modules/<ModuleName>/Controllers/`
*   **Daftar File:**
    *   Core:
        *   `app/Http/Controllers/Controller.php`
        *   `app/Http/Controllers/ProfileController.php`
        *   `app/Http/Controllers/StudentPortalController.php`
        *   `app/Http/Controllers/StudentTaskController.php`
        *   `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
        *   `app/Http/Controllers/Auth/ConfirmablePasswordController.php`
        *   `app/Http/Controllers/Auth/EmailVerificationNotificationController.php`
        *   `app/Http/Controllers/Auth/EmailVerificationPromptController.php`
        *   `app/Http/Controllers/Auth/NewPasswordController.php`
        *   `app/Http/Controllers/Auth/PasswordController.php`
        *   `app/Http/Controllers/Auth/PasswordResetLinkController.php`
        *   `app/Http/Controllers/Auth/RegisteredUserController.php`
        *   `app/Http/Controllers/Auth/VerifyEmailController.php`
    *   Misplaced:
        *   `app/Http/Controllers/Modules/Academic/Controllers/SubjectController.php`
    *   Modules (Academic):
        *   `app/Modules/Academic/Controllers/ClassPromotionController.php`
        *   `app/Modules/Academic/Controllers/ClassroomController.php`
        *   `app/Modules/Academic/Controllers/LearningObjectiveController.php`
        *   `app/Modules/Academic/Controllers/ScheduleController.php`
        *   `app/Modules/Academic/Controllers/StudentAttendanceController.php`
        *   `app/Modules/Academic/Controllers/StudentController.php`
        *   `app/Modules/Academic/Controllers/SubjectController.php`
        *   `app/Modules/Academic/Controllers/TeacherController.php`
        *   `app/Modules/Academic/Controllers/TeachingJournalController.php`
    *   Modules (Counseling):
        *   `app/Modules/Counseling/Controllers/AchievementController.php`
        *   `app/Modules/Counseling/Controllers/CounselingSessionController.php`
        *   `app/Modules/Counseling/Controllers/ViolationCategoryController.php`
        *   `app/Modules/Counseling/Controllers/ViolationController.php`
    *   Modules (Employee):
        *   `app/Modules/Employee/Controllers/AttendanceController.php`
        *   `app/Modules/Employee/Controllers/StaffController.php`
    *   Modules (Finance):
        *   `app/Modules/Finance/Controllers/FinanceAccountController.php`
        *   `app/Modules/Finance/Controllers/FinanceDashboardController.php`
        *   `app/Modules/Finance/Controllers/FinanceReportController.php`
        *   `app/Modules/Finance/Controllers/FinanceTypeController.php`
        *   `app/Modules/Finance/Controllers/StudentBillController.php`
        *   `app/Modules/Finance/Controllers/TransactionController.php`
    *   Modules (LMS):
        *   `app/Modules/LMS/Controllers/Guru/LmsClassroomController.php`
        *   `app/Modules/LMS/Controllers/Siswa/LmsStudentController.php`
    *   Modules (PublicRelations):
        *   `app/Modules/PublicRelations/Controllers/EventController.php`
        *   `app/Modules/PublicRelations/Controllers/NewsController.php`
        *   `app/Modules/PublicRelations/Partners/Form.vue` (Controller: `PartnerController.php`)
    *   Modules (Sarpar):
        *   `app/Modules/Sarpar/Controllers/CategoryController.php`
        *   `app/Modules/Sarpar/Controllers/DashboardController.php`
        *   `app/Modules/Sarpar/Controllers/InventoryController.php`
        *   `app/Modules/Sarpar/Controllers/LoanController.php`
        *   `app/Modules/Sarpar/Controllers/MaintenanceController.php`
        *   `app/Modules/Sarpar/Controllers/RoomController.php`
        *   `app/Modules/Sarpar/Controllers/UsageLogController.php`
    *   Modules (Yayasan):
        *   `app/Modules/Yayasan/Controllers/AcademicYearController.php`
        *   `app/Modules/Yayasan/Controllers/AttendanceApprovalController.php`
        *   `app/Modules/Yayasan/Controllers/AttendanceDataController.php`
        *   `app/Modules/Yayasan/Controllers/AttendanceLocationController.php`
        *   `app/Modules/Yayasan/Controllers/HolidayController.php`
        *   `app/Modules/Yayasan/Controllers/MonitoringController.php`
        *   `app/Modules/Yayasan/Controllers/SettingController.php`
        *   `app/Modules/Yayasan/Controllers/UnitController.php`
        *   `app/Modules/Yayasan/Controllers/UserController.php`

### Models (47)
*   **Lokasi Core:** `app/Models/`
*   **Lokasi Modul:** `app/Modules/<ModuleName>/Models/`
*   **Daftar File:**
    *   Core:
        *   `app/Models/AttendanceLocation.php`
        *   `app/Models/EmployeeAttendance.php`
        *   `app/Models/Event.php`
        *   `app/Models/News.php`
        *   `app/Models/Partner.php`
        *   `app/Models/StudentAttendance.php`
        *   `app/Models/StudentTask.php`
        *   `app/Models/User.php`
    *   Misplaced:
        *   `app/Models/Modules/Academic/Models/Subject.php`
    *   Modules (Academic):
        *   `app/Modules/Academic/Models/Chapter.php`
        *   `app/Modules/Academic/Models/ClassPromotion.php`
        *   `app/Modules/Academic/Models/Classroom.php`
        *   `app/Modules/Academic/Models/ClassSchedule.php`
        *   `app/Modules/Academic/Models/JournalAttendance.php`
        *   `app/Modules/Academic/Models/LearningObjective.php`
        *   `app/Modules/Academic/Models/Student.php`
        *   `app/Modules/Academic/Models/Subject.php`
        *   `app/Modules/Academic/Models/Teacher.php`
        *   `app/Modules/Academic/Models/TeachingJournal.php`
    *   Modules (Counseling):
        *   `app/Modules/Counseling/Models/Achievement.php`
        *   `app/Modules/Counseling/Models/CounselingSession.php`
        *   `app/Modules/Counseling/Models/Violation.php`
        *   `app/Modules/Counseling/Models/ViolationCategory.php`
    *   Modules (Employee):
        *   `app/Modules/Employee/Models/Staff.php`
    *   Modules (Finance):
        *   `app/Modules/Finance/Models/BillPayment.php`
        *   `app/Modules/Finance/Models/FinanceAccount.php`
        *   `app/Modules/Finance/Models/FinanceType.php`
        *   `app/Modules/Finance/Models/StudentBill.php`
        *   `app/Modules/Finance/Models/StudentDiscount.php`
        *   `app/Modules/Finance/Models/Transaction.php`
    *   Modules (LMS):
        *   `app/Modules/LMS/Models/LmsAnnouncement.php`
        *   `app/Modules/LMS/Models/LmsAssignment.php`
        *   `app/Modules/LMS/Models/LmsClassroom.php`
        *   `app/Modules/LMS/Models/LmsMaterial.php`
        *   `app/Modules/LMS/Models/LmsMaterialFile.php`
        *   `app/Modules/LMS/Models/LmsSubmission.php`
        *   `app/Modules/LMS/Models/LmsSubmissionFile.php`
    *   Modules (Sarpar):
        *   `app/Modules/Sarpar/Models/Category.php`
        *   `app/Modules/Sarpar/Models/Inventory.php`
        *   `app/Modules/Sarpar/Models/Loan.php`
        *   `app/Modules/Sarpar/Models/MaintenanceLog.php`
        *   `app/Modules/Sarpar/Models/Room.php`
        *   `app/Modules/Sarpar/Models/UsageLog.php`
    *   Modules (Yayasan):
        *   `app/Modules/Yayasan/Models/AcademicYear.php`
        *   `app/Modules/Yayasan/Models/Holiday.php`
        *   `app/Modules/Yayasan/Models/SystemSetting.php`
        *   `app/Modules/Yayasan/Models/Unit.php`

### Middleware (5)
*   **Lokasi:** `app/Http/Middleware/`
*   **Daftar File:**
    *   `CheckFeatureEnabled.php`
    *   `CheckMaintenanceMode.php`
    *   `CheckUnitScope.php`
    *   `EnsureStudentAccess.php`
    *   `HandleInertiaRequests.php`

### Requests (2)
*   **Lokasi:** `app/Http/Requests/`
*   **Daftar File:**
    *   `ProfileUpdateRequest.php`
    *   `Auth/LoginRequest.php`

### Services (1)
*   **Lokasi:** `app/Modules/Sarpar/Services/`
*   **Daftar File:**
    *   `InventoryCodeGenerator.php`

### Notifications (1)
*   **Lokasi:** `app/Notifications/`
*   **Daftar File:**
    *   `ResetPasswordNotification.php`

### Console Commands (1)
*   **Lokasi:** `app/Console/Commands/`
*   **Daftar File:**
    *   `AutoAlphaAttendance.php`

### Factories (5)
*   **Lokasi Core:** `database/factories/`
*   **Lokasi Misplaced:** `database/factories/Modules/Academic/Models/`
*   **Daftar File:**
    *   Core:
        *   `database/factories/StudentFactory.php`
        *   `database/factories/TeacherFactory.php`
        *   `database/factories/UserFactory.php`
    *   Misplaced:
        *   `database/factories/Modules/Academic/Models/StudentFactory.php`
        *   `database/factories/Modules/Academic/Models/TeacherFactory.php`

### Seeders (16)
*   **Lokasi:** `database/seeders/`
*   **Daftar File:**
    *   `AcademicProfileSeeder.php`, `AdditionalUserSeeder.php`, `AttendanceLocationSeeder.php`, `BulkAcademicSeeder.php`, `ClassroomSeeder.php`, `DatabaseSeeder.php`, `FinanceTestSeeder.php`, `FinanceVaSeeder.php`, `LmsDatabaseSeeder.php`, `RolesAndPermissionsSeeder.php`, `SampleUserSeeder.php`, `SubjectSeeder.php`, `SystemSettingSeeder.php`, `TestingScheduleSeeder.php`, `UnitSeeder.php`, `UserSeeder.php`

### Repositories, Actions, Traits, Enums, Policies, Observers, Events, Listeners, Jobs, Mail
*   *Status:* **Not Found** / Tidak digunakan dalam struktur proyek.

---

## 4. Frontend Discovery
**Confidence Level: High**
(Berdasarkan pemetaan direktori `resources/js/`)

### Pages (98)
*   **Lokasi:** `resources/js/Pages/`
*   **Deskripsi:** Struktur SPA yang terbagi atas folder modul bisnis:
    *   *Root:* `Dashboard.vue`, `Welcome.vue`
    *   *Academic:* 18 Pages (Classrooms, Journal, Objectives, Promotion, Schedules, StudentAttendance, Students, Subjects, Teachers)
    *   *Auth:* 6 Pages (ConfirmPassword, ForgotPassword, Login, Register, ResetPassword, VerifyEmail)
    *   *Counseling:* 8 Pages (Achievement, Category, Session, Violation)
    *   *Employee:* 3 Pages (Attendance, Staff)
    *   *Finance:* 9 Pages (Dashboard, Accounts, Bills, Reports, Transactions, Types)
    *   *LMS:* 8 Pages (Guru, Siswa)
    *   *Profile:* 4 Pages (Edit, Partials/DeleteUserForm, Partials/UpdatePasswordForm, Partials/UpdateProfileInformationForm)
    *   *Public:* 5 Pages (EventDetail, EventIndex, NewsDetail, NewsIndex, TestimonialIndex)
    *   *PublicRelations:* 6 Pages (Events, News, Partners)
    *   *Sarpar:* 7 Pages (Dashboard, Categories, Inventories, Loans, Maintenance, Rooms)
    *   *Student:* 6 Pages (Academic, Counseling, Dashboard, Finance, Menu, Productivity)
    *   *Yayasan:* 16 Pages (AcademicYears, Attendance, AttendanceLocations, Dashboard, Holiday, Monitoring, Settings, Units, Users)

### Layouts (4)
*   **Lokasi:** `resources/js/Layouts/`
*   **Daftar File:**
    *   `AuthenticatedLayout.vue`
    *   `GuestLayout.vue`
    *   `MobileAppShell.vue`
    *   `StudentLayout.vue`

### Components (18)
*   **Lokasi:** `resources/js/Components/`
*   **Daftar File:**
    *   `ApplicationLogo.vue`, `Checkbox.vue`, `DangerButton.vue`, `Dropdown.vue`, `DropdownLink.vue`, `InputError.vue`, `InputLabel.vue`, `MobileCard.vue`, `Modal.vue`, `NamiraLoader.vue`, `NavLink.vue`, `Pagination.vue`, `PrimaryButton.vue`, `ResponsiveNavLink.vue`, `SecondaryButton.vue`, `TextInput.vue`
    *   `Dashboard/Sidebar.vue`
    *   `Dashboard/TopBar.vue`

### Composables & Stores
*   *Status:* **Not Found** (Tidak ada subdirektori atau file khusus yang ditemukan di `resources/js/Composables` atau `resources/js/Stores`).

### Assets & Styling
*   *CSS:* `resources/css/app.css` (TailwindCSS v4 integrasi dengan Vite).
*   *JS:* `resources/js/app.js`, `resources/js/bootstrap.js`.

---

## 5. Route Discovery
**Confidence Level: High**
(Diverifikasi dari `bootstrap/app.php` dan `routes/`)

*   **Web Routes:** Didefinisikan di `routes/web.php` dan `routes/auth.php` (autentikasi Breeze).
*   **API Routes:** Not Found (tidak ada rute eksternal API terpisah).
*   **Console Routes:** Didefinisikan di `routes/console.php` (untuk artisan scheduler).
*   **Broadcast Routes:** Not Found.
*   **Route Groups:** Pengelompokan rute berdasarkan otorisasi middleware Spatie (`role`, `permission`) dan unit scope (`CheckUnitScope`).
*   **Middleware Groups:**
    *   `web`: Berisi middleware default framework, ditambah dengan `CheckUnitScope`, `HandleInertiaRequests`, `AddLinkHeadersForPreloadedAssets`, dan `CheckMaintenanceMode`.

---

## 6. Database Discovery
**Confidence Level: High**
(Berdasarkan audit file di folder `database/`)

*   **Migration (53 files):** Mengelola pembuatan tabel modul inti (`users`, `permissions`), modul akademik, keuangan, kepegawaian, sarana-prasarana (sarpar), bimbingan konseling, LMS, logging aktivitas, dan sistem monitoring.
*   **Seeder (16 files):** Mengisi data awal peran & hak akses, unit sekolah, setting sistem, profil akademik, data keuangan, serta data pengujian schedule.
*   **Factory (5 files):** Penyedia data palsu (faker) untuk model `User`, `Student`, dan `Teacher` (dengan redundansi di folder misplaced).
*   **Database Folder:** Berisi `.gitignore` dan subfolder `factories`, `migrations`, `seeders`. Konfigurasi database utama berada di MySQL via `.env`.

---

## 7. Configuration Discovery
**Confidence Level: High**
(Berdasarkan pemetaan direktori `config/`)

*   `activitylog.php` (Konfigurasi Spatie Activity Log)
*   `app.php` (Konfigurasi aplikasi global, locales, provider)
*   `auth.php` (Konfigurasi autentikasi guard dan provider user)
*   `cache.php` (Driver penyimpanan cache)
*   `database.php` (Konfigurasi koneksi MySQL, SQLite, Redis)
*   `filesystems.php` (Disk penyimpanan lokal/public)
*   `logging.php` (Channel pencatatan log error/debug)
*   `mail.php` (Konfigurasi SMTP server gmail)
*   `permission.php` (Konfigurasi Spatie Role-Permission)
*   `pulse.php` (Konfigurasi Laravel Pulse monitoring tool)
*   `queue.php` (Driver antrean berbasis database)
*   `services.php` (Layanan pihak ketiga)
*   `session.php` (Manajemen sesi berbasis database)

---

## 8. Dependency Discovery
**Confidence Level: High**
(Diverifikasi dari `composer.json` dan `package.json`)

*   **PHP Version:** `^8.2`
*   **Laravel Version:** `^12.0`
*   **Vue Version:** `^3.4.0`
*   **Node Requirement:** Tidak disebutkan batas minimum di package.json, namun menggunakan Vite 6.0.0.
*   **Composer Packages (Main):**
    *   `barryvdh/laravel-dompdf` (v3.1)
    *   `inertiajs/inertia-laravel` (v2.0)
    *   `laravel/pulse`
    *   `laravel/breeze` (v2.3) — autentikasi session-based
    *   `laravel/sanctum` (v4.0) — SPA session guard (dependency Breeze)
    *   `spatie/laravel-activitylog`
    *   `spatie/laravel-permission` (v6.24)
    *   `tightenco/ziggy` (v2.0)
*   **NPM Packages (Main devDependencies & dependencies):**
    *   `@tailwindcss/vite` (v4.0.0)
    *   `vite` (v6.0.0)
    *   `leaflet` (v1.9.4)
    *   `chart.js` & `vue-chartjs`
    *   `sweetalert2`
    *   `@vueup/vue-quill` (Rich Text)
    *   `dayjs`
    *   `aos` (Animations)

---

## 9. Existing Documentation Inventory
**Confidence Level: High**
(Berdasarkan pencarian file markdown di root)

1.  `README.md` (Dokumentasi dasar Laravel)
2.  `ADR.md` (Architecture Decision Records)
3.  `PROJECT_MEMORY.md` (Catatan status memori proyek)
4.  `memory.md` (Log status historis)
5.  `audit_review.md` (Catatan hasil audit review sebelumnya)

---

## 10. Coverage Matrix
**Confidence Level: High**

| Area          | Status   | Coverage | Deskripsi |
| ------------- | -------- | -------- | --------- |
| Source Code   | Complete | 100%     | Seluruh file PHP backend utama telah dihitung dan dipetakan foldernya. |
| Database      | Complete | 100%     | Seluruh file migrasi, seeder, dan factory telah terpetakan tanpa terlewat. |
| Frontend      | Complete | 100%     | Seluruh file Vue, layout, komponen, dan library frontend telah didaftarkan. |
| Configuration | Complete | 100%     | 13 file konfigurasi di folder `config/` dan konfigurasi bootstrap telah terpetakan. |
| Routes        | Complete | 100%     | Semua rute web, auth, console, dan setelan middleware teridentifikasi. |
| Documentation | Complete | 100%     | Inventarisasi semua file dokumen di root telah diselesaikan. |

---

## 11. Discovery Findings
**Confidence Level: High**
(Fakta teknis yang ditemukan tanpa opini)

1.  **Struktur Berbasis Modul:** Aplikasi menerapkan konsep Modular Monolith parsial di dalam `app/Modules`. Logika bisnis utama dibagi menjadi 9 modul fungsional: *Academic*, *Counseling*, *Employee*, *Finance*, *LMS*, *PublicRelations*, *Sarpar*, *Student*, dan *Yayasan*.
2.  **Modul Kosong / Kerangka Kerja:** Modul `Student` di `app/Modules/Student` didapati dalam keadaan kosong tanpa file controller atau model di dalamnya, meskipun foldernya ada.
3.  **Redundansi/Folder Misplaced:**
    *   Ditemukan namespace controller ganda: `app/Http/Controllers/Modules/Academic/Controllers/SubjectController.php` (misplaced), sementara ada file dengan nama yang sama di direktori modular `app/Modules/Academic/Controllers/SubjectController.php`.
    *   Ditemukan model yang diletakkan di luar folder modul yang semestinya: `app/Models/Modules/Academic/Models/Subject.php` (misplaced), padahal ada model serupa di `app/Modules/Academic/Models/Subject.php`.
    *   Ditemukan folder factory ganda: `database/factories/Modules/Academic/Models/StudentFactory.php` dan `database/factories/Modules/Academic/Models/TeacherFactory.php` (misplaced).
4.  **Uji Coba Keamanan Khusus:** Ditemukan folder `tests/Security` berisi file uji coba *security patch* (`test_security_patches_wave3*.php`) yang tidak biasa ada pada instalasi Laravel standar.
5.  **Ketiadaan Layer Abstrak Tambahan:** Tidak ditemukan *Repository Layer*, *Action Layer*, ataupun *Service Layer* global. Satu-satunya service class terisolasi di dalam modul Sarpar (`InventoryCodeGenerator.php`).
6.  **Penggunaan File Script Kustom Root:** Ditemukan script interaktif pengujian di root folder (`debug_roles.php`, `fix_roles.php`, `debug_classroom_data.php`, `inspect_roles.php`, `verify_setup.php`).

---

## 12. Unknown Areas
**Confidence Level: High**

*   Korelasi fungsional dan perilaku dari model/controller/factory yang redundan/misplaced (misalnya, `SubjectController.php` mana yang dipanggil oleh router).
*   Alur kerja dan dependensi antara satu modul dengan modul lainnya (akan dianalisis pada tahap Module Mapping dan Business Flow).
*   Perilaku otorisasi dinamis yang dikonfigurasi melalui migrasi Spatie Permission.

---

## 13. Stage Readiness
**Confidence Level: High**

**Kesimpulan:** Proyek ini **SIAP** untuk memasuki **Stage 03 — Architecture Mapping & Discovery**.
**Alasan:** Semua area fisik proyek (kode backend, frontend, database, rute, konfigurasi) telah berhasil diidentifikasi dan diinventarisasi dengan keyakinan penuh. Temuan-temuan struktural kunci (misplaced namespace, modul kosong, script kustom) telah didokumentasikan sebagai titik awal penelusuran arsitektur di tahap berikutnya.
