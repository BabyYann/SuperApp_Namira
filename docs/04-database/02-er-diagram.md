# SuperApp Namira — Entity Relationship Diagram (ERD)

**Dokumen Kontrak Teknis Basis Data**  
**ID Dokumen:** `DOC-DB-02-ERD`  
**Status Audit:** `IMPLEMENTED`  
**Total Tabel:** 74 Tabel Fisik (11 Domain Bisnis)  
**Terkait:** [`01-database-overview.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/04-database/01-database-overview.md), [`03-data-dictionary.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/04-database/03-data-dictionary.md), [`04-relationship-catalog.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/04-database/04-relationship-catalog.md)

---

## 1. Ringkasan Eksekutif & Struktur Domain

Arsitektur basis data SuperApp Namira v2 Enterprise dirancang sebagai *multi-tenant shared schema* berbasis `unit_id` (*school unit / foundation unit*) dan perpanjangan Spatie Teams `team_id`. Sebanyak 74 tabel dikelompokkan ke dalam 11 domain logis untuk memisahkan bounded context tanpa mengorbankan integritas relasional antar-modul.

```mermaid
graph TD
    subgraph Foundation ["01. Identity & Multi-Tenancy Core"]
        U[units]
        AY[academic_years]
        US[users]
        RL[roles & permissions]
    end

    subgraph AcademicHR ["02. Academic, Curriculum & Staff"]
        TCH[teachers]
        STF[staff]
        STU[students]
        CLS[classrooms]
        SUB[subjects]
        SCH[class_schedules]
        JRN[teaching_journals]
    end

    subgraph GateAtt ["03. Attendance & Gate Scanner"]
        LOC[attendance_locations]
        EATT[employee_attendances]
        SATT[student_attendances]
        CHK[student_checkins]
        PCK[student_pickup_requests]
    end

    subgraph Fin ["04. Financial Management"]
        FT[finance_types]
        FA[finance_accounts]
        SB[student_bills]
        TR[transactions]
        BP[bill_payments]
    end

    subgraph Counsel ["05. Counseling & BK"]
        VC[violation_categories]
        VIO[violations]
        ACH[achievements]
        CS[counseling_sessions]
    end

    subgraph Assets ["06. Sarpras (Asset Management)"]
        SC[sarpar_categories]
        SR[sarpar_rooms]
        SI[sarpar_inventories]
        ML[sarpar_maintenance_logs]
        SL[sarpar_loans]
    end

    subgraph E-Learning ["07. LMS"]
        LC[lms_classrooms]
        LM[lms_materials]
        LA[lms_assignments]
        LS[lms_submissions]
    end

    subgraph DaycareDomain ["08. Daycare Management"]
        DCP[daycare_child_profiles]
        DAP[daycare_authorized_pickups]
        DCA[daycare_attendances]
        DCL[daycare_daily_logs]
    end

    subgraph AdmissionExtra ["09. SPMB & Extracurricular"]
        SPM[spmb_applicants]
        EXT[extracurriculars]
        EXM[extracurricular_members]
    end

    U --> AcademicHR
    U --> GateAtt
    U --> Fin
    U --> Counsel
    U --> Assets
    U --> E-Learning
    U --> DaycareDomain
    U --> AdmissionExtra
```

---

## 2. Diagram ER Domain 1: Identity, Multi-Tenancy & Access Control

Domain ini mengatur autentikasi terpadu pengguna, asosiasi unit organisasi (Yayasan, SMA, SMP, SD, TK, Daycare), tahun ajaran aktif, dan kontrol akses berbasis peran (RBAC) dengan pemisahan konteks `team_id`.

```mermaid
erDiagram
    units ||--o{ users : "has many"
    units ||--o{ academic_years : "scopes"
    units ||--o{ roles : "scoped through team_id"
    users ||--o{ model_has_roles : "assigned"
    roles ||--o{ role_has_permissions : "contains"
    permissions ||--o{ role_has_permissions : "granted to"
    users ||--o{ user_device_tokens : "owns"
    users ||--o{ personal_access_tokens : "generates"
    users ||--o{ activity_log : "triggers"

    units {
        bigint id PK
        string name
        string slug
        string address
        string phone
        string email
        string principal_name
        bigint principal_id FK
        enum unit_type "sekolah, daycare, yayasan"
        json features
        string work_start_time
        string work_end_time
        timestamps created_at
    }

    academic_years {
        bigint id PK
        bigint unit_id FK
        string name
        enum semester "ganjil, genap"
        date start_date
        date end_date
        boolean is_active
        timestamps created_at
    }

    users {
        bigint id PK
        bigint unit_id FK
        string name
        string email
        string username
        string password
        string phone
        string profile_photo
        string status
        timestamp email_verified_at
        timestamps created_at
    }

    roles {
        bigint id PK
        bigint team_id FK
        string name
        string guard_name
        timestamps created_at
    }

    permissions {
        bigint id PK
        string name
        string guard_name
        timestamps created_at
    }

    user_device_tokens {
        bigint id PK
        bigint user_id FK
        string device_token
        string device_type
        timestamps created_at
    }
```

---

## 3. Diagram ER Domain 2: Academic, Curriculum & Teaching Journals

Mengelola struktur kelas, registrasi santri/siswa, dewan guru, mata pelajaran, target capaian (CP/TP), bab ajar, jadwal sesi, serta jurnal kegiatan belajar mengajar harian.

```mermaid
erDiagram
    units ||--o{ classrooms : "houses"
    academic_years ||--o{ classrooms : "scopes"
    classrooms ||--o{ students : "contains"
    teachers ||--o{ classrooms : "serves as walikelas"
    teachers ||--o{ class_schedules : "teaches"
    subjects ||--o{ class_schedules : "scheduled"
    classrooms ||--o{ class_schedules : "assigned"
    academic_years ||--o{ class_schedules : "valid for"
    subjects ||--o{ chapters : "divided into"
    chapters ||--o{ learning_objectives : "contains"
    class_schedules ||--o{ teaching_journals : "logged in"
    teaching_journals ||--o{ journal_attendance : "tracks student presence"
    teaching_journals ||--o{ journal_learning_objectives : "covers objectives"
    class_schedules ||--o{ student_tasks : "assigned in"

    teachers {
        bigint id PK
        bigint user_id FK
        bigint unit_id FK
        string nip
        string nuptk
        string specialization
        string photo
        string status
    }

    classrooms {
        bigint id PK
        bigint unit_id FK
        bigint academic_year_id FK
        bigint homeroom_teacher_id FK
        string name
        string level
        string major
        integer capacity
    }

    students {
        bigint id PK
        bigint user_id FK
        bigint unit_id FK
        bigint classroom_id FK
        bigint academic_year_id FK
        string nis
        string nisn
        string gender
        date birth_date
        string parent_name
        string parent_phone
        string photo
        string status
    }

    subjects {
        bigint id PK
        bigint unit_id FK
        string code
        string name
        integer credits
    }

    class_schedules {
        bigint id PK
        bigint classroom_id FK
        bigint subject_id FK
        bigint teacher_id FK
        bigint academic_year_id FK
        enum day_of_week "senin..sabtu"
        time start_time
        time end_time
        string room
        json joint_classrooms
    }

    teaching_journals {
        bigint id PK
        bigint schedule_id FK
        bigint teacher_id FK
        date date
        text material_summary
        text notes
        string photo_evidence
    }

    journal_attendance {
        bigint id PK
        bigint journal_id FK
        bigint student_id FK
        enum status "hadir, sakit, izin, alpa"
        string notes
    }
```

---

## 4. Diagram ER Domain 3: Attendance, Tracking & Gate Scanner

Menangani geofencing presensi staf dan siswa, verifikasi barcode/QR scanner gerbang sekolah, sinkronisasi kehadiran penjemputan, dan antrean pesan WhatsApp notifikasi wali.

```mermaid
erDiagram
    units ||--o{ attendance_locations : "defines geofences"
    users ||--o{ employee_attendances : "records checkin"
    attendance_locations ||--o{ employee_attendances : "validated by"
    students ||--o{ student_attendances : "recorded"
    students ||--o{ student_checkins : "scanned at gate"
    students ||--o{ student_pickup_requests : "pick up claimed"
    users ||--o{ student_pickup_requests : "requested by parent"
    student_checkins ||--o{ whatsapp_messages_queue : "triggers notification"

    attendance_locations {
        bigint id PK
        bigint unit_id FK
        string name
        decimal latitude
        decimal longitude
        integer radius_meters
        boolean is_active
    }

    employee_attendances {
        bigint id PK
        bigint user_id FK
        bigint attendance_location_id FK
        date date
        time check_in
        time check_out
        decimal check_in_lat
        decimal check_in_lng
        string check_in_photo
        enum status "present, late, absent, permit, sick"
        integer late_minutes
    }

    student_attendances {
        bigint id PK
        bigint student_id FK
        bigint classroom_id FK
        date date
        enum status "present, sick, permit, absent"
        text notes
    }

    student_checkins {
        bigint id PK
        bigint student_id FK
        bigint scanned_by_user_id FK
        datetime checked_in_at
        datetime checked_out_at
        string gate_scanner_id
        string status
    }

    student_pickup_requests {
        bigint id PK
        bigint student_id FK
        bigint parent_user_id FK
        string pickup_person_name
        string pickup_person_relation
        string status "pending, approved, completed, cancelled"
        datetime requested_at
    }

    whatsapp_messages_queue {
        bigint id PK
        string phone_number
        text message
        string status "pending, sent, failed"
        datetime scheduled_at
        datetime sent_at
        text error_log
    }
```

---

## 5. Diagram ER Domain 4: Financial Management & Student Billing

Mendukung tata kelola akun buku besar (COA), tagihan santri (SPP/Infaq), diskon afirmasi/saudara kandung, pencatatan transaksi kas/bank, dan rekonsiliasi pembayaran.

```mermaid
erDiagram
    units ||--o{ finance_types : "categorizes"
    units ||--o{ finance_accounts : "manages accounts"
    finance_types ||--o{ student_bills : "bill category"
    students ||--o{ student_bills : "invoiced to"
    academic_years ||--o{ student_bills : "billed period"
    students ||--o{ student_discounts : "receives discount"
    finance_types ||--o{ student_discounts : "applicable to"
    finance_accounts ||--o{ transactions : "logged under"
    student_bills ||--o{ bill_payments : "settled via"
    transactions ||--o{ bill_payments : "financial record"

    finance_types {
        bigint id PK
        bigint unit_id FK
        string code
        string name
        enum type "income, expense"
        boolean is_monthly
        decimal default_amount
    }

    finance_accounts {
        bigint id PK
        bigint unit_id FK
        string account_number
        string account_name
        string bank_name
        decimal initial_balance
        decimal current_balance
    }

    student_bills {
        bigint id PK
        bigint student_id FK
        bigint finance_type_id FK
        bigint academic_year_id FK
        string bill_code
        decimal amount
        decimal discount_amount
        decimal paid_amount
        decimal remaining_amount
        enum status "unpaid, partial, paid"
        date due_date
        string payment_proof
    }

    transactions {
        bigint id PK
        bigint unit_id FK
        bigint finance_account_id FK
        string transaction_number
        enum type "income, expense, transfer"
        decimal amount
        date transaction_date
        text description
        string evidence_file
    }

    bill_payments {
        bigint id PK
        bigint student_bill_id FK
        bigint transaction_id FK
        decimal amount_paid
        date payment_date
        string payment_method
        string reference_number
    }
```

---

## 6. Diagram ER Domain 5: Counseling, Discipline & Student Achievements (BK)

Mengelola buku catatan pelanggaran siswa, skema poin tata tertib, catatan prestasi akademik/non-akademik, serta sesi konseling bimbingan konseling.

```mermaid
erDiagram
    units ||--o{ violation_categories : "defines categories"
    violation_categories ||--o{ violations : "groups"
    students ||--o{ violations : "committed by"
    users ||--o{ violations : "recorded by"
    students ||--o{ achievements : "achieved by"
    users ||--o{ achievements : "verified by"
    students ||--o{ counseling_sessions : "counseled"
    users ||--o{ counseling_sessions : "conducted by counselor"

    violation_categories {
        bigint id PK
        bigint unit_id FK
        string name
        text description
    }

    violations {
        bigint id PK
        bigint student_id FK
        bigint violation_category_id FK
        bigint recorded_by_user_id FK
        date incident_date
        text description
        integer penalty_points
        string action_taken
    }

    achievements {
        bigint id PK
        bigint student_id FK
        bigint created_by_user_id FK
        string title
        enum level "sekolah, kecamatan, kota, provinsi, nasional, internasional"
        integer reward_points
        date achievement_date
        string certificate_file
    }

    counseling_sessions {
        bigint id PK
        bigint student_id FK
        bigint counselor_user_id FK
        datetime scheduled_at
        text issue_summary
        text action_plan
        enum status "scheduled, ongoing, resolved"
    }
```

---

## 7. Diagram ER Domain 6: Facilities & Asset Management (Sarpras)

Menangani inventarisasi aset, kodifikasi barang, penempatan ruangan, siklus pemeliharaan berkala, peminjaman aset, mutasi antar-unit, dan penghapusan aset afkir.

```mermaid
erDiagram
    units ||--o{ sarpar_categories : "classifies"
    units ||--o{ sarpar_rooms : "houses rooms"
    sarpar_categories ||--o{ sarpar_inventories : "categorizes"
    sarpar_rooms ||--o{ sarpar_inventories : "located in"
    sarpar_inventories ||--o{ sarpar_maintenance_logs : "maintained"
    sarpar_inventories ||--o{ sarpar_loans : "borrowed"
    users ||--o{ sarpar_loans : "borrower user"
    sarpar_inventories ||--o{ sarpar_usage_logs : "usage logged"
    sarpar_inventories ||--o{ sarpar_transfers : "relocated"
    sarpar_inventories ||--o{ sarpar_disposals : "written off"

    sarpar_categories {
        bigint id PK
        bigint unit_id FK
        string code
        string name
    }

    sarpar_rooms {
        bigint id PK
        bigint unit_id FK
        string code
        string name
        string building
        integer capacity
    }

    sarpar_inventories {
        bigint id PK
        bigint category_id FK
        bigint room_id FK
        string item_code
        string item_name
        string brand
        enum item_type "consumable, asset"
        enum condition "good, minor_damage, heavy_damage"
        integer quantity
        decimal purchase_cost
        date purchase_date
    }

    sarpar_maintenance_logs {
        bigint id PK
        bigint inventory_id FK
        date maintenance_date
        decimal cost
        text description
        string vendor_name
    }

    sarpar_loans {
        bigint id PK
        bigint inventory_id FK
        bigint borrower_user_id FK
        datetime borrowed_at
        datetime due_at
        datetime returned_at
        string status "borrowed, returned, overdue"
    }

    sarpar_transfers {
        bigint id PK
        bigint inventory_id FK
        bigint source_room_id FK
        bigint destination_room_id FK
        date transfer_date
        text notes
    }

    sarpar_disposals {
        bigint id PK
        bigint inventory_id FK
        date disposal_date
        string reason
        string disposal_method
    }
```

---

## 8. Diagram ER Domain 7: Learning Management System (LMS)

Mengelola kelas virtual terintegrasi, materi belajar daring berkas multimedia, tugas interaktif, submisi file siswa, dan pengumuman forum kelas.

```mermaid
erDiagram
    classrooms ||--o{ lms_classrooms : "maps to"
    subjects ||--o{ lms_classrooms : "subject context"
    teachers ||--o{ lms_classrooms : "managed by instructor"
    lms_classrooms ||--o{ lms_materials : "hosts materials"
    lms_materials ||--o{ lms_material_files : "contains attachments"
    lms_classrooms ||--o{ lms_assignments : "gives assignments"
    lms_assignments ||--o{ lms_submissions : "submitted by students"
    students ||--o{ lms_submissions : "student submitter"
    lms_submissions ||--o{ lms_submission_files : "uploaded files"
    lms_classrooms ||--o{ lms_announcements : "broadcasts news"

    lms_classrooms {
        bigint id PK
        bigint classroom_id FK
        bigint subject_id FK
        bigint teacher_id FK
        string enrollment_code
        boolean is_active
    }

    lms_materials {
        bigint id PK
        bigint lms_classroom_id FK
        string title
        text content
        boolean is_published
    }

    lms_assignments {
        bigint id PK
        bigint lms_classroom_id FK
        string title
        text instructions
        datetime due_date
        integer max_score
    }

    lms_submissions {
        bigint id PK
        bigint lms_assignment_id FK
        bigint student_id FK
        datetime submitted_at
        decimal score
        text teacher_feedback
        enum status "draft, submitted, graded"
    }
```

---

## 9. Diagram ER Domain 8: Daycare & Early Childhood Care

Dirancang khusus untuk unit Daycare, mengelola profil anak asuh lengkap, otoritas penjemput resmi (dengan foto/KTP), log kegiatan harian (makan, tidur, BAB/BAK, stimulasi), rekaman tumbuh kembang antropometri, dan jurnal perkembangan.

```mermaid
erDiagram
    units ||--o{ daycare_child_profiles : "enrolled in"
    users ||--o{ daycare_child_profiles : "parent user"
    daycare_child_profiles ||--o{ daycare_authorized_pickups : "authorizes"
    daycare_child_profiles ||--o{ daycare_attendances : "checks in"
    daycare_child_profiles ||--o{ daycare_daily_logs : "daily activity"
    daycare_child_profiles ||--o{ daycare_growth_records : "monitored"
    daycare_child_profiles ||--o{ daycare_developmental_journals : "journaled"
    units ||--o{ daycare_caregiver_schedules : "schedules caregivers"
    users ||--o{ daycare_caregiver_schedules : "caregiver staff"

    daycare_child_profiles {
        bigint id PK
        bigint unit_id FK
        bigint parent_user_id FK
        string full_name
        string nickname
        date birth_date
        enum gender "laki_laki, perempuan"
        text allergy_info
        text special_notes
        string photo
    }

    daycare_authorized_pickups {
        bigint id PK
        bigint child_profile_id FK
        string name
        string relationship
        string phone
        string identity_card_photo
        string photo
        boolean is_active
    }

    daycare_attendances {
        bigint id PK
        bigint child_profile_id FK
        date date
        time arrival_time
        time departure_time
        string delivered_by
        string picked_up_by
        enum status "present, absent, sick"
    }

    daycare_daily_logs {
        bigint id PK
        bigint child_profile_id FK
        date date
        json meals_log
        json naps_log
        json hygiene_log
        text mood_and_behavior
        text notes_to_parent
    }

    daycare_growth_records {
        bigint id PK
        bigint child_profile_id FK
        date recorded_date
        decimal weight_kg
        decimal height_cm
        decimal head_circumference_cm
        text nutrition_notes
    }
```

---

## 10. Diagram ER Domain 9 & 10: SPMB & Extracurricular Activities

Mengatur penerimaan santri/siswa baru secara mandiri per unit sekolah dan pendaftaran serta penilaian kegiatan ekstrakurikuler.

```mermaid
erDiagram
    units ||--o{ spmb_settings : "configures"
    academic_years ||--o{ spmb_settings : "academic batch"
    spmb_settings ||--o{ spmb_applicants : "received applicants"
    units ||--o{ extracurriculars : "offers"
    teachers ||--o{ extracurricular_instructors : "instructs"
    extracurriculars ||--o{ extracurricular_instructors : "assigned to"
    extracurriculars ||--o{ extracurricular_members : "enrolled members"
    students ||--o{ extracurricular_members : "joins"
    extracurriculars ||--o{ extracurricular_sessions : "schedules meet"
    extracurricular_sessions ||--o{ extracurricular_attendances : "takes attendance"
    extracurricular_members ||--o{ extracurricular_attendances : "member attendance"
    extracurricular_members ||--o{ extracurricular_grades : "graded"

    spmb_settings {
        bigint id PK
        bigint unit_id FK
        bigint academic_year_id FK
        string wave_name
        date start_date
        date end_date
        decimal registration_fee
        boolean is_active
    }

    spmb_applicants {
        bigint id PK
        bigint spmb_setting_id FK
        string registration_number
        string full_name
        string email
        string phone
        enum status "registered, verified, test_passed, accepted, rejected"
        string payment_proof
    }

    extracurriculars {
        bigint id PK
        bigint unit_id FK
        string name
        text description
        string schedule_day
        time schedule_time
    }

    extracurricular_members {
        bigint id PK
        bigint extracurricular_id FK
        bigint student_id FK
        date joined_date
        enum status "active, inactive"
    }

    extracurricular_grades {
        bigint id PK
        bigint member_id FK
        bigint academic_year_id FK
        string grade "A, B, C, D"
        text description
    }
```

---

## 11. Diagram Relasi Lintas-Domain Komprehensif (Composite Cross-Domain)

Merangkum simpul-simpul integrasi utama (*nexus tables*) yang menghubungkan seluruh subsistem aplikasi:

```mermaid
graph LR
    subgraph CoreNexus ["Core Nexus Entities"]
        U["units (Unit ID)"]
        USR["users (User ID)"]
        AY["academic_years (Year ID)"]
    end

    subgraph AcademicNexus ["Academic Nexus"]
        STU["students"]
        TCH["teachers"]
        CLS["classrooms"]
    end

    subgraph OperationalConsumers ["Downstream Operational Modules"]
        BILL["student_bills (Finance)"]
        GATE["student_checkins (Gate / WA)"]
        BK["violations & achievements (BK)"]
        LMS["lms_submissions (LMS)"]
        EXTRA["extracurricular_members (Extra)"]
        DAYC["daycare_child_profiles (Daycare)"]
    end

    U --> USR
    U --> AY
    U --> CLS
    USR --> TCH
    USR --> STU
    CLS --> STU
    AY --> CLS

    STU --> BILL
    STU --> GATE
    STU --> BK
    STU --> LMS
    STU --> EXTRA
    USR --> DAYC
```

---

## 12. Rekapitulasi Integritas Relasional

1. **Foreign Key Enforcement**: Mayoritas foreign key menggunakan `ON DELETE CASCADE` untuk data hierarkis anak (seperti `journal_attendance` ke `teaching_journals`), dan `ON DELETE RESTRICT` atau `SET NULL` pada entitas utama (seperti `unit_id`, `classroom_id`).
2. **Soft Deletes**: Tabel utama seperti `users`, `students`, `teachers`, `sarpar_inventories` menggunakan soft deletes (`deleted_at`) guna melindungi riwayat audit keuangan dan legal akademik.
3. **Multi-tenancy Scoping**: Semua query kritis wajib membatasi relasi berbasis `unit_id` atau `team_id` melalui global scope atau Eloquent service layer.
