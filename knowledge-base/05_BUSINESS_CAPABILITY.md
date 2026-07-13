# 05_BUSINESS_CAPABILITY

## 1. Business Capability Groups

Aplikasi SuperApp Namira memiliki fungsionalitas bisnis yang terbagi atas 9 domain domain berikut:

### Academic Domain
*   **Student Profile & Enrollment:** Manajemen biodata siswa aktif, status registrasi, impor data, dan setelan Virtual Account bank.
*   **Teacher Directory:** Manajemen biodata pendidik (guru), NIK, dan riwayat mengajar.
*   **Classroom Allocation:** Manajemen pembagian rombongan belajar (rombel) fisik dan penempatan siswa-guru ke dalam kelas.
*   **Curriculum Mapping:** Penentuan bab pelajaran (`Chapter`) dan Indikator Ketercapaian Tujuan Pembelajaran (`LearningObjective`).
*   **Weekly Scheduling:** Penyusunan jadwal pelajaran mingguan per kelas beserta fitur kloning dan reset.
*   **Student Attendance:** Pencatatan absensi harian kelas oleh guru serta penarikan rekapitulasi ketidakhadiran siswa.
*   **Teaching Journal:** Pencatatan aktivitas materi ajar harian oleh guru mata pelajaran di kelas.
*   **Class Promotion:** Pemrosesan kenaikan kelas massal dan pelacakan riwayat kenaikan/kelulusan siswa.

### Finance Domain
*   **Student Billing:** Pembuatan draf dan penerbitan invoice tagihan SPP bulanan atau tagihan insidental siswa.
*   **Transaction Matching:** Pencatatan transaksi pembayaran SPP melalui input manual kasir atau impor mutasi rekening bank CSV.
*   **Student Discount:** Penetapan pemotongan biaya khusus beasiswa atau keringanan yayasan bagi siswa tertentu.
*   **Arrears Reporting:** Rekapitulasi tunggakan siswa, pencetakan rekap tunggakan unit, dan pencetakan surat penagihan tunggakan resmi.
*   **Finance Dashboard:** Grafik kas masuk/keluar untuk memantau neraca penerimaan kasir unit sekolah.

### LMS Domain
*   **Classroom Feed:** Linimasa interaktif guru dan siswa untuk diskusi kelas daring.
*   **Material Sharing:** Pengunggahan dokumen, file PDF, atau tautan materi ajar oleh guru.
*   **Online Assignment:** Pembuatan tugas belajar daring oleh guru beserta tenggat waktu pengumpulan.
*   **Submission Evaluation:** Pengumpulan lembar jawaban tugas berbentuk file dokumen oleh siswa beserta penginputan nilai tugas oleh guru.
*   **Gradebook Consolidation:** Rekapitulasi perolehan nilai tugas siswa untuk satu semester di kelas.

### Counseling (BK) Domain
*   **BK Counseling Session:** Guru BK mencatat agenda konsultasi rahasia dengan siswa.
*   **Achievement Records:** Pencatatan penghargaan prestasi non-akademik siswa.
*   **Disciplinary Violations:** Pencatatan pelanggaran disiplin siswa disertai akumulasi bobot skor poin pelanggaran.

### Sarpar Domain
*   **Inventory & Code Generator:** Pengelolaan aset barang sekolah beserta penyusunan nomor seri kode barang otomatis.
*   **Room Allocation:** Pendataan ruangan fisik sekolah.
*   **Asset Loan Log:** Pencatatan alur pengajuan dan pengembalian peminjaman barang sekolah oleh staf.
*   **Maintenance Log:** Pelaporan kerusakan barang inventaris beserta pelacakan penanganan oleh teknisi.
*   **Usage Log:** Pencatatan pemakaian barang habis pakai sekolah.

### Employee Domain
*   **Staff Registry:** Profil staf dan karyawan non-guru.
*   **GPS Attendance:** Fitur absensi masuk/pulang harian staf menggunakan validasi koordinat GPS dan radius toleransi.

### Public Relations Domain
*   **News Portal:** Pembuatan artikel berita dan dokumentasi kegiatan sekolah untuk portal publik.
*   **Event Promotion:** Publikasi agenda acara sekolah yang dapat diakses oleh masyarakat umum.
*   **Partnership Directory:** Manajemen data kerja sama industri (kemitraan).

### Yayasan Domain
*   **Unit Directory:** Master data unit sekolah (SD, SMP, SMA Namira).
*   **Academic Calendar & Calendar Holidays:** Penentuan tahun ajaran yang aktif serta registrasi hari libur nasional/bersama.
*   **GPS Radius Settings:** Konfigurasi kordinat geografis sekolah untuk toleransi absensi harian GPS.
*   **User Directory Management:** Manajemen registrasi, perizinan, dan reset sandi pengguna global.
*   **System Settings & Control:** Pengendalian status aktif fitur global (toggle feature) serta otorisasi peran dinamis.
*   **Monitoring System:** Peninjauan jejak audit (audit trail) aktivitas transaksi pengguna.

### Student Portal Domain
*   **Student Panel:** Dashboard rekap jadwal pelajaran, histori tagihan keuangan, notifikasi tugas aktif, dan poin pelanggaran BK siswa.
*   **Productivity Tasks:** Fitur To-Do list bagi siswa untuk mencatat dan mencentang tugas produktivitas pribadi mereka.

---

## 2. Detailed Feature Mapping

### Feature: Student Profile Management
*   **Purpose:** Mengelola profil, foto, NIK, dan data pokok siswa.
*   **Owner Domain:** Academic
*   **Primary Actor:** Admin Yayasan, Admin Unit, Staf Kurikulum
*   **Secondary Actor:** Siswa (Hanya membaca via portal)
*   **Entry Point:** URL Route `/yayasan/students`
*   **Required Permission:** `role:super_admin_yayasan|admin_yayasan|admin_unit|staff_yayasan|staff_unit`
*   **Reads Data From:** `students` table, `units` table, `academic_years` table
*   **Writes Data To:** `students` table
*   **Depends On:** `Unit`, `AcademicYear`
*   **Used By:** LMS, Finance, Student Portal
*   **UI Pages:** `resources/js/Pages/Academic/Students/Index.vue`, `Show.vue`, `Edit.vue`
*   **Controllers:** `\App\Modules\Academic\Controllers\StudentController`
*   **Models:** `\App\Modules\Academic\Models\Student`
*   **Services:** None
*   **Notifications:** None
*   **Output:** HTML Table, JSON response, data profil siswa
*   **Confidence:** High

### Feature: Class Promotion (Kenaikan Kelas)
*   **Purpose:** Memindahkan rombongan siswa ke tingkat kelas di atasnya secara massal pada tahun ajaran baru.
*   **Owner Domain:** Academic
*   **Primary Actor:** Staf Kurikulum, Admin Unit
*   **Secondary Actor:** None
*   **Entry Point:** URL Route `/yayasan/promotion`
*   **Required Permission:** `role:super_admin_yayasan|admin_yayasan|admin_unit|staff_yayasan|staff_unit`
*   **Reads Data From:** `students`, `classrooms`, `class_promotions`, `academic_years`
*   **Writes Data To:** `students` (update classroom_id), `class_promotions` (record history)
*   **Depends On:** `Student`, `Classroom`, `AcademicYear`
*   **Used By:** None
*   **UI Pages:** `resources/js/Pages/Academic/Promotion/Index.vue`, `History.vue`
*   **Controllers:** `\App\Modules\Academic\Controllers\ClassPromotionController`
*   **Models:** `\App\Modules\Academic\Models\ClassPromotion`, `Student`, `Classroom`
*   **Services:** None
*   **Notifications:** None
*   **Output:** History log table, preview promotion list
*   **Confidence:** High

### Feature: Student Attendance (Daily)
*   **Purpose:** Pencatatan kehadiran harian siswa per kelas oleh guru pengajar.
*   **Owner Domain:** Academic
*   **Primary Actor:** Guru (Teacher)
*   **Secondary Actor:** Wali Kelas, Admin Unit
*   **Entry Point:** URL Route `/yayasan/student-attendance/{classroom}`
*   **Required Permission:** `role:super_admin_yayasan|admin_yayasan|admin_unit|staff_yayasan|staff_unit|teacher`
*   **Reads Data From:** `students`, `classrooms`, `student_attendances`
*   **Writes Data To:** `student_attendances` table
*   **Depends On:** `Student`, `Classroom`
*   **Used By:** Student Portal
*   **UI Pages:** `resources/js/Pages/Academic/StudentAttendance/Index.vue`, `Show.vue`, `Recap.vue`
*   **Controllers:** `\App\Modules\Academic\Controllers\StudentAttendanceController`
*   **Models:** `\App\Models\StudentAttendance`, `Classroom`, `Student`
*   **Services:** None
*   **Notifications:** None
*   **Output:** Rekap absensi kelas, ekspor XLS laporan absensi
*   **Confidence:** High

### Feature: Student Billing
*   **Purpose:** Menerbitkan invoice tagihan SPP bulanan atau tagihan khusus pembangunan bagi siswa.
*   **Owner Domain:** Finance
*   **Primary Actor:** Bendahara Yayasan, Staf Keuangan Unit
*   **Secondary Actor:** None
*   **Entry Point:** URL Route `/yayasan/finance/bills`
*   **Required Permission:** `role:super_admin_yayasan|admin_yayasan|admin_unit|staff_yayasan|staff_unit`, `feature:feature_finance`
*   **Reads Data From:** `students`, `student_bills`, `finance_types`, `academic_years`
*   **Writes Data To:** `student_bills` table
*   **Depends On:** `Student`, `AcademicYear`, `FinanceType`
*   **Used By:** Student Portal
*   **UI Pages:** `resources/js/Pages/Finance/Bills/Index.vue`, `Create.vue`, `Show.vue`
*   **Controllers:** `\App\Modules\Finance\Controllers\StudentBillController`
*   **Models:** `\App\Modules\Finance\Models\StudentBill`, `Student`, `FinanceType`
*   **Services:** None
*   **Notifications:** None
*   **Output:** Invoice tagihan, daftar tagihan siswa
*   **Confidence:** High

### Feature: Payment Bank Mutation CSV Import
*   **Purpose:** Rekonsiliasi tagihan siswa otomatis melalui impor data transaksi Virtual Account bank (format CSV).
*   **Owner Domain:** Finance
*   **Primary Actor:** Staf Keuangan Unit, Bendahara Yayasan
*   **Secondary Actor:** None
*   **Entry Point:** URL Route `/yayasan/finance/transactions/import`
*   **Required Permission:** `role:super_admin_yayasan|admin_yayasan|admin_unit|staff_yayasan|staff_unit`, `feature:feature_finance`
*   **Reads Data From:** CSV file uploaded, `student_bills`, `students`
*   **Writes Data To:** `transactions` table, `bill_payments` table, `student_bills` (update status to paid)
*   **Depends On:** `StudentBill`, `Transaction`
*   **Used By:** None
*   **UI Pages:** `resources/js/Pages/Finance/Transactions/Import.vue`
*   **Controllers:** `\App\Modules\Finance\Controllers\TransactionController`
*   **Models:** `\App\Modules\Finance\Models\Transaction`, `BillPayment`, `StudentBill`
*   **Services:** None
*   **Notifications:** None
*   **Output:** Status rekonsiliasi pembayaran tagihan siswa
*   **Confidence:** High

### Feature: LMS Assignment Submission
*   **Purpose:** Memungkinkan siswa mengunggah lembar jawaban tugas sekolah daring berbentuk dokumen lampiran.
*   **Owner Domain:** LMS
*   **Primary Actor:** Siswa (Siswa)
*   **Secondary Actor:** Guru (Pemeriksa)
*   **Entry Point:** URL Route `/lms/student/classrooms/{classroom}/assignments/{assignment}`
*   **Required Permission:** `role:siswa`
*   **Reads Data From:** `lms_assignments`, `lms_submissions`, `lms_classrooms`
*   **Writes Data To:** `lms_submissions` table, `lms_submission_files` table
*   **Depends On:** `LmsAssignment`, `Student`
*   **Used By:** LMS Gradebook
*   **UI Pages:** `resources/js/Pages/LMS/Siswa/Assignment/Show.vue`
*   **Controllers:** `\App\Modules\LMS\Controllers\Siswa\LmsStudentController`
*   **Models:** `\App\Modules\LMS\Models\LmsSubmission`, `LmsSubmissionFile`, `LmsAssignment`
*   **Services:** None
*   **Notifications:** None
*   **Output:** Status penyerahan tugas, file dokumen tugas terunggah
*   **Confidence:** High

### Feature: Disciplinary Violations (Pencatatan Pelanggaran)
*   **Purpose:** Mencatat pelanggaran perilaku siswa BK dan mengakumulasikan skor poin sanksi.
*   **Owner Domain:** Counseling
*   **Primary Actor:** Guru BK, Guru Piket
*   **Secondary Actor:** Orang Tua (Notifikasi), Siswa (Portal)
*   **Entry Point:** URL Route `/counseling/violations`
*   **Required Permission:** `role:super_admin_yayasan|admin_unit|bk|teacher`, `feature:feature_counseling`
*   **Reads Data From:** `students`, `violation_categories`, `violations`
*   **Writes Data To:** `violations` table
*   **Depends On:** `Student`, `ViolationCategory`
*   **Used By:** Student Portal
*   **UI Pages:** `resources/js/Pages/Counseling/Violation/Index.vue`, `Create.vue`
*   **Controllers:** `\App\Modules\Counseling\Controllers\ViolationController`
*   **Models:** `\App\Modules\Counseling\Models\Violation`, `ViolationCategory`, `Student`
*   **Services:** None
*   **Notifications:** WhatsApp alert to parents via `WhatsAppHelper`
*   **Output:** Surat peringatan, log poin pelanggaran siswa BK
*   **Confidence:** High

### Feature: Inventory Management & Code Generator
*   **Purpose:** Manajemen profil aset sekolah dan menyusun kode seri unik inventaris secara otomatis.
*   **Owner Domain:** Sarpar
*   **Primary Actor:** Koordinator Sarpar, Staf Logistik
*   **Secondary Actor:** None
*   **Entry Point:** URL Route `/sarpar/inventories`
*   **Required Permission:** `role:super_admin_yayasan|admin_yayasan|admin_unit|koordinator_sarpar|teacher`, `feature:feature_sarpar`
*   **Reads Data From:** `sarpar_inventories`, `sarpar_categories`, `sarpar_rooms`
*   **Writes Data To:** `sarpar_inventories` table
*   **Depends On:** `Category`, `Room`
*   **Used By:** Loans, Maintenance
*   **UI Pages:** `resources/js/Pages/Sarpar/Inventories/Index.vue`, `Show.vue`
*   **Controllers:** `\App\Modules\Sarpar\Controllers\InventoryController`
*   **Models:** `\App\Modules\Sarpar\Models\Inventory`, `Category`, `Room`
*   **Services:** `\App\Modules\Sarpar\Services\InventoryCodeGenerator`
*   **Notifications:** None
*   **Output:** QR/Barcode data, excel export data inventaris
*   **Confidence:** High

### Feature: Staff GPS Attendance
*   **Purpose:** Fasilitas absensi harian karyawan non-guru memanfaatkan deteksi kordinat GPS perangkat.
*   **Owner Domain:** Employee
*   **Primary Actor:** Staf Karyawan, Guru
*   **Secondary Actor:** Admin Yayasan (Persetujuan Izin)
*   **Entry Point:** URL Route `/employee/attendance`
*   **Required Permission:** `role:teacher|staff_unit|staff_yayasan|super_admin_yayasan|admin_unit`, `feature:feature_employee`
*   **Reads Data From:** `attendance_locations` table, `employee_attendances` table
*   **Writes Data To:** `employee_attendances` table
*   **Depends On:** `AttendanceLocation`
*   **Used By:** Yayasan Monitoring
*   **UI Pages:** `resources/js/Pages/Employee/Attendance/Index.vue`
*   **Controllers:** `\App\Modules\Employee\Controllers\AttendanceController`
*   **Models:** `\App\Models\EmployeeAttendance`, `AttendanceLocation`
*   **Services:** None
*   **Notifications:** None
*   **Output:** Status absensi (Tepat Waktu, Terlambat, Di Luar Radius)
*   **Confidence:** High

### Feature: Switch Unit Context
*   **Purpose:** Memungkinkan admin multi-tenant berganti dasbor sekolah aktif (SD/SMP/SMA) secara dinamis.
*   **Owner Domain:** Yayasan
*   **Primary Actor:** Admin Yayasan, Staf Yayasan
*   **Secondary Actor:** None
*   **Entry Point:** URL Route `/switch-unit` (POST)
*   **Required Permission:** `role:super_admin_yayasan|admin_yayasan|admin_unit|staff_yayasan|staff_unit`
*   **Reads Data From:** `units` table
*   **Writes Data To:** User session update
*   **Depends On:** `Unit`
*   **Used By:** All modules
*   **UI Pages:** TopBar dropdown switch unit
*   **Controllers:** `\App\Modules\Yayasan\Controllers\UnitController`
*   **Models:** `\App\Modules\Yayasan\Models\Unit`
*   **Services:** None
*   **Notifications:** None
*   **Output:** Redirect dashboard unit terpilih
*   **Confidence:** High

---

## 3. Feature Dependency Matrix

| Feature | Owner | Depends On | Used By |
| :--- | :--- | :--- | :--- |
| **Student Profile Management** | Academic | Yayasan (Unit, Academic Year) | LMS, Finance, Student Portal |
| **Classroom Allocation** | Academic | Academic (Student, Teacher) | LMS, Student Portal |
| **Weekly Scheduling** | Academic | Academic (Teacher, Subject, Classroom) | Student Portal |
| **Student Attendance** | Academic | Academic (Student, Classroom) | Student Portal |
| **Teaching Journal** | Academic | Academic (Teacher, Classroom, Subject) | Student Portal |
| **Class Promotion** | Academic | Academic (Student, Classroom), Yayasan (Academic Year) | None |
| **Student Billing** | Finance | Academic (Student), Yayasan (Academic Year, Unit) | Student Portal |
| **Payment CSV Import** | Finance | Finance (Student Billing) | None |
| **LMS Assignment & Stream** | LMS | Academic (Classroom, Student, Teacher) | Student Portal |
| **LMS Submission** | LMS | LMS (Assignment), Academic (Student) | LMS Gradebook |
| **Counseling Session BK** | Counseling | Academic (Student) | None |
| **Disciplinary Violations** | Counseling | Academic (Student) | Student Portal |
| **Inventory & QR Code** | Sarpar | Yayasan (Unit) | Loans, Maintenance |
| **Asset Loan Log** | Sarpar | Sarpar (Inventory), Core (User) | None |
| **Maintenance Log** | Sarpar | Sarpar (Inventory), Core (User) | None |
| **Staff Registry** | Employee | Yayasan (Unit), Core (User) | GPS Attendance |
| **Staff GPS Attendance** | Employee | Yayasan (Attendance Location), Core (User) | Yayasan Monitoring |
| **Switch Unit Context** | Yayasan | Yayasan (Unit) | All Modules |
| **User Directory Admin** | Yayasan | Core (User) | All Modules |
| **Student Dashboard** | Student Portal | Academic (Attendance, Class, Schedule), Finance (Bills), Counseling (Violations), LMS | None |
| **Student Productivity Tasks** | Student Portal | Core (User) | None |

---

## 4. Feature Ownership Rules

### Student Profile & Classroom Management
*   **Owner Domain:** Academic
*   **Allowed Modules:** Academic, LMS, Finance, Counseling, Student Portal (Read Only)
*   **Forbidden Modules:** Employee, Sarpar, PublicRelations (Tidak memiliki izin mengonsumsi profil siswa)
*   **Shared Entities:** `Student`, `Classroom`

### Billing & Payment Processing
*   **Owner Domain:** Finance
*   **Allowed Modules:** Finance, Student Portal (Read Only)
*   **Forbidden Modules:** Academic, LMS, Counseling, Sarpar, Employee, PublicRelations (Akses data SPP tertutup rapat)
*   **Shared Entities:** `StudentBill`

### LMS Learning & Submissions
*   **Owner Domain:** LMS
*   **Allowed Modules:** LMS, Student Portal
*   **Forbidden Modules:** Finance, Employee, Sarpar, PublicRelations
*   **Shared Entities:** `LmsClassroom`, `LmsAssignment`

### Asset Loan & Maintenance
*   **Owner Domain:** Sarpar
*   **Allowed Modules:** Sarpar
*   **Forbidden Modules:** Academic, LMS, Finance, Counseling, Employee, PublicRelations, Student Portal
*   **Shared Entities:** `Inventory`, `Room`

### Kepegawaian & GPS Absensi Staf
*   **Owner Domain:** Employee
*   **Allowed Modules:** Employee, Yayasan (Monitoring & Persetujuan)
*   **Forbidden Modules:** Student Portal, LMS, PublicRelations, Finance
*   **Shared Entities:** `Staff`, `EmployeeAttendance`

---

## 5. Unknown Area

*   **Pemicu Otomatisasi Absensi Alpha Siswa:**
    *   *Evidence:* Ditemukan command `AutoAlphaAttendance.php` di core Console folder. Namun, detail waktu pemicuan cron job di server (apakah terjadwal setiap pukul 16:00 sore) belum dapat diverifikasi secara fisik tanpa mengakses file crontab server OS.
    *   *Need Further Verification:* Diperlukan pemeriksaan file penjadwal scheduler Laravel di routes/console.php pada tahap audit database/flow lanjutan.
*   **Virtual Account Billing Integration:**
    *   *Evidence:* Terdapat method `importVa` di StudentController. Belum dapat dipastikan apakah Virtual Account di-generate otomatis via API Bank atau diimpor secara statis dari nomor VA yang disediakan bank di excel.
    *   *Need Further Verification:* Diperlukan pemeriksaan detail integrasi controller pada tahap berikutnya.
