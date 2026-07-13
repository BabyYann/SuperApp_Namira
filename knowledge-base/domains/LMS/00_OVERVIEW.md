# LMS Domain Overview

## 1. Domain Overview
*   **Domain Name:** LMS (Learning Management System)
*   **Purpose:** Menyediakan lingkungan belajar mengajar daring bagi guru dan siswa, meliputi penyebaran materi pelajaran, pengumuman kelas online, pembuatan tugas sekolah, pengumpulan jawaban tugas oleh siswa, serta penilaian tugas.
*   **Responsibility:**
    *   Menyediakan forum diskusi / linimasa pengumuman kelas daring (`LmsAnnouncement`).
    *   Memfasilitasi guru mengunggah materi pelajaran (`LmsMaterial`) beserta lampiran file (`LmsMaterialFile`).
    *   Memfasilitasi guru membuat tugas (`LmsAssignment`) beserta batas waktu pengumpulan.
    *   Memfasilitasi siswa mengirimkan lembar jawaban tugas (`LmsSubmission`) beserta lampiran file dokumen/gambar (`LmsSubmissionFile`).
    *   Menghitung rekapitulasi nilai tugas siswa per kelas (gradebook).
*   **Business Objective:** Mendukung pembelajaran hibrida (*hybrid learning*), memudahkan guru mendistribusikan materi belajar dan mengoreksi tugas secara digital, serta memudahkan siswa mengumpulkan tugas dari mana saja.

---

## 2. Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Distribusi materi dan file tugas daring.
    *   Pencatatan tanggal pengumpulan tugas oleh siswa untuk evaluasi keterlambatan.
    *   Penilaian tugas mandiri oleh guru mata pelajaran.
*   **Bukan Tanggung Jawab Domain:**
    *   Pencatatan kehadiran fisik siswa di ruang kelas nyata (tanggung jawab domain *Academic* - ditangani oleh `JournalAttendance`).
    *   Pencatatan kemajuan pencapaian Indikator Ketercapaian Tujuan Pembelajaran (tanggung jawab domain *Academic* - ditangani oleh `LearningObjective`).

---

## 3. Owned Components
*   **Controllers (2):**
    *   `Guru/LmsClassroomController.php`, `Siswa/LmsStudentController.php`
*   **Models (7):**
    *   `LmsAnnouncement.php`, `LmsAssignment.php`, `LmsClassroom.php`, `LmsMaterial.php`, `LmsMaterialFile.php`, `LmsSubmission.php`, `LmsSubmissionFile.php`
*   **Pages (8):**
    *   Vue files under `resources/js/Pages/LMS/`

---

## 4. Owned Entities
*   `LmsClassroom`
*   `LmsAnnouncement`
*   `LmsMaterial`
*   `LmsAssignment`
*   `LmsSubmission`

---

## 5. Shared Components
*   `Student` (Academic Model - data profil siswa sebagai peserta kelas LMS).
*   `Teacher` (Academic Model - data profil guru pembuat kelas dan pemberi nilai).
*   `Classroom` (Academic Model - referensi kelas fisik untuk sinkronisasi kelas daring).
*   `User` (Core Model - autentikasi login guru/siswa).

---

## 6. Incoming Dependencies
*   **Student (Portal):** Dashboard portal siswa mengambil data notifikasi tugas aktif yang belum dikerjakan dari LMS.

---

## 7. Outgoing Dependencies
*   **Academic:** Sangat bergantung pada data modul Akademik untuk memetakan hubungan antara Guru (`Teacher`), Siswa (`Student`), dan Kelas (`Classroom`) agar rincian hak akses forum LMS terbentuk secara otomatis berdasarkan daftar rombongan belajar kelas fisik.
*   **Core:** Membutuhkan model `User` untuk otentikasi login.

---

## 8. Entry Points
*   **Web Routes:** Dideklarasikan di `routes/web.php` di bawah pengelompokan peran guru (`/yayasan/lms/guru`) dan peran siswa (`/student/lms`).
*   **Sidebar Navigation:** Menu Ruang Kelas / LMS pada dashboard guru dan kesiswaan.

---

## 9. Public Interface
*   Daftar pengumuman dan materi terekspos langsung ke akun portal siswa.

---

## 10. Internal Structure
*   `app/Modules/LMS/Controllers/Guru/` untuk fitur administrasi guru (tambah tugas, input nilai).
*   `app/Modules/LMS/Controllers/Siswa/` untuk fitur pengumpulan tugas siswa.
*   `app/Modules/LMS/Models/` model data relasional LMS.
*   `resources/js/Pages/LMS/` antarmuka linimasa materi dan pengumpulan tugas.

---

## 11. Domain Findings
1.  **Pemisahan Alur Kerja Guru dan Siswa di Tingkat Controller:**
    *   *Evidence:* Terdapat subfolder terpisah `Guru` dan `Siswa` pada level controller di backend modul LMS, menunjukkan pemisahan hak akses yang ketat sejak awal pemrosesan request HTTP.
    *   *Confidence:* High.

---

## 12. Unknown Areas
*   Apakah dokumen tugas yang diunggah siswa disimpan di penyimpanan cloud (seperti S3) atau folder local storage (karena `.env` mendefinisikan `FILESYSTEM_DISK=local`).

---

## 13. Confidence
*   **High:** Skema database LMS dan file views terisolasi secara menyeluruh dengan pengelompokan data guru/siswa yang rapi.
