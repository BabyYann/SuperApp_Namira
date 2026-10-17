# SuperApp Namira — Katalog Relasi Basis Data & Eloquent ORM

**Dokumen Spesifikasi Teknis Basis Data**  
**ID Dokumen:** `DOC-DB-04-RELATIONSHIPS`  
**Status Audit:** `IMPLEMENTED`  
**Total Model Eloquent:** 75 Model  
**Terkait:** [`01-database-overview.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/04-database/01-database-overview.md), [`02-er-diagram.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/04-database/02-er-diagram.md), [`03-data-dictionary.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/04-database/03-data-dictionary.md)

---

## 1. Ikhtisar Arsitektur Relasi

SuperApp Namira mengombinasikan dua lapis integritas relasi data:
1. **Physical Database Constraints**: Ditegakkan langsung oleh RDBMS MySQL/MariaDB pada level skema DDL (`FOREIGN KEY ... REFERENCES ... ON DELETE ...`).
2. **Logical Eloquent Relationships**: Didefinisikan dalam kode PHP pada kelas-kelas `App\Models\*` (`hasMany`, `belongsTo`, `belongsToMany`, `morphTo`, `hasOne`).

Ditemukan beberapa kasus di mana relasi logis di Eloquent sengaja tidak menerapkan physical foreign key di level DB demi fleksibilitas mutasi data atau dukungan polimorfik, serta penggunaan kolom JSON (misal `joint_classrooms`).

---

## 2. Katalog Relasi Fisik vs Eloquent (Domain-by-Domain)

### 2.1 Domain Inti: Multi-Tenancy, Identitas & RBAC

| Model Induk (Parent) | Model Anak (Child) | Tipe Relasi | Kunci Fisik (Foreign Key) | Definisi Eloquent | On Delete Behavior | Status Verifikasi |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `Unit` | `User` | 1:N | `users.unit_id` -> `units.id` | `Unit::users()`, `User::unit()` | `SET NULL` / `RESTRICT` | `IMPLEMENTED` |
| `Unit` | `AcademicYear` | 1:N | `academic_years.unit_id` -> `units.id` | `Unit::academicYears()`, `AcademicYear::unit()` | `CASCADE` | `IMPLEMENTED` |
| `User` | `Teacher` | 1:1 | `teachers.user_id` -> `users.id` | `User::teacher()`, `Teacher::user()` | `CASCADE` | `IMPLEMENTED` |
| `User` | `Staff` | 1:1 | `staff.user_id` -> `users.id` | `User::staff()`, `Staff::user()` | `CASCADE` | `IMPLEMENTED` |
| `User` | `Student` | 1:1 / 1:N | `students.user_id` -> `users.id` | `User::student()`, `Student::user()` | `SET NULL` | `IMPLEMENTED` |
| `User` | `UserDeviceToken` | 1:N | `user_device_tokens.user_id` -> `users.id` | `User::deviceTokens()` | `CASCADE` | `IMPLEMENTED` |
| `User` | `PersonalAccessToken` | 1:N Polymorphic | `personal_access_tokens.tokenable_id` | `HasApiTokens::tokens()` | `CASCADE` | `IMPLEMENTED` |

---

### 2.2 Domain Akademik & Kurikulum

| Model Induk (Parent) | Model Anak (Child) | Tipe Relasi | Kunci Fisik (Foreign Key) | Definisi Eloquent | On Delete Behavior | Status Verifikasi |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `Unit` | `Classroom` | 1:N | `classrooms.unit_id` -> `units.id` | `Unit::classrooms()`, `Classroom::unit()` | `CASCADE` | `IMPLEMENTED` |
| `AcademicYear` | `Classroom` | 1:N | `classrooms.academic_year_id` -> `academic_years.id` | `AcademicYear::classrooms()` | `RESTRICT` | `IMPLEMENTED` |
| `Teacher` | `Classroom` | 1:N (Walikelas) | `classrooms.homeroom_teacher_id` -> `teachers.id` | `Classroom::homeroomTeacher()` | `SET NULL` | `IMPLEMENTED` |
| `Classroom` | `Student` | 1:N | `students.classroom_id` -> `classrooms.id` | `Classroom::students()`, `Student::classroom()` | `SET NULL` | `IMPLEMENTED` |
| `Unit` | `Subject` | 1:N | `subjects.unit_id` -> `units.id` | `Unit::subjects()`, `Subject::unit()` | `CASCADE` | `IMPLEMENTED` |
| `Subject` | `Chapter` | 1:N | `chapters.subject_id` -> `subjects.id` | `Subject::chapters()`, `Chapter::subject()` | `CASCADE` | `IMPLEMENTED` |
| `Chapter` | `LearningObjective` | 1:N | `learning_objectives.chapter_id` -> `chapters.id` | `Chapter::learningObjectives()` | `CASCADE` | `IMPLEMENTED` |
| `ClassSchedule` | `TeachingJournal` | 1:N | `teaching_journals.schedule_id` -> `class_schedules.id` | `ClassSchedule::teachingJournals()` | `RESTRICT` | `IMPLEMENTED` |
| `TeachingJournal` | `JournalAttendance` | 1:N | `journal_attendance.journal_id` -> `teaching_journals.id` | `TeachingJournal::attendances()` | `CASCADE` | `IMPLEMENTED` |
| `Student` | `JournalAttendance` | 1:N | `journal_attendance.student_id` -> `students.id` | `Student::journalAttendances()` | `CASCADE` | `IMPLEMENTED` |

---

### 2.3 Domain Presensi & Keamanan Gerbang (Gate Scanner)

| Model Induk (Parent) | Model Anak (Child) | Tipe Relasi | Kunci Fisik (Foreign Key) | Definisi Eloquent | On Delete Behavior | Status Verifikasi |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `Unit` | `AttendanceLocation` | 1:N | `attendance_locations.unit_id` -> `units.id` | `Unit::attendanceLocations()` | `CASCADE` | `IMPLEMENTED` |
| `User` | `EmployeeAttendance` | 1:N | `employee_attendances.user_id` -> `users.id` | `User::employeeAttendances()` | `CASCADE` | `IMPLEMENTED` |
| `AttendanceLocation` | `EmployeeAttendance` | 1:N | `employee_attendances.attendance_location_id` | `EmployeeAttendance::location()` | `SET NULL` | `IMPLEMENTED` |
| `Student` | `StudentCheckin` | 1:N | `student_checkins.student_id` -> `students.id` | `Student::checkins()` | `CASCADE` | `IMPLEMENTED` |
| `Student` | `StudentPickupRequest`| 1:N | `student_pickup_requests.student_id` -> `students.id` | `Student::pickupRequests()` | `CASCADE` | `IMPLEMENTED` |
| `User` (Parent) | `StudentPickupRequest`| 1:N | `student_pickup_requests.parent_user_id` -> `users.id` | `StudentPickupRequest::parent()` | `CASCADE` | `IMPLEMENTED` |

---

### 2.4 Domain Keuangan (Finance)

| Model Induk (Parent) | Model Anak (Child) | Tipe Relasi | Kunci Fisik (Foreign Key) | Definisi Eloquent | On Delete Behavior | Status Verifikasi |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `Unit` | `FinanceType` | 1:N | `finance_types.unit_id` -> `units.id` | `Unit::financeTypes()` | `CASCADE` | `IMPLEMENTED` |
| `Unit` | `FinanceAccount` | 1:N | `finance_accounts.unit_id` -> `units.id` | `Unit::financeAccounts()` | `CASCADE` | `IMPLEMENTED` |
| `Student` | `StudentBill` | 1:N | `student_bills.student_id` -> `students.id` | `Student::bills()`, `StudentBill::student()` | `RESTRICT` | `IMPLEMENTED` |
| `FinanceType` | `StudentBill` | 1:N | `student_bills.finance_type_id` -> `finance_types.id` | `StudentBill::financeType()` | `RESTRICT` | `IMPLEMENTED` |
| `StudentBill` | `BillPayment` | 1:N | `bill_payments.student_bill_id` -> `student_bills.id` | `StudentBill::payments()` | `CASCADE` | `IMPLEMENTED` |
| `Transaction` | `BillPayment` | 1:N | `bill_payments.transaction_id` -> `transactions.id` | `Transaction::billPayments()` | `SET NULL` | `IMPLEMENTED` |
| `FinanceAccount` | `Transaction` | 1:N | `transactions.finance_account_id` -> `finance_accounts.id` | `FinanceAccount::transactions()` | `RESTRICT` | `IMPLEMENTED` |

---

### 2.5 Domain Bimbingan Konseling (BK) & Prestasi

| Model Induk (Parent) | Model Anak (Child) | Tipe Relasi | Kunci Fisik (Foreign Key) | Definisi Eloquent | On Delete Behavior | Status Verifikasi |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `Unit` | `ViolationCategory` | 1:N | `violation_categories.unit_id` -> `units.id` | `Unit::violationCategories()` | `CASCADE` | `IMPLEMENTED` |
| `ViolationCategory` | `Violation` | 1:N | `violations.violation_category_id` -> `violation_categories.id` | `Violation::category()` | `RESTRICT` | `IMPLEMENTED` |
| `Student` | `Violation` | 1:N | `violations.student_id` -> `students.id` | `Student::violations()` | `CASCADE` | `IMPLEMENTED` |
| `Student` | `Achievement` | 1:N | `achievements.student_id` -> `students.id` | `Student::achievements()` | `CASCADE` | `IMPLEMENTED` |
| `Student` | `CounselingSession` | 1:N | `counseling_sessions.student_id` -> `students.id` | `Student::counselingSessions()` | `CASCADE` | `IMPLEMENTED` |

---

### 2.6 Domain Sarana & Prasarana (Sarpras)

| Model Induk (Parent) | Model Anak (Child) | Tipe Relasi | Kunci Fisik (Foreign Key) | Definisi Eloquent | On Delete Behavior | Status Verifikasi |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `Unit` | `SarparCategory` | 1:N | `sarpar_categories.unit_id` -> `units.id` | `SarparCategory::unit()` | `CASCADE` | `IMPLEMENTED` |
| `Unit` | `SarparRoom` | 1:N | `sarpar_rooms.unit_id` -> `units.id` | `SarparRoom::unit()` | `CASCADE` | `IMPLEMENTED` |
| `SarparCategory` | `SarparInventory` | 1:N | `sarpar_inventories.category_id` -> `sarpar_categories.id` | `SarparInventory::category()` | `RESTRICT` | `IMPLEMENTED` |
| `SarparRoom` | `SarparInventory` | 1:N | `sarpar_inventories.room_id` -> `sarpar_rooms.id` | `SarparInventory::room()` | `SET NULL` | `IMPLEMENTED` |
| `SarparInventory` | `SarparMaintenanceLog`| 1:N | `sarpar_maintenance_logs.inventory_id` | `SarparInventory::maintenanceLogs()` | `CASCADE` | `IMPLEMENTED` |
| `SarparInventory` | `SarparLoan` | 1:N | `sarpar_loans.inventory_id` -> `sarpar_inventories.id` | `SarparInventory::loans()` | `RESTRICT` | `IMPLEMENTED` |
| `SarparInventory` | `SarparTransfer` | 1:N | `sarpar_transfers.inventory_id` -> `sarpar_inventories.id` | `SarparInventory::transfers()` | `CASCADE` | `IMPLEMENTED` |
| `SarparInventory` | `SarparDisposal` | 1:1 / 1:N | `sarpar_disposals.inventory_id` -> `sarpar_inventories.id` | `SarparInventory::disposals()` | `CASCADE` | `IMPLEMENTED` |

---

### 2.7 Domain LMS (Learning Management System)

| Model Induk (Parent) | Model Anak (Child) | Tipe Relasi | Kunci Fisik (Foreign Key) | Definisi Eloquent | On Delete Behavior | Status Verifikasi |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `Classroom` | `LmsClassroom` | 1:N | `lms_classrooms.classroom_id` -> `classrooms.id` | `LmsClassroom::classroom()` | `CASCADE` | `IMPLEMENTED` |
| `LmsClassroom` | `LmsMaterial` | 1:N | `lms_materials.lms_classroom_id` -> `lms_classrooms.id` | `LmsClassroom::materials()` | `CASCADE` | `IMPLEMENTED` |
| `LmsMaterial` | `LmsMaterialFile` | 1:N | `lms_material_files.lms_material_id` | `LmsMaterial::files()` | `CASCADE` | `IMPLEMENTED` |
| `LmsClassroom` | `LmsAssignment` | 1:N | `lms_assignments.lms_classroom_id` | `LmsClassroom::assignments()` | `CASCADE` | `IMPLEMENTED` |
| `LmsAssignment` | `LmsSubmission` | 1:N | `lms_submissions.lms_assignment_id` | `LmsAssignment::submissions()` | `CASCADE` | `IMPLEMENTED` |
| `Student` | `LmsSubmission` | 1:N | `lms_submissions.student_id` -> `students.id` | `Student::lmsSubmissions()` | `CASCADE` | `IMPLEMENTED` |
| `LmsSubmission` | `LmsSubmissionFile` | 1:N | `lms_submission_files.lms_submission_id` | `LmsSubmission::files()` | `CASCADE` | `IMPLEMENTED` |

---

### 2.8 Domain Daycare

| Model Induk (Parent) | Model Anak (Child) | Tipe Relasi | Kunci Fisik (Foreign Key) | Definisi Eloquent | On Delete Behavior | Status Verifikasi |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `Unit` | `DaycareChildProfile` | 1:N | `daycare_child_profiles.unit_id` -> `units.id` | `Unit::daycareChildren()` | `CASCADE` | `IMPLEMENTED` |
| `User` (Wali) | `DaycareChildProfile` | 1:N | `daycare_child_profiles.parent_user_id` -> `users.id` | `User::daycareChildren()` | `SET NULL` | `IMPLEMENTED` |
| `DaycareChildProfile` | `DaycareAuthorizedPickup`| 1:N | `daycare_authorized_pickups.child_profile_id` | `DaycareChildProfile::authorizedPickups()` | `CASCADE` | `IMPLEMENTED` |
| `DaycareChildProfile` | `DaycareAttendance` | 1:N | `daycare_attendances.child_profile_id` | `DaycareChildProfile::attendances()` | `CASCADE` | `IMPLEMENTED` |
| `DaycareChildProfile` | `DaycareDailyLog` | 1:N | `daycare_daily_logs.child_profile_id` | `DaycareChildProfile::dailyLogs()` | `CASCADE` | `IMPLEMENTED` |
| `DaycareChildProfile` | `DaycareGrowthRecord` | 1:N | `daycare_growth_records.child_profile_id` | `DaycareChildProfile::growthRecords()` | `CASCADE` | `IMPLEMENTED` |
| `DaycareChildProfile` | `DaycareDevelopmentalJournal`| 1:N | `daycare_developmental_journals.child_profile_id` | `DaycareChildProfile::developmentalJournals()` | `CASCADE` | `IMPLEMENTED` |

---

### 2.9 Domain SPMB & Ekstrakurikuler

| Model Induk (Parent) | Model Anak (Child) | Tipe Relasi | Kunci Fisik (Foreign Key) | Definisi Eloquent | On Delete Behavior | Status Verifikasi |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `Unit` | `SpmbSetting` | 1:N | `spmb_settings.unit_id` -> `units.id` | `Unit::spmbSettings()` | `CASCADE` | `IMPLEMENTED` |
| `SpmbSetting` | `SpmbApplicant` | 1:N | `spmb_applicants.spmb_setting_id` | `SpmbSetting::applicants()` | `CASCADE` | `IMPLEMENTED` |
| `Unit` | `Extracurricular` | 1:N | `extracurriculars.unit_id` -> `units.id` | `Unit::extracurriculars()` | `CASCADE` | `IMPLEMENTED` |
| `Extracurricular` | `ExtracurricularMember`| 1:N | `extracurricular_members.extracurricular_id` | `Extracurricular::members()` | `CASCADE` | `IMPLEMENTED` |
| `Student` | `ExtracurricularMember`| 1:N | `extracurricular_members.student_id` -> `students.id` | `Student::extracurriculars()` | `CASCADE` | `IMPLEMENTED` |
| `Extracurricular` | `ExtracurricularSession`| 1:N | `extracurricular_sessions.extracurricular_id` | `Extracurricular::sessions()` | `CASCADE` | `IMPLEMENTED` |
| `ExtracurricularMember`| `ExtracurricularGrade` | 1:N | `extracurricular_grades.member_id` | `ExtracurricularMember::grades()` | `CASCADE` | `IMPLEMENTED` |

---

## 3. Relasi Virtual (Tanpa Physical Foreign Key / Berbasis JSON)

Terdapat relasi bisnis yang tidak diikat oleh *physical foreign key* di skema MySQL melainkan dikelola secara dinamis di kode aplikasi:

1. **`class_schedules.joint_classrooms` (JSON Array)**:
   - Menyimpan array ID kelas gabungan: `[12, 14]`.
   - Di-handle oleh method `ClassSchedule::getJointClassroomsAttribute()` dan query manual `whereJsonContains`.
2. **`units.features` (JSON Object)**:
   - Menyimpan konfigurasi modul aktif: `{"lms": true, "daycare": false, "counseling": true}`.
   - Dipakai dalam pengecekan middleware perizinan rute tanpa tabel pivot terpisah.
3. **`activity_log` (Spatie Polymorphic)**:
   - Kolom `subject_type` (string kelas model) dan `subject_id` (bigint ID).
   - Menghubungkan log audit ke model mana pun secara dinamis tanpa foreign key fisik.

---

## 4. Evaluasi Kebijakan Integritas Relasional

1. **Perlindungan Terhadap Data Yatim (Orphan Records)**:
   - Seluruh relasi hierarkis anak (misal: *files*, *submissions*, *logs*, *attendances*) memiliki `ON DELETE CASCADE`, memastikan pembersihan otomatis saat entitas induk dihapus.
2. **Perlindungan Rekam Jejak Finansial & Legal**:
   - `student_bills` dan `transactions` membatasi penghapusan (`RESTRICT`), mencegah penghapusan data siswa yang telah memiliki riwayat transaksi keuangan aktif tanpa pembatalan formal.
