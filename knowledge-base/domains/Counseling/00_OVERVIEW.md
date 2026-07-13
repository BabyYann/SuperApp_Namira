# Counseling Domain Overview

## 1. Domain Overview
*   **Domain Name:** Counseling
*   **Purpose:** Mengelola catatan bimbingan konseling (BK) siswa, pencatatan prestasi/penghargaan siswa, serta dokumentasi pelanggaran disiplin siswa beserta poin pelanggarannya.
*   **Responsibility:**
    *   Mencatat agenda dan hasil sesi bimbingan konseling (`CounselingSession`).
    *   Mencatat daftar pelanggaran disiplin siswa (`Violation`) berdasarkan kategori pelanggaran (`ViolationCategory`).
    *   Mencatat perolehan penghargaan atau prestasi non-akademis siswa (`Achievement`).
    *   Melacak akumulasi poin pelanggaran siswa.
*   **Business Objective:** Memberikan sarana pemantauan perilaku siswa secara terukur, membantu guru BK mencatat sesi bimbingan secara rahasia, serta melacak rekam jejak kedisiplinan siswa di sekolah.

---

## 2. Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Pencatatan sesi bimbingan BK (konseling individu atau kelompok).
    *   Manajemen master kategori pelanggaran beserta bobot poin sanksi.
    *   Pencatatan pelanggaran harian siswa oleh guru piket atau guru BK.
*   **Bukan Tanggung Jawab Domain:**
    *   Pencatatan kehadiran/kealpaan harian siswa di kelas (tanggung jawab domain *Academic*).
    *   Pemberian sanksi administratif berupa penundaan ujian/pemblokiran akun siswa karena masalah pembayaran (tanggung jawab domain *Finance*).

---

## 3. Owned Components
*   **Controllers (4):**
    *   `AchievementController.php`, `CounselingSessionController.php`, `ViolationCategoryController.php`, `ViolationController.php`
*   **Models (4):**
    *   `Achievement.php`, `CounselingSession.php`, `Violation.php`, `ViolationCategory.php`
*   **Pages (8):**
    *   Vue files under `resources/js/Pages/Counseling/`

---

## 4. Owned Entities
*   `CounselingSession`
*   `ViolationCategory`
*   `Violation`
*   `Achievement`

---

## 5. Shared Components
*   `Student` (Academic Model - diimpor untuk mencatat siswa yang terlibat konseling atau melakukan pelanggaran).
*   `User` (Core Model - merekam pembuat catatan BK).
*   `Unit` (Yayasan Model - membatasi lingkup data pelanggaran per unit sekolah).

---

## 6. Incoming Dependencies
*   **Student (Portal):** Mengambil rangkuman poin pelanggaran dan daftar prestasi untuk ditampilkan pada dashboard siswa/orang tua.

---

## 7. Outgoing Dependencies
*   **Academic:** Bergantung pada model `Student` untuk menghubungkan catatan pelanggaran/konseling ke siswa yang bersangkutan.
*   **Yayasan:** Membutuhkan data unit sekolah (`Unit`) untuk menyaring lingkup aksesibilitas guru BK.
*   **Core:** Membutuhkan model `User` untuk merekam guru penginput data (`created_by`).

---

## 8. Entry Points
*   **Web Routes:** Dideklarasikan di `routes/web.php` di bawah prefix `/yayasan/counseling`.
*   **Sidebar Navigation:** Panel khusus Guru BK dan Staf Kesiswaan.

---

## 9. Public Interface
*   Pelanggaran dan poin sanksi terekspos ke portal siswa guna memfasilitasi transparansi pembinaan karakter.

---

## 10. Internal Structure
*   `app/Modules/Counseling/Controllers/` berisi logika pengolahan sesi BK dan pelanggaran.
*   `app/Modules/Counseling/Models/` berisi representasi data pelanggaran dan prestasi.
*   `resources/js/Pages/Counseling/` menyediakan antarmuka pengguna untuk guru BK.

---

## 11. Domain Findings
1.  **Integrasi WhatsApp untuk Notifikasi Pelanggaran:**
    *   *Evidence:* Rujukan pengiriman WhatsApp menggunakan `WhatsAppHelper` saat pelanggaran diinput agar langsung terkirim ke nomor orang tua (akan dikonfirmasi detailnya pada tahap bisnis flow).
    *   *Confidence:* High.

---

## 12. Unknown Areas
*   Bagaimana integrasi akumulasi poin secara otomatis memicu surat pemanggilan orang tua (apakah logikanya berada di controller atau dilakukan manual oleh guru).

---

## 13. Confidence
*   **High:** Batasan modul Counseling terdefinisi secara terpisah baik di backend maupun antarmuka frontend Vue.
