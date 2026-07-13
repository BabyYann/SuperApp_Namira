# 04_DOMAIN_RELATIONSHIP_MAP

## Domain Relationship Table

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

---

## Core Shared Domain

Domain bersama (*Core Shared Domain*) adalah pusat pengelolaan data bersama yang tidak terisolasi dalam satu modul tunggal melainkan dikonsumsi oleh seluruh sistem:
1.  **User (`User.php`):** Model pengguna utama untuk autentikasi login guru, staf, admin, dan siswa.
2.  **Permissions (`Spatie Permission`):** Guard peran (*role*) dan izin (*permission*) untuk membatasi hak akses rute di setiap modul.
3.  **Unit (`Unit.php`):** Entitas sekolah (misalnya SD Namira, SMP Namira, SMA Namira) yang mengontrol multi-tenancy unit sekolah yang aktif.
4.  **AcademicYear (`AcademicYear.php`):** Tahun ajaran aktif yang membatasi durasi jadwal pelajaran, tagihan keuangan, dan riwayat absensi kelas.
5.  **AttendanceLocation (`AttendanceLocation.php`):** Titik kordinat kantor/sekolah untuk mencocokkan validasi lokasi absen GPS karyawan.

---

## Domain Communication

Interaksi antar modul dalam sistem monolitik ini dilakukan melalui:
1.  **Impor Model Langsung (Direct Eloquent Imports):** Modul-modul sekunder mengimpor langsung model dari modul Yayasan atau Core. Contoh: Controller keuangan `StudentBillController` mengimpor `App\Modules\Academic\Models\Student` untuk memetakan data SPP siswa.
2.  **Relasi SQL (Foreign Key Constraints):** Database memetakan integritas data antar modul (misal `unit_id` atau `academic_year_id` di hampir setiap tabel transaksi).
3.  **Helper Pihak Ketiga (Global Helper Call):** Pengiriman notifikasi SMS/WhatsApp menggunakan helper bersama `App\Helpers\WhatsAppHelper`.

---

## Domain Ownership Matrix

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

---

## Domain Dependency Graph

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

**Keterangan Grafik:**
*   **Core Shared & Yayasan** berada di tingkat teratas karena modul-modul ini menyediakan basis data dasar (User, Unit, Tahun Ajaran, Penanda Libur) yang dikonsumsi oleh seluruh modul bisnis di bawahnya.
*   **Academic** bertindak sebagai jembatan data siswa/guru bagi modul **LMS**, **Finance**, **Counseling**, dan **Student Portal**. Tanpa entitas akademik, modul-modul ini tidak dapat mengidentifikasi target operasionalnya.
