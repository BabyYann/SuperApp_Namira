# Academic Domain Overview

## 1. Domain Overview
*   **Domain Name:** Academic
*   **Purpose:** Mengelola seluruh data akademis sekolah, meliputi pembagian kelas, data guru dan siswa, jadwal pelajaran, jurnal mengajar guru, pencatatan kehadiran siswa, serta sistem kenaikan kelas.
*   **Responsibility:**
    *   Mengatur pembagian siswa ke dalam kelas (`Classroom`).
    *   Mengelola jadwal mengajar guru dan jadwal pelajaran kelas (`ClassSchedule`).
    *   Mencatat aktivitas jurnal mengajar dan kehadiran guru (`TeachingJournal`).
    *   Mencatat dan merekapitulasi kehadiran harian siswa (`JournalAttendance`).
    *   Mengatur kurikulum dasar berupa bab pelajaran, indikator ketercapaian tujuan pembelajaran (`Chapter`, `LearningObjective`).
    *   Mengelola riwayat dan kelulusan/kenaikan kelas siswa (`ClassPromotion`).
*   **Business Objective:** Menyediakan satu titik kendali atas manajemen data pendidikan, jadwal pelajaran, pencatatan jurnal mengajar guru, dan rekap absensi kelas yang akurat dan teratur untuk seluruh unit pendidikan di bawah yayasan.

---

## 2. Domain Boundary
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

---

## 3. Owned Components
*   **Controllers (9):**
    *   `ClassPromotionController.php`, `ClassroomController.php`, `LearningObjectiveController.php`, `ScheduleController.php`, `StudentAttendanceController.php`, `StudentController.php`, `SubjectController.php`, `TeacherController.php`, `TeachingJournalController.php`
*   **Models (10):**
    *   `Chapter.php`, `ClassPromotion.php`, `Classroom.php`, `ClassSchedule.php`, `JournalAttendance.php`, `LearningObjective.php`, `Student.php`, `Subject.php`, `Teacher.php`, `TeachingJournal.php`
*   **Pages (18):**
    *   Vue files under `resources/js/Pages/Academic/`

---

## 4. Owned Entities
*   `Student`
*   `Teacher`
*   `Subject`
*   `Classroom`
*   `ClassSchedule`
*   `TeachingJournal`
*   `LearningObjective`
*   `ClassPromotion`

---

## 5. Shared Components
*   `User` (Core Model - dihubungkan ke data login Guru dan Siswa).
*   `Unit` (Yayasan Model - untuk memilah kepemilikan kelas berdasarkan sekolah).
*   `AcademicYear` (Yayasan Model - untuk menyaring tahun ajaran aktif).

---

## 6. Incoming Dependencies
*   **LMS:** Mengonsumsi kelas akademik (`Classroom`) untuk mendistribusikan materi/tugas, serta data siswa/guru.
*   **Finance:** Menggunakan data siswa (`Student`) untuk menghasilkan rincian tagihan SPP dan diskon.
*   **Student (Portal):** Mengambil data jadwal kelas, jurnal mengajar, dan absensi siswa untuk ditampilkan ke panel siswa.

---

## 7. Outgoing Dependencies
*   **Yayasan:** Membutuhkan data unit sekolah (`Unit`) dan tahun ajaran (`AcademicYear`) untuk inisialisasi kelas dan filter data jadwal pelajaran.
*   **Core:** Membutuhkan model `User` untuk mengaitkan profil Guru/Siswa ke data autentikasi login.

---

## 8. Entry Points
*   **Web Routes:** Dideklarasikan dalam `routes/web.php` di bawah prefix `/yayasan/teachers`, `/yayasan/classrooms`, dan `/yayasan/student-attendance`.
*   **Sidebar Navigation:** Menu navigasi dashboard untuk Guru, Staf Kurikulum, dan Administrator Yayasan.

---

## 9. Public Interface
*   `App\Modules\Academic\Models\Student` (Diimpor oleh LMS dan Finance).
*   `App\Modules\Academic\Models\Teacher` (Diimpor oleh LMS).
*   `App\Modules\Academic\Models\Classroom` (Diimpor oleh LMS).

---

## 10. Internal Structure
Modul terorganisasi secara MVC standard:
*   `app/Modules/Academic/Controllers/` berisi penangan request HTTP.
*   `app/Modules/Academic/Models/` berisi representasi data tabel akademis.
*   `resources/js/Pages/Academic/` berisi antarmuka kelola data.

---

## 11. Domain Findings
1.  **Ditemukan Duplikasi File Kelas Akademik di Luar Modul:**
    *   *Evidence:* File `app/Models/Modules/Academic/Models/Subject.php` dan `app/Http/Controllers/Modules/Academic/Controllers/SubjectController.php` ditemukan di luar struktur direktori modul yang semestinya.
    *   *Confidence:* High.
2.  **Sistem Penjadwalan Terintegrasi Kalender Yayasan:**
    *   *Evidence:* Relasi antara `ClassSchedule` dengan model `AcademicYear` dan `Unit` untuk isolasi multi-tenant data sekolah.
    *   *Confidence:* High.

---

## 12. Unknown Areas
*   Porsi otomatisasi pengalihan data siswa saat tahun ajaran baru berganti (apakah ada seeder/cron kenaikan kelas masal yang belum dipetakan).

---

## 13. Confidence
*   **High:** Struktur entitas utama dan boundary fungsional sangat jelas terdokumentasi dalam implementasi controller dan model.
