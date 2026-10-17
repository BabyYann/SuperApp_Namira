# SuperApp Namira — Kamus Data Basis Data (Data Dictionary)

**Dokumen Spesifikasi Teknis Basis Data**  
**ID Dokumen:** `DOC-DB-03-DATADICT`  
**Status Audit:** `IMPLEMENTED`  
**Total Tabel Terdokumentasi:** 74 Tabel Fisik  
**Terkait:** [`01-database-overview.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/04-database/01-database-overview.md), [`02-er-diagram.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/04-database/02-er-diagram.md), [`04-relationship-catalog.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/04-database/04-relationship-catalog.md)

---

## 1. Domain 1: Identity, Framework Foundation & Core RBAC

### 1.1 `users`
Tabel entitas akun sentral untuk seluruh personil sistem (admin yayasan, pimpinan unit, guru, staf, wali murid, santri).

| Nama Kolom | Tipe Data | Nullable | Default | Keterangan & Referensi Relasi |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | NO | *Auto Increment* | Primary Key. |
| `unit_id` | `BIGINT UNSIGNED` | YES | `NULL` | FK ke `units.id`. Null untuk akun level yayasan / multi-unit. |
| `name` | `VARCHAR(255)` | NO | - | Nama lengkap resmi pengguna. |
| `email` | `VARCHAR(255)` | NO | - | Alamat email unik (login credential). Unique index. |
| `username` | `VARCHAR(255)` | YES | `NULL` | Username unik opsional untuk login. Unique index. |
| `phone` | `VARCHAR(50)` | YES | `NULL` | Nomor telepon/WhatsApp aktif. Ditambahkan migrasi `2026_09_16_100001`. |
| `password` | `VARCHAR(255)` | NO | - | Password ter-hash menggunakan bcrypt. |
| `profile_photo` | `VARCHAR(255)` | YES | `NULL` | Jalur berkas foto profil avatar di storage publik. |
| `status` | `VARCHAR(50)` | NO | `'active'` | Status akun (`active`, `inactive`, `suspended`). |
| `email_verified_at` | `TIMESTAMP` | YES | `NULL` | Stempel waktu verifikasi email. |
| `remember_token` | `VARCHAR(100)` | YES | `NULL` | Token "Remember Me" otentikasi sesi browser. |
| `created_at` | `TIMESTAMP` | YES | `NULL` | Stempel waktu pembuatan baris. |
| `updated_at` | `TIMESTAMP` | YES | `NULL` | Stempel waktu pembaruan baris terakhir. |
| `deleted_at` | `TIMESTAMP` | YES | `NULL` | Soft delete stempel waktu. |

---

### 1.2 `units`
Tabel unit operasional di bawah Yayasan Namira (misal: TK, SD, SMP, SMA, Daycare, Yayasan Pusat).

| Nama Kolom | Tipe Data | Nullable | Default | Keterangan & Referensi Relasi |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | NO | *Auto Increment* | Primary Key. |
| `name` | `VARCHAR(255)` | NO | - | Nama resmi unit pendidikan/organisasi. |
| `slug` | `VARCHAR(255)` | NO | - | URL slug unik unit (misal: `sma-namira`). Unique index. |
| `unit_type` | `VARCHAR(50)` | NO | `'sekolah'` | Jenis operasional (`sekolah`, `daycare`, `yayasan`). |
| `features` | `JSON` | YES | `NULL` | Feature flags aktif untuk unit (LMS, BK, Tahfidz, Daycare). |
| `principal_id` | `BIGINT UNSIGNED` | YES | `NULL` | FK ke `users.id` (Kepala Sekolah/Pimpinan Unit). |
| `principal_name` | `VARCHAR(255)` | YES | `NULL` | Nama tekstual pimpinan untuk cetak rapor/ijazah. |
| `npsn` | `VARCHAR(50)` | YES | `NULL` | Nomor Pokok Sekolah Nasional. |
| `address` | `TEXT` | YES | `NULL` | Alamat fisik lengkap unit. |
| `phone` | `VARCHAR(50)` | YES | `NULL` | Nomor kontak resmi unit. |
| `email` | `VARCHAR(255)` | YES | `NULL` | Alamat email dinas unit. |
| `website` | `VARCHAR(255)` | YES | `NULL` | URL portal unit sekolah. |
| `work_start_time` | `TIME` | YES | `'07:00:00'` | Batas jam masuk kerja/presensi staf. |
| `work_end_time` | `TIME` | YES | `'16:00:00'` | Batas jam kepulangan dinas staf. |
| `created_at` | `TIMESTAMP` | YES | `NULL` | Stempel waktu pembuatan. |
| `updated_at` | `TIMESTAMP` | YES | `NULL` | Stempel waktu pembaruan. |

---

### 1.3 `academic_years`
Tabel periode tahun akademik/ajaran dan semester aktif.

| Nama Kolom | Tipe Data | Nullable | Default | Keterangan & Referensi Relasi |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | NO | *Auto Increment* | Primary Key. |
| `unit_id` | `BIGINT UNSIGNED` | NO | - | FK ke `units.id`. Scoped per unit. |
| `name` | `VARCHAR(100)` | NO | - | Label tahun (misal: `2025/2026`). |
| `semester` | `ENUM('ganjil','genap')` | NO | - | Semester berjalan. |
| `start_date` | `DATE` | NO | - | Tanggal mulai kalender akademik. |
| `end_date` | `DATE` | NO | - | Tanggal akhir kalender akademik. |
| `is_active` | `TINYINT(1)` | NO | `0` | Flag penanda tahun ajaran berjalan aktif. |
| `created_at` | `TIMESTAMP` | YES | `NULL` | Stempel waktu pembuatan. |
| `updated_at` | `TIMESTAMP` | YES | `NULL` | Stempel waktu pembaruan. |

---

### 1.4 `roles` & `permissions` (Spatie Permission Tables)
Mengelola RBAC berbasis guard `web` atau `sanctum` dengan multi-tenancy `team_id` pada tabel `roles`.

| Tabel | Kolom Kunci | Tipe Data | Keterangan |
| :--- | :--- | :--- | :--- |
| `roles` | `id`, `team_id`, `name`, `guard_name` | `BIGINT`, `BIGINT`, `VARCHAR(255)` | `team_id` merujuk ke `units.id` (atau null untuk peran global). |
| `permissions` | `id`, `name`, `guard_name` | `BIGINT`, `VARCHAR(255)`, `VARCHAR(255)` | Hak akses atomik (misal: `academic.schedule.view`). |
| `model_has_roles` | `role_id`, `model_type`, `model_id`, `team_id` | Composite PK | Asosiasi pengguna ke peran dengan konteks unit. |
| `role_has_permissions` | `permission_id`, `role_id` | Composite PK | Pemetaan hak akses ke peran. |

---

### 1.5 `user_device_tokens` & `personal_access_tokens`
| Nama Tabel | Kolom Utama | Fungsi Teknis |
| :--- | :--- | :--- |
| `user_device_tokens` | `id`, `user_id`, `device_token`, `device_type`, `created_at` | Registrasi token FCM (Firebase Cloud Messaging) mobile app/PWA. |
| `personal_access_tokens` | `id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities` | Token REST API Laravel Sanctum untuk mobile/external integration. |

---

## 2. Domain 2: Academic, Curriculum & Teaching Journals

### 2.1 `teachers`
Menyimpan profil khusus dewan guru pengajar.

| Nama Kolom | Tipe Data | Nullable | Default | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | NO | *Auto Increment* | Primary Key. |
| `user_id` | `BIGINT UNSIGNED` | NO | - | FK ke `users.id` (Relasi 1:1 akun login). |
| `unit_id` | `BIGINT UNSIGNED` | NO | - | FK ke `units.id`. |
| `nip` | `VARCHAR(50)` | YES | `NULL` | Nomor Induk Pegawai unik. |
| `nuptk` | `VARCHAR(50)` | YES | `NULL` | Nomor Unik Pendidik dan Tenaga Kependidikan. |
| `specialization` | `VARCHAR(255)` | YES | `NULL` | Bidang keahlian mata pelajaran. |
| `photo` | `VARCHAR(255)` | YES | `NULL` | Berkas foto profil pengajar. |
| `status` | `VARCHAR(50)` | NO | `'active'` | Status kepegawaian pengajar. |
| `created_at`, `updated_at` | `TIMESTAMP` | YES | `NULL` | Stempel waktu pembuatan dan edit. |

---

### 2.2 `classrooms`
Menyimpan entitas rombel/ruang kelas akademik.

| Nama Kolom | Tipe Data | Nullable | Default | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | NO | *Auto Increment* | Primary Key. |
| `unit_id` | `BIGINT UNSIGNED` | NO | - | FK ke `units.id`. |
| `academic_year_id` | `BIGINT UNSIGNED` | NO | - | FK ke `academic_years.id`. |
| `homeroom_teacher_id` | `BIGINT UNSIGNED` | YES | `NULL` | FK ke `teachers.id` (Wali Kelas). |
| `name` | `VARCHAR(100)` | NO | - | Nama rombel (misal: `X-IPA-1`, `Grade 4A`). |
| `level` | `VARCHAR(20)` | NO | - | Tingkat jenjang kelas (`10`, `11`, `12`, `1`, dsb). |
| `major` | `VARCHAR(100)` | YES | `NULL` | Jurusan/peminatan (misal: `IPA`, `IPS`, `Tahfidz`). |
| `capacity` | `INT` | NO | `30` | Daya tampung maksimum santri/siswa. |

---

### 2.3 `students`
Master data santri / siswa terdaftar.

| Nama Kolom | Tipe Data | Nullable | Default | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | NO | *Auto Increment* | Primary Key. |
| `user_id` | `BIGINT UNSIGNED` | YES | `NULL` | FK ke `users.id` jika siswa memiliki akun login mandiri. |
| `unit_id` | `BIGINT UNSIGNED` | NO | - | FK ke `units.id`. |
| `classroom_id` | `BIGINT UNSIGNED` | YES | `NULL` | FK ke `classrooms.id` (Kelas saat ini). |
| `academic_year_id` | `BIGINT UNSIGNED` | YES | `NULL` | FK ke `academic_years.id` (Tahun masuk/aktif). |
| `nis` | `VARCHAR(50)` | NO | - | Nomor Induk Siswa lokal unit. Unique per unit. |
| `nisn` | `VARCHAR(50)` | YES | `NULL` | Nomor Induk Siswa Nasional Kemdikbud. |
| `name` | `VARCHAR(255)` | NO | - | Nama lengkap santri. |
| `gender` | `ENUM('L','P')` | NO | - | Jenis kelamin (`L` = Laki-laki, `P` = Perempuan). |
| `birth_place` | `VARCHAR(100)` | YES | `NULL` | Tempat lahir. |
| `birth_date` | `DATE` | YES | `NULL` | Tanggal lahir santri. |
| `parent_name` | `VARCHAR(255)` | YES | `NULL` | Nama ayah/ibu/wali murid. |
| `parent_phone` | `VARCHAR(50)` | YES | `NULL` | Nomor WhatsApp kontak utama wali murid. |
| `status` | `VARCHAR(50)` | NO | `'active'` | Status (`active`, `graduated`, `transferred`, `dropped`). |
| `deleted_at` | `TIMESTAMP` | YES | `NULL` | Soft delete timestamp. |

---

### 2.4 `subjects`
Katalog mata pelajaran yang diselenggarakan unit sekolah.

| Nama Kolom | Tipe Data | Nullable | Default | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | NO | *Auto Increment* | Primary Key. |
| `unit_id` | `BIGINT UNSIGNED` | NO | - | FK ke `units.id`. |
| `code` | `VARCHAR(50)` | NO | - | Kode unik mapel (misal: `MAT-SMA-10`). |
| `name` | `VARCHAR(255)` | NO | - | Nama resmi mapel (misal: `Matematika Peminatan`). |
| `category` | `VARCHAR(100)` | YES | `NULL` | Kelompok mapel (Muatan Nasional, Agama, Mulok). |
| `order_number` | `INT` | NO | `0` | Urutan penomoran tampilan rapor. |

---

### 2.5 `class_schedules`
Jadwal pelajaran mingguan rombel per hari dan jam.

| Nama Kolom | Tipe Data | Nullable | Default | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | NO | *Auto Increment* | Primary Key. |
| `classroom_id` | `BIGINT UNSIGNED` | NO | - | FK ke `classrooms.id`. |
| `subject_id` | `BIGINT UNSIGNED` | NO | - | FK ke `subjects.id`. |
| `teacher_id` | `BIGINT UNSIGNED` | NO | - | FK ke `teachers.id`. |
| `academic_year_id` | `BIGINT UNSIGNED` | NO | - | FK ke `academic_years.id`. |
| `day_of_week` | `VARCHAR(20)` | NO | - | Nama hari pelaksanaan (`senin` .. `sabtu`). |
| `start_time` | `TIME` | NO | - | Waktu mulai sesi ajar. |
| `end_time` | `TIME` | NO | - | Waktu selesai sesi ajar. |
| `room` | `VARCHAR(100)` | YES | `NULL` | Ruang fisik (Lab, Kelas, Lapangan). |
| `joint_classrooms` | `JSON` | YES | `NULL` | Daftar ID kelas gabungan dalam sesi bersama. |

---

### 2.6 `chapters` & `learning_objectives`
Struktur kurikulum (Capaian Pembelajaran dan Tujuan Pembelajaran).

| Nama Tabel | Kolom Kunci | Keterangan |
| :--- | :--- | :--- |
| `chapters` | `id`, `subject_id`, `grade_level`, `title`, `order_number` | Bab materi ajar dalam kurikulum merdeka. |
| `learning_objectives` | `id`, `chapter_id`, `code`, `description` | Indikator TP (Tujuan Pembelajaran) spesifik bab. |

---

### 2.7 `teaching_journals`, `journal_attendance`, `journal_learning_objectives`
Pencatatan harian realisasi belajar mengajar oleh guru kelas/mapel.

| Nama Tabel | Kolom Kunci | Keterangan |
| :--- | :--- | :--- |
| `teaching_journals` | `id`, `schedule_id`, `teacher_id`, `date`, `material_summary`, `notes`, `photo_evidence` | Jurnal utama guru saat jam mengajar. |
| `journal_attendance` | `id`, `journal_id`, `student_id`, `status` (`hadir,sakit,izin,alpa`), `notes` | Presensi presisi per jam pelajaran santri. |
| `journal_learning_objectives` | `id`, `journal_id`, `learning_objective_id` | TP kurikulum yang berhasil diajarkan pada sesi ini. |

---

## 3. Domain 3: Presensi, Gate Scanner & Notifikasi Gerbang

### 3.1 `attendance_locations`
Titik koordinat GPS dan radius geofence lokasi presensi karyawan dan guru.

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id`, `unit_id` | `BIGINT UNSIGNED` | Identitas geofence dan unit sekolah. |
| `name` | `VARCHAR(255)` | Label nama lokasi (misal: Kampus Utama Namira). |
| `latitude`, `longitude` | `DECIMAL(10, 8)`, `DECIMAL(11, 8)` | Titik koordinat pusat geofence. |
| `radius_meters` | `INT` | Radius toleransi geofencing (meter). |
| `is_active` | `BOOLEAN` | Status aktif lokasi presensi. |

---

### 3.2 `employee_attendances`
Log riwayat presensi harian guru dan staf sekolah.

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id`, `user_id` | `BIGINT UNSIGNED` | Akun pegawai yang melakukan presensi. |
| `attendance_location_id` | `BIGINT UNSIGNED` | FK ke `attendance_locations.id`. |
| `date` | `DATE` | Tanggal hari kerja. |
| `check_in`, `check_out` | `TIME` | Waktu kedatangan dan kepulangan pegawai. |
| `check_in_lat`, `check_in_lng` | `DECIMAL(10,8)` | Koordinat GPS aktual pegawai saat klik presensi. |
| `check_in_photo` | `VARCHAR(255)` | Foto selfie bukti presensi kehadiran. |
| `status` | `VARCHAR(50)` | `present`, `late`, `absent`, `permit`, `sick`. |
| `late_minutes` | `INT` | Total keterlambatan dalam menit (dihitung otomatis). |

---

### 3.3 `student_checkins`, `student_pickup_requests`, `whatsapp_messages_queue`
| Nama Tabel | Kolom Utama | Fungsi Operasional |
| :--- | :--- | :--- |
| `student_checkins` | `id`, `student_id`, `scanned_by_user_id`, `checked_in_at`, `checked_out_at`, `gate_scanner_id` | Rekaman scan barcode kartu pelajar pada pos gerbang sekolah. |
| `student_pickup_requests` | `id`, `student_id`, `parent_user_id`, `pickup_person_name`, `status`, `requested_at` | Permintaan penjemputan santri oleh orang tua/wali murid via app. |
| `whatsapp_messages_queue` | `id`, `phone_number`, `message`, `status`, `scheduled_at`, `sent_at`, `error_log` | Antrean pengiriman pesan WhatsApp otomatis notifikasi kedatangan santri. |

---

## 4. Domain 4: Keuangan & Tagihan Santri (Finance)

### 4.1 `finance_types` & `finance_accounts`
| Nama Tabel | Kolom Kunci | Keterangan |
| :--- | :--- | :--- |
| `finance_types` | `id`, `unit_id`, `code`, `name`, `type` (`income,expense`), `is_monthly`, `default_amount` | Master pos pembayaran (SPP, DPP, Kegiatan, Seragam). |
| `finance_accounts` | `id`, `unit_id`, `account_number`, `account_name`, `bank_name`, `current_balance` | Akun rekening bank / buku kas penampung yayasan. |

---

### 4.2 `student_bills`, `student_discounts`, `bill_payments`, `transactions`
| Nama Tabel | Kolom Kunci | Keterangan |
| :--- | :--- | :--- |
| `student_bills` | `id`, `student_id`, `finance_type_id`, `academic_year_id`, `bill_code`, `amount`, `discount_amount`, `paid_amount`, `remaining_amount`, `status`, `due_date`, `payment_proof` | Invoice tagihan berkala atau insidental siswa. |
| `student_discounts` | `id`, `student_id`, `finance_type_id`, `discount_percentage`, `discount_nominal`, `reason` | Potongan beasiswa atau keringanan biaya santri. |
| `transactions` | `id`, `unit_id`, `finance_account_id`, `transaction_number`, `type`, `amount`, `transaction_date`, `evidence_file` | Jurnal arus kas masuk/keluar buku besar. |
| `bill_payments` | `id`, `student_bill_id`, `transaction_id`, `amount_paid`, `payment_date`, `payment_method`, `reference_number` | Kwitansi pelunasan cicilan tagihan santri. |

---

## 5. Domain 5: Bimbingan Konseling (BK) & Kesiswaan

| Nama Tabel | Kolom Kunci | Keterangan |
| :--- | :--- | :--- |
| `violation_categories` | `id`, `unit_id`, `name`, `description` | Klasifikasi pelanggaran (Ringan, Sedang, Berat). |
| `violations` | `id`, `student_id`, `violation_category_id`, `recorded_by_user_id`, `incident_date`, `description`, `penalty_points`, `action_taken` | Catatan pelanggaran tata tertib dan poin indisipliner santri. |
| `achievements` | `id`, `student_id`, `created_by_user_id`, `title`, `level` (`sekolah..internasional`), `reward_points`, `achievement_date`, `certificate_file` | Catatan prestasi perlombaan santri dan poin apresiasi. |
| `counseling_sessions` | `id`, `student_id`, `counselor_user_id`, `scheduled_at`, `issue_summary`, `action_plan`, `status` | Notula sesi konseling BK personal atau panggilan santri. |

---

## 6. Domain 6: Sarana Prasarana (Sarpras / Asset Management)

| Nama Tabel | Kolom Kunci | Keterangan |
| :--- | :--- | :--- |
| `sarpar_categories` | `id`, `unit_id`, `code`, `name` | Kategori aset (Elektronik, Meubelair, Mesin, Buku). |
| `sarpar_rooms` | `id`, `unit_id`, `code`, `name`, `building`, `capacity` | Daftar ruangan fisik di lingkungan kampus unit. |
| `sarpar_inventories` | `id`, `category_id`, `room_id`, `item_code`, `item_name`, `brand`, `item_type` (`asset,consumable`), `condition`, `quantity`, `purchase_cost`, `purchase_date` | Master katalog inventaris barang dan sarana sekolah. |
| `sarpar_maintenance_logs`| `id`, `inventory_id`, `maintenance_date`, `cost`, `description`, `vendor_name` | Riwayat pemeliharaan, servis, dan perbaikan aset. |
| `sarpar_loans` | `id`, `inventory_id`, `borrower_user_id`, `borrowed_at`, `due_at`, `returned_at`, `status` | Catatan peminjaman aset oleh guru atau staf. |
| `sarpar_usage_logs` | `id`, `inventory_id`, `user_id`, `usage_date`, `quantity_used`, `purpose` | Log pemakaian barang habis pakai (*consumables*). |
| `sarpar_transfers` | `id`, `inventory_id`, `source_room_id`, `destination_room_id`, `transfer_date`, `notes` | Berita acara mutasi/pemindahan aset antar-ruangan. |
| `sarpar_disposals` | `id`, `inventory_id`, `disposal_date`, `reason`, `disposal_method` | Berita acara penghapusan aset rusak berat/afkir. |

---

## 7. Domain 7: LMS (Learning Management System)

| Nama Tabel | Kolom Kunci | Keterangan |
| :--- | :--- | :--- |
| `lms_classrooms` | `id`, `classroom_id`, `subject_id`, `teacher_id`, `enrollment_code`, `is_active` | Ruang kelas digital e-learning per mapel. |
| `lms_materials` | `id`, `lms_classroom_id`, `title`, `content`, `is_published` | Modul materi kuliah/sekolah daring. |
| `lms_material_files` | `id`, `lms_material_id`, `file_name`, `file_path`, `file_size`, `mime_type` | Lampiran berkas PDF/video modul materi ajar. |
| `lms_assignments` | `id`, `lms_classroom_id`, `title`, `instructions`, `due_date`, `max_score` | Tugas daring siswa dengan tenggat waktu. |
| `lms_submissions` | `id`, `lms_assignment_id`, `student_id`, `submitted_at`, `score`, `teacher_feedback`, `status` (`draft,submitted,graded`) | Pengumpulan tugas santri beserta nilai guru. |
| `lms_submission_files` | `id`, `lms_submission_id`, `file_name`, `file_path`, `file_size` | Lampiran lembar jawaban berkas tugas siswa. |
| `lms_announcements` | `id`, `lms_classroom_id`, `title`, `content`, `is_pinned` | Papan pengumuman virtual dalam ruang kelas LMS. |

---

## 8. Domain 8: Daycare & Early Childhood Care

| Nama Tabel | Kolom Kunci | Keterangan |
| :--- | :--- | :--- |
| `daycare_child_profiles` | `id`, `unit_id`, `parent_user_id`, `full_name`, `nickname`, `birth_date`, `gender`, `allergy_info`, `special_notes`, `photo` | Profil lengkap anak asuh tempat penitipan anak. |
| `daycare_authorized_pickups`| `id`, `child_profile_id`, `name`, `relationship`, `phone`, `identity_card_photo`, `photo`, `is_active` | Daftar wali/orang yang berwenang menjemput anak. |
| `daycare_attendances` | `id`, `child_profile_id`, `date`, `arrival_time`, `departure_time`, `delivered_by`, `picked_up_by`, `status` | Log check-in dan check-out kedatangan harian anak asuh. |
| `daycare_daily_logs` | `id`, `child_profile_id`, `date`, `meals_log`, `naps_log`, `hygiene_log`, `mood_and_behavior`, `notes_to_parent` | Catatan harian aktivitas (makan, tidur, popok, mood). |
| `daycare_growth_records` | `id`, `child_profile_id`, `recorded_date`, `weight_kg`, `height_cm`, `head_circumference_cm`, `nutrition_notes` | Riwayat pemantauan antropometri tumbuh kembang berkala. |
| `daycare_developmental_journals`| `id`, `child_profile_id`, `date`, `milestone_category`, `observation`, `photo_evidence` | Jurnal capaian milestone perkembangan motorik & kognitif. |
| `daycare_caregiver_schedules`| `id`, `unit_id`, `caregiver_user_id`, `day_of_week`, `shift_start`, `shift_end`, `room_assigned` | Jadwal rotasi tugas pengasuh/perawat daycare. |

---

## 9. Domain 9 & 10: SPMB & Ekstrakurikuler

### 9.1 SPMB (Sistem Penerimaan Santri/Siswa Baru)
| Nama Tabel | Kolom Kunci | Keterangan |
| :--- | :--- | :--- |
| `spmb_settings` | `id`, `unit_id`, `academic_year_id`, `wave_name`, `start_date`, `end_date`, `registration_fee`, `is_active` | Konfigurasi gelombang pendaftaran SPMB per unit. |
| `spmb_applicants` | `id`, `spmb_setting_id`, `registration_number`, `full_name`, `email`, `phone`, `gender`, `origin_school`, `status` (`registered,verified,test_passed,accepted,rejected`), `payment_proof` | Formulir calon santri baru pendaftar online. |

---

### 9.2 Ekstrakurikuler
| Nama Tabel | Kolom Kunci | Keterangan |
| :--- | :--- | :--- |
| `extracurriculars` | `id`, `unit_id`, `name`, `description`, `schedule_day`, `schedule_time` | Katalog ekstrakurikuler (Pramuka, Futsal, Robotik, Panahan). |
| `extracurricular_instructors` | `id`, `extracurricular_id`, `teacher_id` | Penugasan instruktur/pelatih ekskul. |
| `extracurricular_members` | `id`, `extracurricular_id`, `student_id`, `joined_date`, `status` | Anggota santri yang terdaftar dalam klub ekskul. |
| `extracurricular_sessions` | `id`, `extracurricular_id`, `session_date`, `activity_summary` | Jadwal temu dan ringkasan latihan rutin ekskul. |
| `extracurricular_attendances`| `id`, `session_id`, `member_id`, `status` (`present,sick,absent`) | Presensi kehadiran santri saat kegiatan ekskul. |
| `extracurricular_grades` | `id`, `member_id`, `academic_year_id`, `grade` (`A,B,C,D`), `description` | Nilai predikat dan deskripsi ekskul untuk rapor resmi. |

---

## 10. Domain 11: Humas, Sistem & Monitoring Framework

| Nama Tabel | Kolom Kunci | Keterangan |
| :--- | :--- | :--- |
| `news` | `id`, `unit_id`, `title`, `slug`, `content`, `cover_image`, `views`, `is_published`, `approved_by` | Warta dan publikasi berita resmi yayasan/unit. |
| `events` | `id`, `unit_id`, `title`, `description`, `start_date`, `end_date`, `location`, `poster`, `is_published` | Agenda kegiatan dan kalender event publik sekolah. |
| `partners` | `id`, `name`, `logo`, `partnership_type`, `website` | Daftar instansi mitra kerja sama strategis Yayasan. |
| `testimonials` | `id`, `name`, `role`, `content`, `photo`, `rating` | Ulasan dan testimoni orang tua, alumni, atau tokoh. |
| `banners` | `id`, `title`, `image_path`, `target_url`, `is_active`, `order_number` | Banner slider visual portal depan aplikasi. |
| `university_destinations`| `id`, `alumni_name`, `university_name`, `major`, `graduation_year`, `pathway` | Jejak alumni masuk perguruan tinggi favorit. |
| `system_settings` | `id`, `key`, `value`, `group` | Konfigurasi global dinamis pasangan key-value sistem. |
| `activity_log` | `id`, `log_name`, `description`, `subject_type`, `subject_id`, `causer_type`, `causer_id`, `properties` | Log audit aktivitas Spatie (siapa merubah apa dan kapan). |
| `notifications_app` | `id`, `user_id`, `title`, `message`, `type`, `is_read`, `read_at` | Notifikasi in-app untuk pengguna di navbar mobile/web. |
| `pulse_*` | `id`, `key`, `timestamp`, `value` | Tabel telemetri kinerja runtime Laravel Pulse. |
| `cache` & `jobs` | `key`, `value`, `expiration` / `id`, `queue`, `payload`, `attempts` | Tabel cache basis data dan worker antrean background jobs. |
