# 08_DATABASE_ARCHITECTURE

## 1. Database Overview
**Confidence Level: High**
(Diverifikasi dari berkas konfigurasi `.env`, `composer.json`, dan file migrasi)

*   **DBMS:** MySQL / MariaDB (SQLite dikonfigurasi sebagai database pengujian lokal pada composer scripts).
*   **Database Version:** Unknown (Need Further Verification).
*   **Character Set:** `utf8mb4` (Mendukung karakter UTF-8 penuh termasuk emoji).
*   **Collation:** `utf8mb4_unicode_ci` / `utf8mb4_0900_ai_ci` (Default Laravel 12).
*   **Engine:** `InnoDB` (Mendukung transaksi database, foreign key constraints, dan pemulihan crash).
*   **Migration Strategy:** Laravel Migrations secara terpusat di folder `database/migrations/`.
*   **Naming Convention:**
    *   *Tabel:* `snake_case` dan berbentuk jamak (*plural*), contoh: `student_bills`, `lms_submissions`.
    *   *Kolom:* `snake_case`, contoh: `billing_date`, `paid_amount`.
    *   *Foreign Key:* `singuler_table_name_id`, contoh: `student_id` yang merujuk pada `students.id`.

---

## 2. Schema Catalog

### Domain: Core Shared & Framework
*   **`users`:** Menampung data login akun pengguna global. (Aggregate: User | Owner: Core | Confidence: High)
*   **`permissions` / `roles` / `model_has_roles` / `model_has_permissions` / `role_has_permissions`:** Tabel otorisasi bawaan Spatie Laravel Permission. (Aggregate: Permission | Owner: Core | Confidence: High)
*   **`activity_log`:** Jejak audit aktivitas transaksi sistem bawaan Spatie Activity Log. (Aggregate: AuditLog | Owner: Core | Confidence: High)
*   **`sessions` / `cache` / `cache_locks` / `jobs` / `failed_jobs` / `job_batches`:** Tabel manajemen sesi, antrean, dan cache sistem bawaan Laravel. (Aggregate: Framework | Owner: Core | Confidence: High)
*   **`pulse_values` / `pulse_entries` / `pulse_aggregates`:** Tabel monitoring kinerja server milik Laravel Pulse. (Aggregate: Pulse | Owner: Core | Confidence: High)

### Domain: Yayasan
*   **`units`:** Menampung data unit sekolah (SD, SMP, SMA). (Aggregate: Unit | Owner: Yayasan | Confidence: High)
*   **`academic_years`:** Data kalender tahun ajaran aktif sekolah. (Aggregate: AcademicYear | Owner: Yayasan | Confidence: High)
*   **`holidays`:** Kalender hari libur bersama sekolah. (Aggregate: Holiday | Owner: Yayasan | Confidence: High)
*   **`system_settings`:** Konfigurasi parameter fitur global. (Aggregate: SystemSetting | Owner: Yayasan | Confidence: High)
*   **`attendance_locations`:** Koordinat GPS dan radius absensi WFO karyawan. (Aggregate: AttendanceLocation | Owner: Yayasan | Confidence: High)

### Domain: Academic
*   **`students`:** Profil detail data siswa aktif. (Aggregate: Student | Owner: Academic | Confidence: High)
*   **`teachers`:** Profil detail data guru pendidik. (Aggregate: Teacher | Owner: Academic | Confidence: High)
*   **`classrooms`:** Data rombongan belajar (rombel) kelas fisik. (Aggregate: Classroom | Owner: Academic | Confidence: High)
*   **`subjects`:** Daftar mata pelajaran sekolah. (Aggregate: Classroom | Owner: Academic | Confidence: High)
*   **`class_schedules`:** Jadwal pelajaran mingguan kelas. (Aggregate: Classroom | Owner: Academic | Confidence: High)
*   **`chapters`:** Daftar bab kurikulum pelajaran. (Aggregate: Classroom | Owner: Academic | Confidence: High)
*   **`learning_objectives`:** Indikator ketercapaian tujuan pembelajaran. (Aggregate: Classroom | Owner: Academic | Confidence: High)
*   **`teaching_journals`:** Jurnal harian ajar guru di kelas. (Aggregate: Teacher | Owner: Academic | Confidence: High)
*   **`journal_learning_objectives`:** Pivot relasi jurnal dengan indikator tujuan belajar. (Aggregate: Teacher | Owner: Academic | Confidence: High)
*   **`journal_attendance` (or `student_attendances`):** Pencatatan absensi harian kelas siswa. (Aggregate: Student | Owner: Academic | Confidence: High)
*   **`class_promotions`:** Log riwayat kenaikan kelas massal siswa. (Aggregate: Student | Owner: Academic | Confidence: High)

### Domain: Finance
*   **`finance_types`:** Kategori master biaya sekolah (SPP, Gedung, dll). (Aggregate: StudentBill | Owner: Finance | Confidence: High)
*   **`finance_accounts`:** Rekening bank yayasan tujuan transfer kas. (Aggregate: StudentBill | Owner: Finance | Confidence: High)
*   **`student_discounts`:** Pemberian potongan/beasiswa siswa BK. (Aggregate: StudentBill | Owner: Finance | Confidence: High)
*   **`student_bills`:** Dokumen tagihan/invoice siswa per item. (Aggregate: StudentBill | Owner: Finance | Confidence: High)
*   **`transactions`:** Header pembayaran mutasi kas. (Aggregate: StudentBill | Owner: Finance | Confidence: High)
*   **`bill_payments`:** Pivot alokasi transaksi kas terhadap tagihan siswa. (Aggregate: StudentBill | Owner: Finance | Confidence: High)

### Domain: LMS
*   **`lms_classrooms`:** Ruang kelas virtual. (Aggregate: LmsClassroom | Owner: LMS | Confidence: High)
*   **`lms_announcements`:** Linimasa pengumuman kelas online. (Aggregate: LmsClassroom | Owner: LMS | Confidence: High)
*   **`lms_materials`:** Postingan materi ajar digital. (Aggregate: LmsClassroom | Owner: LMS | Confidence: High)
*   **`lms_material_files`:** Attachment dokumen lampiran materi. (Aggregate: LmsClassroom | Owner: LMS | Confidence: High)
*   **`lms_assignments`:** Pembuatan tugas daring kelas. (Aggregate: LmsClassroom | Owner: LMS | Confidence: High)
*   **`lms_submissions`:** Pengumpulan tugas oleh siswa BK. (Aggregate: LmsClassroom | Owner: LMS | Confidence: High)
*   **`lms_submission_files`:** File lampiran jawaban tugas siswa. (Aggregate: LmsClassroom | Owner: LMS | Confidence: High)

### Domain: Counseling (BK)
*   **`violation_categories`:** Master bobot skor pelanggaran tata tertib BK. (Aggregate: Violation | Owner: Counseling | Confidence: High)
*   **`violations`:** Transaksi pencatatan pelanggaran siswa. (Aggregate: Violation | Owner: Counseling | Confidence: High)
*   **`achievements`:** Pencatatan prestasi non-akademik siswa. (Aggregate: Violation | Owner: Counseling | Confidence: High)
*   **`counseling_sessions`:** Log privat bimbingan konseling siswa. (Aggregate: CounselingSession | Owner: Counseling | Confidence: High)

### Domain: Sarpar
*   **`sarpar_categories`:** Klasifikasi kategori aset barang. (Aggregate: Inventory | Owner: Sarpar | Confidence: High)
*   **`sarpar_rooms`:** Data penempatan ruang fisik barang. (Aggregate: Room | Owner: Sarpar | Confidence: High)
*   **`sarpar_inventories`:** Detail spesifikasi dan kode barang unik. (Aggregate: Inventory | Owner: Sarpar | Confidence: High)
*   **`sarpar_loans`:** Log transaksi pinjam-kembali barang. (Aggregate: Inventory | Owner: Sarpar | Confidence: High)
*   **`sarpar_maintenance_logs`:** Log laporan perbaikan barang rusak. (Aggregate: Inventory | Owner: Sarpar | Confidence: High)
*   **`sarpar_usage_logs`:** Log pemakaian bahan habis pakai. (Aggregate: Inventory | Owner: Sarpar | Confidence: High)

### Domain: Employee
*   **`staff`:** Profil karyawan non-guru. (Aggregate: Staff | Owner: Employee | Confidence: High)
*   **`employee_attendances`:** Log absensi masuk/pulang GPS karyawan WFO. (Aggregate: Staff | Owner: Employee | Confidence: High)

### Domain: Public Relations & Student Portal
*   **`news`:** Tabel artikel berita sekolah (Core models). (Aggregate: Shared | Owner: PublicRelations | Confidence: High)
*   **`events`:** Tabel publikasi acara/agenda sekolah. (Aggregate: Shared | Owner: PublicRelations | Confidence: High)
*   **`partners`:** Tabel daftar kerja sama mitra. (Aggregate: Shared | Owner: PublicRelations | Confidence: High)
*   **`student_tasks`:** Tabel To-Do List produktivitas mandiri siswa. (Aggregate: StudentTask | Owner: Student | Confidence: High)

---

## 3. Table Analysis (Core Domain Tables)

### Tabel: `users`
*   **Purpose:** Menyimpan akun otentikasi kredensial pengguna global.
*   **Primary Key:** `id` (bigint, auto_increment)
*   **Foreign Keys:** None.
*   **Columns:** `id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `photo_path` (nullable).
*   **Nullable Columns:** `email_verified_at`, `remember_token`, `photo_path`.
*   **Default Values:** None.
*   **Unique Constraints:** `email` (unique).
*   **Soft Delete:** No.
*   **Timestamp:** Yes (`created_at`, `updated_at`).
*   **Relationships:** HasOne `Student`, HasOne `Teacher`, HasOne `Staff`, HasMany `EmployeeAttendance`, HasMany `LmsAnnouncement`.
*   **Referenced By:** `students.user_id`, `teachers.user_id`, `staff.user_id`, `employee_attendances.user_id`.
*   **Used By Module:** All Modules.
*   **Estimated Rows:** Unknown.
*   **Confidence:** High.

### Tabel: `students`
*   **Purpose:** Profil data biodata siswa terdaftar.
*   **Primary Key:** `id` (bigint, auto_increment)
*   **Foreign Keys:**
    *   `user_id` → `users.id` (cascade on delete)
    *   `unit_id` → `units.id` (cascade on delete)
    *   `classroom_id` → `classrooms.id` (null on delete)
*   **Columns:** `id`, `user_id`, `unit_id`, `classroom_id`, `nis`, `nisn`, `full_name`, `gender` (enum: L/P), `parent_phone`, `va_number`, `photo`, `created_at`, `updated_at`.
*   **Nullable Columns:** `classroom_id`, `nis`, `nisn`, `parent_phone`, `va_number`, `photo`.
*   **Indexes:** `va_number` (index).
*   **Soft Delete:** No.
*   **Timestamp:** Yes.
*   **Relationships:** BelongsTo `User`, BelongsTo `Unit`, BelongsTo `Classroom`, HasMany `StudentBill`, HasMany `Violation`, HasMany `LmsSubmission`, HasMany `ClassPromotion`.
*   **Used By Module:** Academic, Finance, LMS, BK, Student Portal.
*   **Estimated Rows:** Unknown.
*   **Confidence:** High.

### Tabel: `student_bills`
*   **Purpose:** Menyimpan berkas invoice tagihan bulanan/insidental milik siswa.
*   **Primary Key:** `id` (bigint, auto_increment)
*   **Foreign Keys:**
    *   `student_id` → `students.id` (cascade on delete)
    *   `finance_type_id` → `finance_types.id` (null on delete)
*   **Columns:** `id`, `student_id`, `finance_type_id`, `bill_code`, `description`, `billing_date`, `due_date`, `original_amount` (decimal), `discount_amount` (decimal), `final_amount` (decimal), `paid_amount` (decimal), `status` (enum: unpaid, partial, paid, cancelled), `created_at`, `updated_at`, `deleted_at`.
*   **Nullable Columns:** `finance_type_id`, `due_date`, `deleted_at`.
*   **Unique Constraints:** `bill_code` (unique).
*   **Soft Delete:** Yes (`deleted_at`).
*   **Timestamp:** Yes.
*   **Relationships:** BelongsTo `Student`, BelongsTo `FinanceType`, HasMany `BillPayment`.
*   **Used By Module:** Finance, Student Portal.
*   **Estimated Rows:** Unknown.
*   **Confidence:** High.

### Tabel: `transactions`
*   **Purpose:** Menampung mutasi kas masuk pelunasan pembayaran SPP.
*   **Primary Key:** `id` (bigint, auto_increment)
*   **Foreign Keys:**
    *   `student_id` → `students.id` (null on delete)
    *   `user_id` → `users.id` (nullable, kasir input)
    *   `finance_account_id` → `finance_accounts.id` (nullable)
*   **Columns:** `id`, `student_id`, `user_id`, `finance_account_id`, `transaction_code`, `amount` (decimal), `payment_method` (enum: cash, transfer, manual_va, other), `source`, `reference_id`, `notes`, `transaction_date` (timestamp), `allocated_amount` (decimal), `excess_amount` (decimal), `created_at`, `updated_at`.
*   **Nullable Columns:** `student_id`, `user_id`, `finance_account_id`, `reference_id`, `notes`.
*   **Unique Constraints:** `transaction_code` (unique).
*   **Soft Delete:** No.
*   **Timestamp:** Yes.
*   **Relationships:** BelongsTo `Student`, BelongsTo `FinanceAccount`, HasMany `BillPayment`.
*   **Used By Module:** Finance.
*   **Estimated Rows:** Unknown.
*   **Confidence:** High.

### Tabel: `employee_attendances`
*   **Purpose:** Log absensi GPS masuk/pulang karyawan.
*   **Primary Key:** `id` (bigint, auto_increment)
*   **Foreign Keys:**
    *   `user_id` → `users.id` (cascade on delete)
    *   `attendance_location_id` → `attendance_locations.id` (null on delete)
    *   `approved_by` → `users.id` (null on delete)
*   **Columns:** `id`, `user_id`, `date` (date), `check_in_time` (time), `check_in_latitude` (decimal), `check_in_longitude` (decimal), `check_in_photo`, `check_out_time` (time), `check_out_latitude` (decimal), `check_out_longitude` (decimal), `check_out_photo`, `status` (string), `note`, `attendance_location_id`, `permit_file`, `approval_status` (string), `approved_by`, `rejection_reason`, `late_minutes` (int), `created_at`, `updated_at`.
*   **Nullable Columns:** Hampir semua kolom Check In/Check Out bernilai nullable (diisi bertahap), `attendance_location_id`, `approved_by`, `rejection_reason`.
*   **Unique Constraints:** `['user_id', 'date']` (Mencegah absen ganda pada hari yang sama).
*   **Soft Delete:** No.
*   **Timestamp:** Yes.
*   **Relationships:** BelongsTo `User`, BelongsTo `AttendanceLocation`.
*   **Used By Module:** Employee, Yayasan.
*   **Estimated Rows:** Unknown.
*   **Confidence:** High.

### Tabel: `sarpar_inventories`
*   **Purpose:** Pendataan aset inventaris sekolah.
*   **Primary Key:** `id` (bigint, auto_increment)
*   **Foreign Keys:**
    *   `unit_id` → `units.id` (cascade on delete)
    *   `category_id` → `sarpar_categories.id` (cascade on delete)
    *   `room_id` → `sarpar_rooms.id` (null on delete)
*   **Columns:** `id`, `unit_id`, `category_id`, `room_id`, `funding_source` (enum: BOS/YYS), `code`, `created_at`, `updated_at` (kolom detail lainnya menyusul di migrasi lanjutan).
*   **Unique Constraints:** `code` (unique).
*   **Soft Delete:** No.
*   **Timestamp:** Yes.
*   **Relationships:** BelongsTo `Category`, BelongsTo `Room`, HasMany `Loan`, HasMany `MaintenanceLog`, HasMany `UsageLog`.
*   **Used By Module:** Sarpar.
*   **Confidence:** High.

---

## 4. Relationship Mapping

Desain relasi database yang diimplementasikan pada database Namira:

*   **One To One:**
    *   `users` ↔ `students` (Satu user log-in dikaitkan ke satu profil siswa).
    *   `users` ↔ `teachers` (Satu user dikaitkan ke satu profil guru).
    *   `users` ↔ `staff` (Satu user dikaitkan ke satu profil staf).
*   **One To Many:**
    *   `units` ↔ `classrooms` (Satu unit sekolah memiliki banyak kelas).
    *   `classrooms` ↔ `students` (Satu kelas menampung banyak siswa).
    *   `students` ↔ `student_bills` (Satu siswa menerima banyak invoice tagihan SPP).
    *   `lms_assignments` ↔ `lms_submissions` (Satu tugas menampung banyak jawaban siswa).
    *   `sarpar_inventories` ↔ `sarpar_loans` (Satu barang dapat dipinjam berkali-kali).
*   **Many To Many / Pivot Table:**
    *   `student_bills` ↔ `transactions` (Melalui pivot `bill_payments` untuk menjamin alokasi bayar *waterfall*).
    *   `teaching_journals` ↔ `learning_objectives` (Melalui pivot `journal_learning_objectives` untuk memetakan capaian mengajar guru).
*   **Self Reference / Parent-Child:**
    *   Tidak ditemukan model self-reference dalam migrasi utama.

---

## 5. Entity Relationship Analysis (by Aggregates)

Batas konsistensi transaksi dikelompokkan dalam domain Aggregate:

### Student Aggregate Boundary
*   `students` (Root)
*   `class_promotions` (Log riwayat kelas)
*   `student_attendances` (Absensi harian sekolah)
*   `student_tasks` (To-Do list pribadi)
*   *Integritas:* Perubahan status kelulusan di `class_promotions` otomatis memicu penolakan relasi baru siswa BK di kelas lama.

### StudentBill (Billing) Aggregate Boundary
*   `student_bills` (Root)
*   `student_discounts` (Potongan beasiswa)
*   `bill_payments` (Pivot pembayaran)
*   *Integritas:* Pengurangan diskon memodifikasi nilai `final_amount` pada root tagihan sebelum di-commit lunas oleh `bill_payments`.

### LMS Classroom Aggregate Boundary
*   `lms_classrooms` (Root)
*   `lms_materials` / `lms_material_files`
*   `lms_assignments`
*   `lms_submissions` / `lms_submission_files`
*   `lms_announcements`

---

## 6. Foreign Key Dependency Graph

Hierarki ketergantungan tabel (foreign key constraints) digambarkan sebagai berikut:

```
                  [units] 
                     │
         ┌───────────┼───────────┐
         ▼           ▼           ▼
   [teachers]  [classrooms]  [staff]
         │           │           │
         │           ▼           ▼
         │       [students]  [employee_attendances]
         │           │
         ▼           ▼
[teaching_journals] [student_bills] <── [bill_payments] 
                            │                 │
                            ▼                 ▼
                     [student_discounts] [transactions]
```

---

## 7. Shared Tables

Beberapa tabel dimanfaatkan secara lintas domain untuk menyatukan ekosistem Modular Monolith:
1.  **`users`:** Digunakan oleh domain Academic, Employee, LMS, dan Yayasan sebagai basis data kredensial login dan identifikasi pembuat aksi (*reported_by*, *counselor_id*).
2.  **`units`:** Digunakan hampir di seluruh modul bisnis sebagai filter isolasi unit (tenancy) sekolah.
3.  **`academic_years`:** Digunakan modul Academic, Finance, dan LMS untuk mengelompokkan data tahun ajaran.

---

## 8. Multi Tenancy Analysis

Aplikasi mengimplementasikan **Single Database Multi-Tenancy** menggunakan kolom:
*   `unit_id` (Isolasi sekolah SD/SMP/SMA)
*   `academic_year_id` (Isolasi kalender operasional)

### Scope Query
Isolasi data di tingkat database dilakukan secara eksplisit pada query controller atau relasi Eloquent:
```php
$unitId = session('active_unit_id');
$query->where('unit_id', $unitId);
```
*   *Global Scope:* Aplikasi **tidak** menerapkan Laravel Global Scope (`booted` method pada Model) untuk otomatisasi pemilahan `unit_id`. Seluruh pemilahan data dilakukan secara manual di tingkat controller query (`TransactionController`, `StaffController`, `ClassroomController`). Hal ini berisiko memicu kebocoran data jika ada rute baru yang lupa memfilter `session('active_unit_id')`.

---

## 9. Data Integrity

Untuk mengamankan validitas data, sistem menerapkan aturan *constraints* berikut:
*   **Cascade On Delete:** Diterapkan pada relasi bertingkat, contoh: `student_id` di tabel `student_bills` terkonfigurasi `cascadeOnDelete()`. Jika data siswa dihapus, seluruh rekam tagihannya ikut terhapus otomatis.
*   **Null On Delete:** Diterapkan pada referensi sekunder, contoh: `classroom_id` di tabel `students` terkonfigurasi `nullOnDelete()`. Jika kelas fisik dihapus, siswa tidak ikut terhapus melainkan status kelasnya bernilai null (menunggu alokasi baru).
*   **Unique Constraints:** Mencegah redundansi data, contoh: `['user_id', 'date']` pada `employee_attendances` untuk menjamin karyawan tidak Check In ganda.

---

## 10. Normalization Analysis

*   **1NF / 2NF / 3NF:** Struktur tabel secara umum telah memenuhi kriteria Normalisasi 3NF. Kolom deskriptif dipisahkan ke tabel master (`violation_categories`, `finance_types`).
*   **Denormalisasi Terencana (Performance-driven):**
    *   Kolom `points` di tabel `violations` disimpan secara langsung (redudan dengan `violation_categories.default_points`). Ini adalah denormalisasi terencana yang diizinkan untuk memfasilitasi *override* poin sanksi khusus oleh Guru BK tanpa mengubah bobot master kategori pelanggaran BK.
*   **Redundansi / Duplikasi Data:**
    *   Ditemukan duplikasi model dan factory di root `database/factories/` dan `database/factories/Modules/Academic/Models/`. Ini memicu technical debt di tingkat kode, namun struktur tabel fisiknya tetap normal.

---

## 11. Index Analysis

### Indexes Terdaftar (Evidence)
*   **Primary Keys:** Secara otomatis dibuat pada kolom `id` (B-Tree index) untuk lookup instan.
*   **Unique Index:** Dibuat pada `bill_code` (student_bills), `transaction_code` (transactions), dan composite unique key `['user_id', 'date']` (employee_attendances).
*   **Foreign Key Index:** Secara otomatis digenerate oleh MySQL untuk mempercepat proses JOIN antar tabel.

### Potential Missing Index (Risk)
*   Tabel `student_bills` memiliki query pencarian status pembayaran dan tanggal jatuh tempo yang intensif (`whereIn('status', ['unpaid', 'partial'])->orderBy('due_date', 'asc')`). Perlu ditambahkan composite index pada `['status', 'due_date']` untuk optimasi pencarian waterfall payment.

---

## 12. Audit Trail Analysis

Sistem mencatat rekam jejak aktivitas dengan dua pendekatan:
1.  **Spatie Activity Log (`activity_log` table):** Merekam log audit trail transaksi CRUD global yang dilakukan user secara otomatis (dilengkapi properti request metadata).
2.  **Jejak Aksi Model (Foreign Key):** Menggunakan kolom `recorded_by`, `reported_by`, `approved_by` di tabel pelanggaran BK, absensi, dan pembayaran SPP untuk melacak akuntabilitas staf penginput data.
3.  **Soft Deletes:** Diterapkan pada tabel keuangan (`student_bills`) dan kesiswaan BK (`violations`, `achievements`, `counseling_sessions`) dengan kolom `deleted_at` untuk mengamankan data dari aksi penghapusan permanen tidak terencana.

---

## 13. Database Dependency Matrix

| Table | Depends On | Referenced By | Aggregate | Domain |
| :--- | :--- | :--- | :--- | :--- |
| **users** | None | students, teachers, staff, employee_attendances | User | Core |
| **units** | None | teachers, classrooms, students, staff, sarpar_rooms, violations | Unit | Yayasan |
| **academic_years** | None | classrooms, student_bills, lms_classrooms | AcademicYear | Yayasan |
| **students** | users, units, classrooms | student_bills, lms_submissions, violations | Student | Academic |
| **student_bills** | students, finance_types | bill_payments | StudentBill | Finance |
| **transactions** | students, users, finance_accounts | bill_payments | StudentBill | Finance |
| **bill_payments** | transactions, student_bills | None | StudentBill | Finance |
| **employee_attendances**| users, attendance_locations | None | Staff | Employee |
| **lms_classrooms** | classrooms, subjects, teachers, academic_years | lms_materials, lms_assignments | LmsClassroom | LMS |

---

## 14. Database Hotspot

Pusat kendali skema database yang menjadi simpul utama sistem adalah:
1.  **`users`:** Seluruh entitas operasional (siswa, guru, staf, admin) dihubungkan ke tabel ini. Jika tabel ini lumpuh, seluruh autentikasi dan pencatatan absensi/BK/LMS terhenti.
2.  **`students`:** Menjadi episentrum relasi transaksi keuangan (`student_bills`), penempatan kelas (`classrooms`), log BK (`violations`), dan pengumpulan tugas (`lms_submissions`).
3.  **`units`:** Pengendali multi-tenancy. Semua tabel operasional menggantungkan filter keamanan datanya pada `unit_id`.

---

## 15. Database Refactoring Recommendation

Rekomendasi arsitektural database pasca reverse engineering:

1.  **Penerapan Multi-Tenancy Global Scope:**
    *   *Problem:* Tidak adanya Global Scope pada model Laravel untuk `unit_id` memaksa developer memilah data secara manual di controller. Ini sangat rawan bocor data (*security flaw*).
    *   *Rekomendasi:* Buat Trait `BelongsToUnit` dan gunakan Global Scope di Eloquent Model yang otomatis menyaring query berdasarkan `session('active_unit_id')`.
2.  **Pemisahan Log Absensi Fisik (Student Attendance):**
    *   *Problem:* Tabel `student_attendances` (absensi harian sekolah) bertindak sebagai transaksi harian yang tumbuh sangat cepat.
    *   *Rekomendasi:* Tambahkan composite index pada `['classroom_id', 'date']` karena query rekap absensi wali kelas akan melakukan pencarian intensif pada kombinasi kolom ini.
3.  **Penyediaan Indeks Tambahan untuk Waterfall Payment:**
    *   *Problem:* Proses waterfall query mencari tagihan parsial/belum lunas diurutkan jatuh tempo secara berulang.
    *   *Rekomendasi:* Buat index baru `student_bills_lookup_idx` pada kolom `(student_id, status, due_date)` untuk mempercepat proses komparasi pada saat proses impor CSV massal.
