# 09_API_ARCHITECTURE

## 1. API Architecture Overview
**Confidence Level: High**
(Diverifikasi dari file routing dan controller)

*   **Jenis Arsitektur API:** **Hybrid / Inertia.js API** (Single Page Application). Aplikasi tidak mengekspos API REST tradisional terpisah melalui `routes/api.php` (file tersebut kosong/tidak digunakan). Seluruh komunikasi backend-frontend dijembatani oleh *Inertia.js* pada `routes/web.php`.
*   **Response Strategy:** Controller memproses logika bisnis dan merender respons UI reaktif berupa komponen Vue menggunakan method `Inertia::render('ComponentName', $data)`. Pada request inisial, server mengembalikan dokumen HTML dengan payload data mentah di dalam tag div root. Pada request navigasi internal, Inertia mengirimkan request Ajax dan server mengembalikan respons JSON berisi data properti baru.
*   **Authentication & Session:**
    *   Menggunakan stateful cookie-based session authentication bawaan Laravel Breeze.
    *   **Sanctum:** Terinstal tetapi tidak digunakan sebagai token bearer API. Sanctum hanya bertindak sebagai pengaman sesi SPA default.
    *   **CSRF:** Diproteksi oleh middleware `VerifyCsrfToken` (atau Middleware CSRF default di Laravel 12), mencocokkan header `X-XSRF-TOKEN` pada setiap request Axios/Inertia non-GET.
*   **API Versioning:** Not Found (tidak diterapkan karena frontend dan backend terikat erat dalam satu repositori Monolith).

---

## 2. Route Structure Analysis

### Module: Public Relations (Humas)
*   **Route Prefix:** `/public-relations`
*   **Middleware:** `auth`, `role:super_admin_yayasan|admin_yayasan|admin_unit|humas_unit`
*   **Controller Namespace:** `App\Modules\PublicRelations\Controllers`
*   **Total Route:** 15 (Resource: `news`, `events`, `partners` kecuali `show`)
*   **Naming Convention:** `public-relations.news.index`, `public-relations.events.store`, dll.

### Module: Yayasan (Central Control)
*   **Route Prefix:** `/yayasan`
*   **Middleware:** `auth`, `role:super_admin_yayasan|admin_yayasan|admin_unit|staff_yayasan|staff_unit`
*   **Controller Namespace:** `App\Modules\Yayasan\Controllers`
*   **Total Route:** ~25 (Resource `units`, `academic-years`, `users`, `holidays`, `attendance-locations`, `settings` global)
*   **Naming Convention:** `yayasan.units.index`, `yayasan.settings.update`, dll.

### Module: Academic
*   **Route Prefix:** `/yayasan`
*   **Middleware:** `auth`, `role:super_admin_yayasan|admin_yayasan|admin_unit|staff_yayasan|staff_unit|teacher`
*   **Controller Namespace:** `App\Modules\Academic\Controllers`
*   **Total Route:** ~35 (Resource `students`, `teachers`, `classrooms`, `subjects`, `schedules`, `student-attendance`, `promotion`)
*   **Naming Convention:** `yayasan.students.index`, `yayasan.classrooms.add-student`, `yayasan.promotion.store`, dll.

### Module: Finance
*   **Route Prefix:** `/yayasan/finance`
*   **Middleware:** `auth`, `feature:feature_finance` (plus role spesifik per endpoint)
*   **Controller Namespace:** `App\Modules\Finance\Controllers`
*   **Total Route:** 18 (Resource `accounts`, `types`, `bills`, `transactions` plus `reports`)
*   **Naming Convention:** `yayasan.finance.dashboard`, `yayasan.finance.transactions.import`, dll.

### Module: Employee
*   **Route Prefix:** `/employee` / `/yayasan/staff`
*   **Middleware:** `auth`, `role:teacher|staff_unit|staff_yayasan|super_admin_yayasan|admin_unit`, `feature:feature_employee`
*   **Controller Namespace:** `App\Modules\Employee\Controllers`
*   **Total Route:** 8 (Absensi masuk/pulang GPS + Resource `staff`)
*   **Naming Convention:** `employee.attendance.index`, `yayasan.staff.index`

### Module: LMS (Teacher & Student)
*   **Route Prefix:** `/lms/teacher` (Guru) / `/lms/student` (Siswa)
*   **Middleware:** `auth`, `role:super_admin_yayasan|admin_yayasan|admin_unit|teacher` (Guru) / `role:siswa` (Siswa)
*   **Controller Namespace:** `App\Modules\LMS\Controllers`
*   **Total Route:** 13
*   **Naming Convention:** `lms.teacher.classrooms.show`, `lms.student.classrooms.assignments.submit`

### Module: Student Portal
*   **Route Prefix:** `/student`
*   **Middleware:** `auth`, `EnsureStudentAccess`, `feature:feature_student_login`
*   **Controller Namespace:** `App\Http\Controllers` (Core)
*   **Total Route:** 9
*   **Naming Convention:** `student.dashboard`, `student.tasks.store`

---

## 3. Endpoint Catalog (Core Operations)

### Domain: Academic
| METHOD | URI | Controller | Action | Middleware / Permission | Module | Purpose | Confidence |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| POST | `/yayasan/students/import-excel` | StudentController | importExcel | role:super_admin_yayasan\|admin_yayasan\|admin_unit | Academic | Impor profil siswa massal | High |
| POST | `/yayasan/promotion` | ClassPromotionController | store | role:super_admin_yayasan\|admin_yayasan\|admin_unit | Academic | Eksekusi naik kelas massal | High |
| POST | `/yayasan/student-attendance/{classroom}` | StudentAttendanceController | store | role:teacher\|staff_unit\|admin_unit | Academic | Input absensi kelas | High |

### Domain: Finance
| METHOD | URI | Controller | Action | Middleware / Permission | Module | Purpose | Confidence |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| GET | `/yayasan/finance/transactions/import` | TransactionController | import | role:super_admin_yayasan\|finance | Finance | Buka form upload CSV | High |
| POST | `/yayasan/finance/transactions/import` | TransactionController | processImport | role:super_admin_yayasan\|finance | Finance | Proses impor waterfall | High |
| GET | `/yayasan/finance/reports/arrears/{student}/print` | FinanceReportController | printArrearsLetter | role:super_admin_yayasan\|finance | Finance | Cetak PDF surat tunggakan | High |

### Domain: LMS
| METHOD | URI | Controller | Action | Middleware / Permission | Module | Purpose | Confidence |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| POST | `/lms/teacher/classrooms/{class}/announcements` | LmsClassroomController | storeAnnouncement | role:teacher | LMS | Posting di stream LMS | High |
| POST | `/lms/student/classrooms/{class}/assignments/{id}/submit` | LmsStudentController | submitAssignment | role:siswa | LMS | Siswa kirim file tugas | High |

### Domain: Employee
| METHOD | URI | Controller | Action | Middleware / Permission | Module | Purpose | Confidence |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| POST | `/employee/attendance/check-in` | AttendanceController | store | role:teacher\|staff_unit | Employee | Absen masuk via GPS | High |
| PUT | `/employee/attendance/check-out/{id}` | AttendanceController | update | role:teacher\|staff_unit | Employee | Absen pulang via GPS | High |

---

## 4. Controller Architecture
**Confidence Level: High**
(Berdasarkan penelusuran kode program controller)

*   **Responsibility:** Controller bertindak sebagai orkestrator tipis (*thin controllers*), kecuali pada modul *Finance* (`TransactionController`) dan *Academic* (`ClassPromotionController`) yang tergolong gemuk (*fat controllers*) karena menampung logika parsing CSV, kalkulasi waterfall payment, dan pemutakhiran kenaikan kelas.
*   **Dependency:** Controller memanggil langsung model Eloquent (`Student`, `StudentBill`, `EmployeeAttendance`) untuk manipulasi basis data.
*   **Service & Repository Usage:** Tidak menggunakan *Repository Pattern*. Penggunaan *Service Class* terbatas hanya pada domain Sarpar (`InventoryCodeGenerator`).
*   **Validation:** Dilakukan di dalam controller menggunakan method `$request->validate()` bawaan Laravel.
*   **Authorization:** Menggunakan middleware rute Spatie `role:...` untuk hak akses pintu masuk, serta verifikasi manual `auth()->user()->hasRole()` di dalam controller untuk membatasi kueri data unit aktif.
*   **Transaction:** Menggunakan `DB::beginTransaction()` / `DB::commit()` / `DB::rollBack()` secara manual di dalam method controller.

---

## 5. Request Lifecycle Diagram

Siklus perjalanan request Inertia.js pada aplikasi:

```
[Browser (Inertia Link Click)]
              ↓
      [public/index.php]
              ↓
  [bootstrap/app.php (Booting)]
              ↓
   [routes/web.php (Matching)]
              ↓
     [Middleware (Spatie RBAC)]
              ↓
     [Middleware (CheckUnitScope)]
              ↓
    [Controller Action Execute]
              ↓
[Request Validation ($request->validate)]
              ↓
    [Database Transaction (MySQL)]
              ↓
[Controller returns Inertia::render]
              ↓
   [Inertia Middleware Intercept]
              ↓
[Browser receives JSON (UI updates)]
```

---

## 6. Request Validation Analysis
**Confidence Level: High**
(Diverifikasi dari validasi request di controller)

*   **Form Request:** Terbatas hanya untuk fungsionalitas core seperti `ProfileUpdateRequest` dan `LoginRequest`.
*   **Request Validation (Inline):** Mayoritas validasi ditulis inline di dalam method controller. Contoh:
    ```php
    $request->validate([
        'type' => 'required|in:present,business_trip,sick,permit',
        'latitude' => 'required_if:type,present|numeric',
        ...
    ]);
    ```
*   **Database Validation:** Menggunakan aturan bawaan Laravel seperti `exists:classrooms,id` dan `unique:transactions,transaction_code` untuk menjamin integritas relasional data sebelum diproses.

---

## 7. Middleware Analysis

*   **`CheckUnitScope`:** Memastikan pengguna mengakses data di bawah unit sekolah yang aktif dalam sesi mereka (`active_unit_id`). Diterapkan secara global pada rute `/yayasan`.
*   **`EnsureStudentAccess`:** Membatasi agar rute portal siswa `/student/*` hanya dapat dibuka oleh user yang memiliki peran `siswa`.
*   **`CheckFeatureEnabled` (`feature:feature_name`):** Mengevaluasi apakah fitur modul tersebut aktif di tabel `system_settings` sebelum memproses rute (misal memblokir akses ke modul BK jika `feature_counseling` bernilai false).
*   **`HandleInertiaRequests`:** Middleware bawaan Inertia untuk membagikan data flash message, unit aktif, dan status login user global ke frontend Vue.
*   **`CheckMaintenanceMode`:** Memblokir akses sistem ke mode pemeliharaan jika diaktifkan admin.

---

## 8. Authentication Flow
**Confidence Level: High**
(Berdasarkan file `routes/auth.php` dan Breeze Controllers)

1.  **Login:** Siswa/Staf memasukkan email dan kata sandi. Diolah oleh `AuthenticatedSessionController@store` menggunakan `LoginRequest`.
2.  **CSRF & Cookie:** Cookie session di-generate di browser. Setiap request non-GET wajib melampirkan token CSRF di header `X-XSRF-TOKEN`.
3.  **Role Redirect:** Setelah masuk, middleware / controller mengecek peran user di tabel `model_has_roles`. Jika peran `siswa`, dialihkan ke `/student/dashboard`. Jika peran staf/guru BK/yayasan, dialihkan ke `/yayasan/dashboard`.
4.  **Guard:** Menggunakan default web guard session. Tidak menggunakan API stateless bearer token (Sanctum/JWT).

---

## 9. Authorization Analysis
**Confidence Level: High**
(Diverifikasi dari rute dan method di controller)

*   **Spatie Role Middleware:** Memvalidasi peran pengguna langsung di deklarasi rute (`middleware('role:teacher|admin_unit')`).
*   **Controller Manual Check:**
    *   Mengecek unit kepemilikan untuk mencegah akses silang:
        ```php
        if ($classroom->unit_id !== session('active_unit_id')) {
            abort(403, 'Akses Ditolak');
        }
        ```
*   **Gate & Policy:** Tidak ditemukan file Policy khusus di dalam modul. Otorisasi diselesaikan sepenuhnya via Spatie RBAC dan pengecekan manual ID unit.

---

## 10. API Response Analysis

*   **Response Type:**
    *   *Inertia SPA Page:* Mengembalikan payload JSON berupa komponen Vue dan properti datanya (`Inertia::render()`).
    *   *Redirect:* Mengalihkan rute pasca aksi POST/PUT/DELETE (`redirect()->route(...)`) dengan membawa flash session data (`with('success', 'Pesan sukses')`).
    *   *File Download:* Mengembalikan response stream PDF (`return response()->download()` atau render DomPDF).
*   **Flash Message & Validation Error:** Dikirimkan via properti `errors` dan `flash` global yang didefinisikan di middleware `HandleInertiaRequests` sehingga dibaca otomatis oleh komponen Toast/Alert di frontend Vue.

---

## 11. Error Handling

*   **Validation Error:** Laravel menangkap `ValidationException` secara otomatis dan mengembalikan kode status HTTP `422 Unprocessable Entity` beserta pesan kesalahan per kolom dalam format JSON Inertia.
*   **Exception Render (`PostTooLargeException`):** Dikonfigurasi khusus di `bootstrap/app.php` untuk memotong file upload di atas limit php.ini dan mengembalikan pesan dalam bahasa Indonesia ke halaman formulir sebelumnya.
*   **Abort:** Menggunakan helper `abort(403, 'Pesan Kustom')` untuk menolak akses jika kueri database unit aktif tidak cocok.

---

## 12. File Upload Architecture
**Confidence Level: High**
(Berdasarkan analisis controller LMS dan Absensi Karyawan)

*   **Storage Disk:** Menggunakan disk publik lokal (`Storage::disk('public')`).
*   **Folder Destinasi:**
    *   LMS Jawaban Tugas: `lms/submissions/`
    *   LMS Materi: `lms/materials/`
    *   Absensi Selfie: `attendance_photos/`
    *   Sakit/Izin Dokumen: `permits/`
*   **File Naming Strategy:** Kombinasi penamaan prefix entitas, ID pengguna, dan timestamp (contoh: `'attendance_' . $user->id . '_' . time() . '.jpg'`).
*   **Delete Strategy (LMS Re-upload):** Sebelum menyimpan berkas baru pada saat pengumpulan ulang tugas, controller mencari tautan berkas lama di basis data, menghapusnya dari disk server menggunakan `Storage::disk('public')->delete()`, baru kemudian menulis file baru guna menghemat ruang penyimpanan disk.

---

## 13. API Security Analysis
**Confidence Level: High**

*   **CSRF Protection:** Aman. Dilindungi oleh middleware token CSRF global bawaan Laravel pada rute web.
*   **Mass Assignment Protection:** Aman. Model-model menggunakan `protected $guarded = []` atau deklarasi `$fillable` yang membatasi kolom masukan.
*   **Input Validation:** Menggunakan validasi stringent di tingkat controller sebelum memicu penulisan ke database.
*   **Broken Access Control Risk:**
    *   *Risk:* Tidak adanya Global Scope untuk `unit_id` pada query model memicu celah keamanan. Jika ada controller baru yang lupa memfilter unit aktif dari sesi, user dapat memanipulasi data sekolah lain melalui modifikasi parameter ID di URL (*ID IDOR*).

---

## 14. API Dependency Graph

Pola arah ketergantungan API:

```
Browser (Inertia Page Click)
       ↓
routes/web.php (Route Matching)
       ↓
CheckUnitScope Middleware (Validation Tenancy)
       ↓
Module Controller (Action Logic)
       ↓
Database Transaction (Commit/Rollback)
       ↓
Inertia Interceptor (Construct JSON Payload)
       ↓
Vite Asset Pipeline (Reacting Vue Component)
```

---

## 15. Cross Module Communication

Komunikasi antar modul dilakukan via import model internal di tingkat controller:
*   **Finance → Academic:** `TransactionController` mengimpor `App\Modules\Academic\Models\Student` untuk alokasi pencarian nomor VA mutasi bank.
*   **LMS → Academic:** `LmsStudentController` mengimpor `App\Modules\Academic\Models\Student` untuk verifikasi kelas virtual.
*   **Student Portal → BK / Finance / LMS:** `StudentPortalController` mengimpor model `Violation` (Counseling BK), `StudentBill` (Finance), dan `LmsClassroom` (LMS) untuk mengumpulkan seluruh rangkuman portofolio siswa ke satu dashboard.

---

## 16. API Performance Analysis
**Confidence Level: High**

*   **Eager Loading vs Lazy Loading:**
    *   *Pola Baik:* Query berita dan event menggunakan eager loading relasi unit (`News::with('unit')->get()`) untuk mencegah masalah kueri N+1.
*   **Heavy Process (Reconciliation):**
    *   *Problem:* Proses impor mutasi rekening bank CSV memproses pencocokan baris secara berulang di memori PHP secara sinkronus. Ini memicu beban RAM server tinggi jika data CSV mencapai ribuan baris.
*   **Pagination:** Diterapkan secara merata pada data riwayat absensi, rincian transaksi (`paginate(20)`), dan daftar berita sekolah.

---

## 17. API Transaction Analysis

Sistem menerapkan proteksi transaksi basis data secara manual pada proses kritis:
*   **Waterfall Payment:** Proses impor mutasi VA CSV menggunakan `DB::beginTransaction()` dan `DB::commit()` untuk menjamin keutuhan data alokasi tagihan siswa.
*   **Class Promotion:** Proses naik kelas massal siswa dilindungi oleh transaksi database untuk memastikan kegagalan satu siswa akan me-rollback seluruh rombel kelas.
*   **LMS Submission:** Pengunggahan file dan pembaruan status tugas siswa BK dibungkus dalam `DB::transaction()`.

---

## 18. Queue & Async Analysis
**Confidence Level: High**
(Berdasarkan analisis file source code)

*   **Queue Driver:** Terkonfigurasi menggunakan database driver (`QUEUE_CONNECTION=database` di `.env`).
*   **Async Processing:** **Not Found** (Belum digunakan). Pengiriman WhatsApp lewat `WhatsAppHelper` saat input pelanggaran BK atau proses parsing CSV masih berjalan secara sinkronus (*blocking process*), yang berpotensi melambatkan respons browser pengguna.
*   **Artisan Scheduler:** Terdapat cron scheduler harian `AutoAlphaAttendance.php` untuk memproses absensi alpa otomatis bagi siswa yang tidak hadir.

---

## 19. REST Compliance
**Confidence Level: High**

*   **REST Compliance Score: Low (Not Compliant).**
    *   Aplikasi ini dirancang sebagai aplikasi Monolith hibrida menggunakan Inertia.js, bukan REST API murni.
    *   Aksi modifikasi data (seperti Check-Out absensi atau submit tugas) mengembalikan respons pengalihan (*redirect*) Inertia dan flash message, bukan payload JSON status standar (seperti `{success: true}`).

---

## 20. API Hotspot

Endpoint yang berpotensi menjadi bottleneck utama sistem:
1.  **POST `/yayasan/finance/transactions/import` (Impor CSV Waterfall):** Memproses baris CSV massal, pencocokan nomor VA regex, komparasi due date tagihan bulanan, dan penulisan pivot payment secara sinkronus.
2.  **POST `/yayasan/promotion` (Kenaikan Kelas Massal):** Pembaruan massal ratusan baris data rombel siswa secara instan di bawah transaksi database.

---

## 21. API Dependency Matrix

| Endpoint | Controller | Model | Database Table | Module |
| :--- | :--- | :--- | :--- | :--- |
| POST `/yayasan/finance/transactions/import` | `TransactionController` | `Transaction`, `StudentBill` | `transactions`, `student_bills` | Finance |
| POST `/yayasan/promotion` | `ClassPromotionController` | `ClassPromotion`, `Student` | `class_promotions`, `students` | Academic |
| POST `/employee/attendance/check-in` | `AttendanceController` | `EmployeeAttendance` | `employee_attendances` | Employee |
| POST `/lms/student/classrooms/{id}/submit` | `LmsStudentController` | `LmsSubmission` | `lms_submissions` | LMS |

---

## 22. API Refactoring Recommendation

Rekomendasi struktural untuk peningkatan arsitektur API tingkat enterprise:

1.  **Asynchronous Background Jobs untuk CSV Import & WhatsApp BK:**
    *   *Problem:* Proses impor mutasi bank CSV dan pengiriman alert WhatsApp wali murid saat BK mencatat pelanggaran berjalan secara sinkronus. Jika API gateway SMS/WhatsApp lambat, browser admin akan mengalami *loading* berkepanjangan.
    *   *Solusi:* Ubah proses impor dan notifikasi menjadi asinkronus menggunakan Laravel Queue Jobs (misal: `ProcessCsvMutationJob` dan `SendWhatsAppNotificationJob`).
2.  **Impelementasi Data Transfer Object (DTO) & Action Classes:**
    *   *Problem:* Logika bisnis waterfall payment dan proses kalkulasi sanksi BK tertumpuk di controller.
    *   *Solusi:* Pindahkan logika bisnis dari controller ke dedicated *Action Classes* (seperti `App\Actions\Finance\ProcessWaterfallPaymentAction`), dan gunakan DTO untuk memvalidasi masukan parameter API.
3.  **Penerapan Global Tenant Scope:**
    *   *Problem:* Pemilahan unit sekolah secara manual (`where('unit_id', session('active_unit_id'))`) rawan memicu kebocoran data jika developer lupa menuliskan kueri tersebut di endpoint baru.
    *   *Solusi:* Buat Trait global scope yang mendeteksi sesi unit aktif secara otomatis untuk seluruh model yang memiliki relasi `unit_id`.
