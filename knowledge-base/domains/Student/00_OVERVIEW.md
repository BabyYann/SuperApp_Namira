# Student Portal Domain Overview

## 1. Domain Overview
*   **Domain Name:** Student (Portal Siswa)
*   **Purpose:** Menyediakan panel khusus bagi siswa untuk mengkonsumsi informasi pembelajaran BK, status pembayaran sekolah, materi kelas online, dan produktivitas tugas mandiri mereka melalui antarmuka ramah pengguna (terutama perangkat seluler).
*   **Responsibility:**
    *   Menampilkan dashboard jadwal harian siswa.
    *   Menampilkan rekapitulasi poin pelanggaran BK siswa.
    *   Menampilkan histori tagihan keuangan siswa.
    *   Menyediakan akses ke ruang kelas LMS siswa.
    *   Mengelola daftar agenda tugas produktivitas pribadi siswa (`StudentTask`).
*   **Business Objective:** Memberikan kemudahan bagi siswa untuk memantau kewajiban akademis & keuangan mereka, serta menyediakan antarmuka terpadu untuk berinteraksi dengan LMS sekolah dari ponsel.

---

## 2. Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Penyajian data konsumsi siswa (dashboard portofolio siswa).
    *   Pencatatan tugas produktivitas mandiri yang dibuat oleh siswa untuk dirinya sendiri (`StudentTask`).
*   **Bukan Tanggung Jawab Domain:**
    *   Membuat modifikasi data SPP siswa (tanggung jawab domain *Finance*).
    *   Membuat draf materi KBM baru (tanggung jawab domain *LMS*).
    *   Melakukan perubahan jadwal kelas (tanggung jawab domain *Academic*).

---

## 3. Owned Components
*   **Controllers:**
    *   *Status:* **None** (Logika pemrosesan backend dioperasikan lewat core controllers di `app/Http/Controllers/StudentPortalController.php` dan `StudentTaskController.php`).
*   **Models:**
    *   *Status:* **None** (Menggunakan core model `app/Models/StudentTask.php`).
*   **Pages (6):**
    *   Vue files under `resources/js/Pages/Student/` (Academic, Counseling, Dashboard, Finance, Menu, Productivity).

---

## 4. Owned Entities
*   `StudentTask` (Reside in `app/Models/StudentTask.php`)

---

## 5. Shared Components
*   `Student` (Academic Model - profil data akademis siswa).
*   `User` (Core Model - akun otentikasi login).
*   `StudentLayout` (Shared Layout khusus portal siswa).
*   `MobileAppShell` (Shared Layout pembungkus aplikasi mobile web).

---

## 6. Incoming Dependencies
*   *Status:* **None** (Tidak ada modul lain yang memanggil portal siswa, karena ia bertindak sebagai konsumen akhir data).

---

## 7. Outgoing Dependencies
*   **Academic:** Memerlukan model `Student`, `Classroom`, dan `JournalAttendance` untuk menyajikan informasi absensi kelas.
*   **Finance:** Memanggil data `StudentBill` untuk menyajikan tagihan SPP belum lunas.
*   **Counseling:** Memanggil data `Violation` untuk menampilkan histori poin hukuman disiplin.
*   **LMS:** Mengarahkan rute navigasi siswa ke kelas digital LMS.
*   **Core:** Memerlukan model `User` dan `StudentTask`.

---

## 8. Entry Points
*   **Web Routes:** Dideklarasikan di `routes/web.php` di bawah pengelompokan middleware `EnsureStudentAccess` dengan prefix rute `/student/`.
*   **Dashboard Auto-Redirect:** Pengguna dengan peran `siswa` otomatis dialihkan ke halaman portal siswa setelah berhasil login di halaman utama `/dashboard`.

---

## 9. Public Interface
*   Tidak mengekspos API/layanan untuk dikonsumsi modul lain (bertindak sebagai ujung akhir pemrosesan/tampilan).

---

## 10. Internal Structure
*   Modul ini hanya memiliki presentasi visual di `resources/js/Pages/Student/` yang dirancang menyerupai aplikasi mobile (Mobile First). Logika backend dialirkan melalui core Http folder.

---

## 11. Domain Findings
1.  **Arsitektur Asimetris (Tampilan Modular, Backend Core):**
    *   *Evidence:* Meskipun presentasi visual Vue dikelompokkan modular di `resources/js/Pages/Student/`, backend controller dan modelnya tidak diletakkan di `app/Modules/Student` melainkan menyebar di core folder `app/Http/Controllers/StudentPortalController.php`. Direktori `app/Modules/Student` didapati dalam keadaan kosong.
    *   *Confidence:* High.

---

## 12. Unknown Areas
*   Rencana pengembangan lanjutan untuk direktori kosong `app/Modules/Student` (apakah akan diisi logika backend di masa mendatang).

---

## 13. Confidence
*   **High:** Perilaku portal terpetakan jelas melalui aturan pengalihan rute di `routes/web.php` dan middleware kustom `EnsureStudentAccess`.
