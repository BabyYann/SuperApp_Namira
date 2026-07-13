# STAGE 04 — DOMAIN MASTER DOCUMENT

## 1. Domain Index

| Domain | Responsibility | Owner Entity | Confidence |
| :--- | :--- | :--- | :--- |
| **Academic** | Mengatur pembagian kelas, jadwal pelajaran, mengelola guru dan siswa, serta pencatatan kehadiran kelas dan kelulusan/kenaikan kelas. | Student, Teacher, Subject, Classroom, ClassSchedule, TeachingJournal, LearningObjective, ClassPromotion | High |
| **Counseling** | Mengelola sesi konseling kesiswaan, pencatatan prestasi/penghargaan, dan pelanggaran tata tertib beserta sanksi poin. | CounselingSession, ViolationCategory, Violation, Achievement | High |
| **Employee** | Mengelola profil kepegawaian (staf non-guru) dan absensi kedatangan harian karyawan. | Staff | High |
| **Finance** | Mengelola setelan rekening keuangan, jenis tagihan SPP, pemotongan diskon siswa, laporan kas, dan impor transaksi mutasi bank. | FinanceAccount, FinanceType, StudentBill, StudentDiscount, Transaction, BillPayment | High |
| **LMS** | Platform pembelajaran online untuk pengumuman, materi/file KBM, pengumpulan tugas, dan rekap penilaian tugas oleh guru. | LmsClassroom, LmsAnnouncement, LmsMaterial, LmsAssignment, LmsSubmission | High |
| **PublicRelations** | Mengelola konten berita umum sekolah, agenda kegiatan humas, testimoni, dan direktori kerja sama kemitraan/sponsor. | News, Event, Partner | High |
| **Sarpar** | Mengatur data aset inventaris sekolah, penempatan ruang, log perawatan barang rusak, peminjaman barang, dan log penggunaan ruang. | Category, Room, Inventory, MaintenanceLog, Loan, UsageLog | High |
| **Student** | Menyediakan antarmuka dashboard terpadu bagi siswa untuk memantau nilai akademik, absensi, tagihan keuangan, BK, dan tugas mandiri. | StudentTask | High |
| **Yayasan** | Pengendali administrasi global yayasan, mengelola data unit sekolah, tahun ajaran aktif, direktori user global, kalender libur, monitoring audit trail, dan setelan radius GPS lokasi absensi. | Unit, AcademicYear, SystemSetting, Holiday | High |

---

## 2. Domain Relationship Map

### 2.1 Relationship Matrix
| Domain | Depends On | Used By | Shared Components | Owner Entities |
| :--- | :--- | :--- | :--- | :--- |
| **Academic** | Yayasan, Core | LMS, Finance, Student BK | User, Unit, AcademicYear | Student, Teacher, Subject, Classroom, ClassSchedule, TeachingJournal, LearningObjective, ClassPromotion |
| **Counseling** | Academic, Yayasan, Core | Student BK | Student, User, Unit | CounselingSession, ViolationCategory, Violation, Achievement |
| **Employee** | Yayasan, Core | Yayasan (Monitoring) | User, Unit, EmployeeAttendance | Staff |
| **Finance** | Academic, Yayasan, Core | Student BK | Student, Unit, AcademicYear, User | FinanceAccount, FinanceType, StudentBill, StudentDiscount, Transaction, BillPayment |
| **LMS** | Academic, Core | Student BK | Student, Teacher, Classroom, User | LmsClassroom, LmsAnnouncement, LmsMaterial, LmsAssignment, LmsSubmission |
| **PublicRelations** | Yayasan, Core | Core (Homepage) | User, Unit, News, Event, Partner | News, Event, Partner (reside in core) |
| **Sarpar** | Yayasan, Core | None | User, Unit | Category, Room, Inventory, MaintenanceLog, Loan, UsageLog |
| **Student** | Academic, Finance, Counseling, LMS, Core | None | User, Student, StudentTask | StudentTask (reside in core) |
| **Yayasan** | Core | All Domains (Academic, Counseling, Employee, Finance, LMS, PublicRelations, Sarpar) | User, AttendanceLocation | Unit, AcademicYear, SystemSetting, Holiday |

### 2.2 Core Shared Domain
Domain bersama (*Core Shared Domain*) adalah pusat pengelolaan data bersama yang tidak terisolasi dalam satu modul tunggal melainkan dikonsumsi oleh seluruh sistem:
1.  **User (`User.php`):** Model pengguna utama untuk autentikasi login guru, staf, admin, dan siswa.
2.  **Permissions (`Spatie Permission`):** Guard peran (*role*) dan izin (*permission*) untuk membatasi hak akses rute di setiap modul.
3.  **Unit (`Unit.php`):** Entitas sekolah (misalnya SD Namira, SMP Namira, SMA Namira) yang mengontrol multi-tenancy unit sekolah yang aktif.
4.  **AcademicYear (`AcademicYear.php`):** Tahun ajaran aktif yang membatasi durasi jadwal pelajaran, tagihan keuangan, dan riwayat absensi kelas.
5.  **AttendanceLocation (`AttendanceLocation.php`):** Titik koordinat kantor/sekolah untuk mencocokkan validasi lokasi absen GPS karyawan.

### 2.3 Domain Communication
Interaksi antar modul dalam sistem monolitik ini dilakukan melalui:
1.  **Impor Model Langsung (Direct Eloquent Imports):** Modul-modul sekunder mengimpor langsung model dari modul Yayasan atau Core. Contoh: Controller keuangan `StudentBillController` mengimpor `App\Modules\Academic\Models\Student` untuk memetakan data SPP siswa.
2.  **Relasi SQL (Foreign Key Constraints):** Database memetakan integritas data antar modul (misal `unit_id` atau `academic_year_id` di hampir setiap tabel transaksi).
3.  **Helper Pihak Ketiga (Global Helper Call):** Pengiriman notifikasi SMS/WhatsApp menggunakan helper bersama `App\Helpers\WhatsAppHelper`.

### 2.4 Domain Ownership Matrix
| Entity | Owner Domain | Used By |
| :--- | :--- | :--- |
| **Student** | Academic | LMS, Finance, Counseling, Student Portal |
| **Teacher** | Academic | LMS, Yayasan (Jadwal) |
| **Subject** | Academic | LMS, Yayasan (Jadwal) |
| **Classroom** | Academic | LMS, Student Portal |
| **Staff** | Employee | Yayasan (Monitoring & Absensi) |
| **Violation & Achievement** | Counseling | Student Portal |
| **StudentBill** | Finance | Student Portal |
| **LmsClassroom & LmsAssignment** | LMS | Student Portal |
| **News & Event** | PublicRelations | Core (Home Portal), Student Portal |
| **Inventory & Room** | Sarpar | None |
| **User** | Core | All Domains (Autentikasi & Jejak Audit) |
| **Unit** | Yayasan | All Domains (Multi-Tenancy) |
| **AcademicYear** | Yayasan | Academic, Finance, LMS |

### 2.5 Domain Dependency Graph
Hierarki hubungan dependensi antar modul didasarkan pada dependensi impor kode dan skema tabel:

```
Core Shared (User, Permission, WhatsAppHelper)
│
└── Yayasan (Unit, AcademicYear, Holiday, SystemSetting)
    ├── Employee (Staff)
    ├── Sarpar (Inventory, Room)
    ├── PublicRelations (News, Event, Partner)
    │
    └── Academic (Student, Teacher, Subject, Classroom)
        ├── LMS (LmsClassroom, Assignment, Material)
        ├── Finance (StudentBill, Transaction, Diskon)
        ├── Counseling (BK Session, Violation, Achievement)
        │
        └── Student Portal (StudentTask, Dashboard Konsumsi)
```

---

## 3. Detailed Domain Overviews

### 3.1 Academic Domain
#### Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Mengatur alokasi siswa dan guru ke kelas.
    *   Validasi rentang waktu tahun ajaran yang sedang aktif saat pendaftaran jadwal pelajaran.
    *   Pencatatan rekam kehadiran siswa oleh guru di kelas.
    *   Penetapan kenaikan kelas siswa dari satu tingkat ke tingkat berikutnya.
*   **Bukan Tanggung Jawab Domain:**
    *   Mengelola slip pembayaran SPP atau tagihan sekolah (tanggung jawab domain *Finance*).
    *   Mengelola absensi kedatangan harian karyawan/staf non-guru (tanggung jawab domain *Employee*).
    *   Proses upload tugas mandiri online dan penilaian kuis (tanggung jawab domain *LMS*).
    *   Pencatatan pelanggaran perilaku non-akademis (tanggung jawab domain *Counseling*).

#### Owned Components
*   *Controllers (9):* ClassPromotionController, ClassroomController, LearningObjectiveController, ScheduleController, StudentAttendanceController, StudentController, SubjectController, TeacherController, TeachingJournalController.
*   *Models (10):* Chapter, ClassPromotion, Classroom, ClassSchedule, JournalAttendance, LearningObjective, Student, Subject, Teacher, TeachingJournal.
*   *Pages (18):* `resources/js/Pages/Academic/*`

#### Domain Findings
*   *Finding:* Ditemukan duplikasi file kelas akademik di luar modul.
    *   *Evidence:* File `app/Models/Modules/Academic/Models/Subject.php` dan `app/Http/Controllers/Modules/Academic/Controllers/SubjectController.php` ditemukan di luar struktur direktori modul yang semestinya.
    *   *Confidence:* High.

---

### 3.2 Counseling Domain
#### Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Pencatatan sesi bimbingan BK (konseling individu atau kelompok).
    *   Manajemen master kategori pelanggaran beserta bobot poin sanksi.
    *   Pencatatan pelanggaran harian siswa oleh guru piket atau guru BK.
*   **Bukan Tanggung Jawab Domain:**
    *   Pencatatan kehadiran/kealpaan harian siswa di kelas (tanggung jawab domain *Academic*).
    *   Pemberian sanksi administratif berupa penundaan ujian/pemblokiran akun siswa karena masalah pembayaran (tanggung jawab domain *Finance*).

#### Owned Components
*   *Controllers (4):* AchievementController, CounselingSessionController, ViolationCategoryController, ViolationController.
*   *Models (4):* Achievement, CounselingSession, Violation, ViolationCategory.
*   *Pages (8):* `resources/js/Pages/Counseling/*`

#### Domain Findings
*   *Finding:* Integrasi WhatsApp untuk Notifikasi Pelanggaran.
    *   *Evidence:* Rujukan pengiriman WhatsApp menggunakan `WhatsAppHelper` saat pelanggaran diinput agar langsung terkirim ke nomor orang tua.
    *   *Confidence:* High.

---

### 3.3 Employee Domain
#### Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Penyimpanan data staf (nama, NIK, jabatan, unit tugas).
    *   Pencatatan absensi masuk dan pulang staf (melalui data absensi GPS/mesin absensi).
*   **Bukan Tanggung Jawab Domain:**
    *   Mengelola jadwal mengajar guru di kelas (tanggung jawab domain *Academic*).
    *   Menghitung gaji, tunjangan, dan potongan keuangan staf (tanggung jawab domain *Finance*).

#### Owned Components
*   *Controllers (2):* AttendanceController, StaffController.
*   *Models (1):* Staff.
*   *Shared Models (1):* `EmployeeAttendance.php` (resides in `app/Models/`).
*   *Pages (3):* `resources/js/Pages/Employee/*`

#### Domain Findings
*   *Finding:* Model Kehadiran Terpisah di Core Folder.
    *   *Evidence:* Data kehadiran staf tidak disimpan di `app/Modules/Employee/Models/` melainkan di core folder `app/Models/EmployeeAttendance.php`. Hal ini menunjukkan dependensi silang.
    *   *Confidence:* High.

---

### 3.4 Finance Domain
#### Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Definisi jenis biaya sekolah (SPP, Uang Pembangunan, Pendaftaran).
    *   Penerbitan faktur tagihan untuk siswa aktif.
    *   Pencatatan pembayaran tagihan (manual input oleh admin atau import mutasi bank).
    *   Pemrosesan diskon khusus beasiswa yayasan.
*   **Bukan Tanggung Jawab Domain:**
    *   Mengelola persediaan barang belanjaan sekolah (tanggung jawab domain *Sarpar*).
    *   Mengelola penggajian karyawan (tanggung jawab domain *Employee* - belum ada modul penggajian).
    *   Mengurusi pendaftaran siswa baru secara akademis (tanggung jawab domain *Academic*).

#### Owned Components
*   *Controllers (6):* FinanceAccountController, FinanceDashboardController, FinanceReportController, FinanceTypeController, StudentBillController, TransactionController.
*   *Models (6):* BillPayment, FinanceAccount, FinanceType, StudentBill, StudentDiscount, Transaction.
*   *Pages (9):* `resources/js/Pages/Finance/*`

#### Domain Findings
*   *Finding:* Impor Mutasi Rekening Otomatis & Sisa Folder Rute.
    *   *Evidence:* `TransactionController` mendukung impor data bank CSV, serta ditemukannya folder `app/Modules/Finance/Routes` yang kosong.
    *   *Confidence:* High.

---

### 3.5 LMS Domain
#### Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Distribusi materi dan file tugas daring.
    *   Pencatatan tanggal pengumpulan tugas oleh siswa untuk evaluasi keterlambatan.
    *   Penilaian tugas mandiri oleh guru mata pelajaran.
*   **Bukan Tanggung Jawab Domain:**
    *   Pencatatan kehadiran fisik siswa di ruang kelas nyata (tanggung jawab domain *Academic* - ditangani oleh `JournalAttendance`).
    *   Pencatatan kemajuan pencapaian Indikator Ketercapaian Tujuan Pembelajaran (tanggung jawab domain *Academic* - ditangani oleh `LearningObjective`).

#### Owned Components
*   *Controllers (2):* Guru/LmsClassroomController, Siswa/LmsStudentController.
*   *Models (7):* LmsAnnouncement, LmsAssignment, LmsClassroom, LmsMaterial, LmsMaterialFile, LmsSubmission, LmsSubmissionFile.
*   *Pages (8):* `resources/js/Pages/LMS/*`

#### Domain Findings
*   *Finding:* Pemisahan Alur Kerja Guru dan Siswa di Tingkat Controller.
    *   *Evidence:* Terdapat subfolder terpisah `Guru` dan `Siswa` pada level controller di backend modul LMS.
    *   *Confidence:* High.

---

### 3.6 Public Relations Domain
#### Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Pengelolaan konten portal publik (News, Events, Partners).
    *   Menghitung jumlah pembaca artikel sekolah.
*   **Bukan Tanggung Jawab Domain:**
    *   Mengelola jadwal pelajaran sekolah (tanggung jawab domain *Academic*).
    *   Pencatatan pelanggaran siswa BK (tanggung jawab domain *Counseling*).
    *   Pengelolaan peminjaman ruangan untuk event internal (tanggung jawab domain *Sarpar*).

#### Owned Components
*   *Controllers (3):* EventController, NewsController, PartnerController.
*   *Models:* None (menggunakan core shared models `News`, `Event`, `Partner` di `app/Models/`).
*   *Pages (6):* `resources/js/Pages/PublicRelations/*` dan `resources/js/Pages/Public/*`

#### Domain Findings
*   *Finding:* Tidak Memiliki Model Internal & Mekanisme View Counter.
    *   *Evidence:* Semua model humas diletakkan di global `app/Models/` (tidak di dalam folder PR). Serta terdapat session-based view counter (`viewed_events`) untuk mencegah spam refresh counter views.
    *   *Confidence:* High.

---

### 3.7 Sarpar Domain
#### Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Manajemen status kelayakan barang (bagus, rusak, diperbaiki).
    *   Pencatatan tanggal pinjam dan tanggal kembali aset sekolah.
    *   Log perbaikan sarana rusak oleh staf teknis.
*   **Bukan Tanggung Jawab Domain:**
    *   Penyediaan dana pembelian aset baru (tanggung jawab domain *Finance*).
    *   Manajemen ruang kelas digital untuk KBM (tanggung jawab domain *LMS*).

#### Owned Components
*   *Controllers (7):* CategoryController, DashboardController, InventoryController, LoanController, MaintenanceController, RoomController, UsageLogController.
*   *Models (6):* Category, Inventory, Loan, MaintenanceLog, Room, UsageLog.
*   *Services (1):* `InventoryCodeGenerator.php`
*   *Pages (7):* `resources/js/Pages/Sarpar/*`

#### Domain Findings
*   *Finding:* Ditemukan Service Khusus untuk Kode Aset.
    *   *Evidence:* Terdapat kelas `InventoryCodeGenerator.php` untuk merumuskan kode penomoran barang terstandardisasi secara otomatis.
    *   *Confidence:* High.

---

### 3.8 Student Portal Domain
#### Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Penyajian data konsumsi siswa (dashboard portofolio siswa).
    *   Pencatatan tugas produktivitas mandiri yang dibuat oleh siswa untuk dirinya sendiri (`StudentTask`).
*   **Bukan Tanggung Jawab Domain:**
    *   Membuat modifikasi data SPP siswa (tanggung jawab domain *Finance*).
    *   Membuat draf materi KBM baru (tanggung jawab domain *LMS*).
    *   Melakukan perubahan jadwal kelas (tanggung jawab domain *Academic*).

#### Owned Components
*   *Controllers:* None (backend dikelola oleh core controllers `StudentPortalController.php` dan `StudentTaskController.php`).
*   *Models:* None (menggunakan core model `StudentTask.php`).
*   *Pages (6):* `resources/js/Pages/Student/*`

#### Domain Findings
*   *Finding:* Arsitektur Asimetris (Tampilan Modular, Backend Core).
    *   *Evidence:* Presentasi visual Vue dikelompokkan modular di `resources/js/Pages/Student/`, namun backend controller dan modelnya berada di core folder, sementara direktori backend `app/Modules/Student` kosong.
    *   *Confidence:* High.

---

### 3.9 Yayasan Domain
#### Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Registrasi sekolah baru (SD, SMP, SMA) di bawah yayasan Namira.
    *   Registrasi dan penonaktifan akun staf/guru secara global.
    *   Penyediaan switch unit context agar admin dapat berpindah dashboard antar sekolah.
    *   Persetujuan pengajuan izin absen absensi staf/guru.
*   **Bukan Tanggung Jawab Domain:**
    *   Mengatur jadwal harian guru BK di kesiswaan (tanggung jawab domain *Counseling*).
    *   Pemberian nilai tugas siswa (tanggung jawab domain *LMS*).

#### Owned Components
*   *Controllers (9):* AcademicYearController, AttendanceApprovalController, AttendanceDataController, AttendanceLocationController, HolidayController, MonitoringController, SettingController, UnitController, UserController.
*   *Models (4):* AcademicYear, Holiday, SystemSetting, Unit.
*   *Pages (16):* `resources/js/Pages/Yayasan/*`

#### Domain Findings
*   *Finding:* Ditemukan Fitur Switch Unit Context Global.
    *   *Evidence:* Terdapat controller unit dengan route `switch-unit` (`[\App\Modules\Yayasan\Controllers\UnitController::class, 'switch']`) yang memicu perpindahan status sesi unit aktif secara dinamis.
    *   *Confidence:* High.
