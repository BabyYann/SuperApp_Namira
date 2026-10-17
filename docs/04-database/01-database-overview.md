# Database Overview (Ikhtisar Arsitektur Basis Data SuperApp Namira)

Dokumen ini mendokumentasikan ikhtisar arsitektur basis data relasional SuperApp Namira yang dirancang di atas MySQL 8.x menggunakan 82 berkas migrasi terkelola Laravel.

---

## 1. Parameter Teknis Basis Data

| Parameter | Spesifikasi Faktual | Keterangan / Sumber Bukti |
|---|---|---|
| **Database Management System (DBMS)** | MySQL 8.x / MariaDB 10.4+ | Konfigurasi `config/database.php` |
| **Storage Engine** | InnoDB | Mendukung transaksi atomik (ACID) & Foreign Key Constraints |
| **Character Set & Collation** | `utf8mb4` / `utf8mb4_unicode_ci` | Mendukung karakter multibyte dan emoji |
| **Jumlah Tabel Utama** | **74 Tabel** | 82 berkas migrasi aktif per 16 Oktober 2026 |
| **Pola Multi-Tenancy** | Shared Database, Shared Schema (Tenant Column Scoped) | Seluruh tabel transaksional memuat kolom `unit_id` |
| **Manajemen Migrasi** | Laravel Migrations (`database/migrations/`) | Version control skema database secara berurutan |

---

## 2. Pengelompokan Kluster Data (Domain Table Clusters)

Basis data terbagi menjadi 10 kluster logis:

```mermaid
graph TD
    subgraph Cluster_Core["1. Fondasi, Autentikasi & Multi-Tenancy (14 Tabel)"]
        users; units; academic_years; holidays; attendance_locations;
        roles; permissions; model_has_roles; model_has_permissions; role_has_permissions;
        activity_log; system_settings; user_device_tokens; whatsapp_messages_queue;
    end

    subgraph Cluster_Academic["2. Akademik, KBM & Siswa (15 Tabel)"]
        students; teachers; classrooms; subjects; class_schedules;
        learning_objectives; chapters; teaching_journals; journal_attendance;
        journal_learning_objectives; class_promotions; student_attendances;
        student_checkins; student_pickup_requests; student_tasks;
    end

    subgraph Cluster_Employee["3. Kepegawaian & Presensi (3 Tabel)"]
        staff; employee_attendances; employee_activity_logs;
    end

    subgraph Cluster_Finance["4. Keuangan & SPP (6 Tabel)"]
        finance_types; finance_accounts; student_bills;
        student_discounts; bill_payments; transactions;
    end

    subgraph Cluster_Counseling["5. Bimbingan Konseling & Prestasi (4 Tabel)"]
        violation_categories; violations; counseling_sessions; achievements;
    end

    subgraph Cluster_Sarpar["6. Sarana Prasarana & Aset (8 Tabel)"]
        sarpar_categories; sarpar_rooms; sarpar_inventories; sarpar_maintenance_logs;
        sarpar_loans; sarpar_usage_logs; sarpar_transfers; sarpar_disposals;
    end

    subgraph Cluster_LMS["7. Learning Management System (7 Tabel)"]
        lms_classrooms; lms_announcements; lms_materials; lms_material_files;
        lms_assignments; lms_submissions; lms_submission_files;
    end

    subgraph Cluster_Daycare["8. Layanan Penitipan Anak Daycare (7 Tabel)"]
        daycare_child_profiles; daycare_attendances; daycare_daily_logs;
        daycare_developmental_journals; daycare_growth_records;
        daycare_authorized_pickups; daycare_caregiver_schedules;
    end

    subgraph Cluster_SPMB["9. Penerimaan Murid Baru SPMB (2 Tabel)"]
        spmb_applicants; spmb_settings;
    end

    subgraph Cluster_Ekskul["10. Ekstrakurikuler (6 Tabel)"]
        extracurriculars; extracurricular_instructors; extracurricular_members;
        extracurricular_sessions; extracurricular_attendances; extracurricular_grades;
    end

    subgraph Cluster_PR["11. Humas & Karir (8 Tabel)"]
        news; events; partners; university_destinations;
        testimonials; banners; job_vacancies; job_applicants;
    end
```

---

## 3. Strategi Integritas & Keamanan Data

1. **Foreign Key Cascading & Restrict:**
   - Relasi master yang kritikal (seperti `unit_id` atau `student_id` pada transaksi keuangan) menerapkan aturan `ON DELETE RESTRICT` untuk mencegah terhapusnya data riwayat keuangan secara tidak sengaja saat data master dibersihkan.
   - Relasi berkas turunan (seperti `lms_material_files` atau `lms_submission_files`) menerapkan `ON DELETE CASCADE` sehingga berkas otomatis terhapus bersama entitas induknya.
2. **Penggunaan Soft Deletes (`deleted_at`):**
   - Diterapkan pada master data penting: `students`, `teachers`, `classrooms`, `inventories`, dan `users` untuk memungkinkan pemulihan data (*restore*) jika terjadi kesalahan penghapusan oleh pengguna.
3. **Pemberian Indeks Performa:**
   - Seluruh foreign key (`unit_id`, `academic_year_id`, `classroom_id`, `student_id`, `teacher_id`) memiliki indeks B-Tree.
   - Indeks gabungan (*composite index*) diterapkan pada tabel berbobot transaksi tinggi: contoh pada `employee_attendances` (`user_id`, `date`) dan `student_bills` (`student_id`, `academic_year_id`, `status`).
