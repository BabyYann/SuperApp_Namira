# Employee Domain Overview

## 1. Domain Overview
*   **Domain Name:** Employee
*   **Purpose:** Mengelola informasi kepegawaian (staf dan karyawan non-guru) serta melacak kehadiran harian staf yayasan maupun unit sekolah.
*   **Responsibility:**
    *   Mengelola profil staf/karyawan (`Staff`).
    *   Mencatat dan mengelola status kehadiran harian staf.
    *   Menyediakan laporan rekap kehadiran staf untuk manajemen unit/yayasan.
*   **Business Objective:** Menyediakan direktori kepegawaian terpadu serta memastikan pencatatan kehadiran staf non-guru berjalan tertib guna mendukung pemantauan kedisiplinan dan penggajian di masa mendatang.

---

## 2. Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Penyimpanan data staf (nama, NIK, jabatan, unit tugas).
    *   Pencatatan absensi masuk dan pulang staf (melalui data absensi GPS/mesin absensi).
*   **Bukan Tanggung Jawab Domain:**
    *   Mengelola jadwal mengajar guru di kelas (tanggung jawab domain *Academic*).
    *   Menghitung gaji, tunjangan, dan potongan keuangan staf (tanggung jawab domain *Finance*).

---

## 3. Owned Components
*   **Controllers (2):**
    *   `AttendanceController.php`, `StaffController.php`
*   **Models (1):**
    *   `Staff.php`
*   **Pages (3):**
    *   Vue files under `resources/js/Pages/Employee/`

---

## 4. Owned Entities
*   `Staff`

---

## 5. Shared Components
*   `User` (Core Model - dihubungkan ke data login akun staf).
*   `Unit` (Yayasan Model - untuk pengelompokan staf berdasarkan tempat bertugas).
*   `EmployeeAttendance` (Core Model - model absensi harian karyawan yang diletakkan di `app/Models/EmployeeAttendance.php`).

---

## 6. Incoming Dependencies
*   **Yayasan (Monitoring):** Menu monitoring yayasan mengonsumsi data absensi staf untuk melacak persentase kehadiran harian unit.

---

## 7. Outgoing Dependencies
*   **Yayasan:** Membutuhkan data unit sekolah (`Unit`) untuk pembagian lokasi tugas staf.
*   **Core:** Membutuhkan model `User` untuk otentikasi login staf, serta model `EmployeeAttendance` untuk menyimpan rekam kehadiran fisik.

---

## 8. Entry Points
*   **Web Routes:** Rute terdaftar di `routes/web.php` di bawah prefix `/yayasan/employee`.
*   **Sidebar Navigation:** Menu Kelola Karyawan dan Absensi Staf di Dashboard Yayasan/Unit.

---

## 9. Public Interface
*   `App\Modules\Employee\Models\Staff` (Untuk relasi data pengguna yayasan).

---

## 10. Internal Structure
*   `app/Modules/Employee/Controllers/` untuk pengolahan CRUD data staf dan absensi kepegawaian.
*   `app/Modules/Employee/Models/` penampung model profil staf.
*   `resources/js/Pages/Employee/` berisi antarmuka pengelolaan.

---

## 11. Domain Findings
1.  **Model Kehadiran Terpisah di Core Folder:**
    *   *Evidence:* Data kehadiran staf tidak disimpan di `app/Modules/Employee/Models/` melainkan di core folder `app/Models/EmployeeAttendance.php`. Hal ini menunjukkan dependensi silang di mana modul Employee mengonsumsi model global.
    *   *Confidence:* High.

---

## 12. Unknown Areas
*   Apakah Guru dimasukkan ke dalam entitas Karyawan BK/Yayasan atau hanya dikelola eksklusif melalui entitas `Teacher` di modul Academic.

---

## 13. Confidence
*   **High:** Modul memiliki batasan fungsional terarah yang fokus pada manajemen staf non-guru dan rekap absensinya.
