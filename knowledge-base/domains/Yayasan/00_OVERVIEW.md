# Yayasan Domain Overview

## 1. Domain Overview
*   **Domain Name:** Yayasan
*   **Purpose:** Bertindak sebagai modul administrasi pusat yayasan yang mengelola master data sekolah (unit), kalender tahun ajaran global, direktori akun pengguna sistem, hari libur bersama, parameter absensi GPS, serta monitoring log audit aktivitas sistem.
*   **Responsibility:**
    *   Mengelola master data unit sekolah di bawah yayasan (`Unit`).
    *   Mengelola kalender tahun ajaran aktif (`AcademicYear`).
    *   Mengelola pembuatan dan edit profil akun pengguna global (`User`).
    *   Mengatur hari libur bersama yayasan (`Holiday`).
    *   Mengatur lokasi absensi resmi beserta toleransi radius GPS (`AttendanceLocation`).
    *   Mengelola konfigurasi fitur global dan parameter setting sistem (`SystemSetting`).
    *   Mencatat jejak audit log aktivitas user (`activity_log`).
*   **Business Objective:** Menyediakan control center terpadu bagi yayasan untuk memantau operasional seluruh unit sekolah, mengendalikan siklus tahun ajaran aktif, dan memantau kepatuhan absensi harian staf dan guru.

---

## 2. Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Registrasi sekolah baru (SD, SMP, SMA) di bawah yayasan Namira.
    *   Registrasi dan penonaktifan akun staf/guru secara global.
    *   Penyediaan switch unit context agar admin dapat berpindah dashboard antar sekolah.
    *   Persetujuan pengajuan izin absen absensi staf/guru.
*   **Bukan Tanggung Jawab Domain:**
    *   Mengatur jadwal harian guru BK di kesiswaan (tanggung jawab domain *Counseling*).
    *   Pemberian nilai tugas siswa (tanggung jawab domain *LMS*).

---

## 3. Owned Components
*   **Controllers (9):**
    *   `AcademicYearController.php`, `AttendanceApprovalController.php`, `AttendanceDataController.php`, `AttendanceLocationController.php`, `HolidayController.php`, `MonitoringController.php`, `SettingController.php`, `UnitController.php`, `UserController.php`
*   **Models (4):**
    *   `AcademicYear.php`, `Holiday.php`, `SystemSetting.php`, `Unit.php`
*   **Pages (16):**
    *   Vue files under `resources/js/Pages/Yayasan/`

---

## 4. Owned Entities
*   `Unit`
*   `AcademicYear`
*   `SystemSetting`
*   `Holiday`

---

## 5. Shared Components
*   `User` (Core Model - diregistrasi dan diatur melalui antarmuka Kelola Pengguna).
*   `AttendanceLocation` (Core Model - dikonfigurasi melalui menu setting lokasi absensi).
*   `activitylog` (Spatie Package - digunakan oleh menu monitoring audit trail).

---

## 6. Incoming Dependencies
*   **Seluruh Modul:** Modul Academic, Finance, Employee, Counseling, LMS, PR, dan Sarpar semuanya bergantung pada model `Unit` dan `AcademicYear` milik Yayasan untuk memvalidasi alokasi data.

---

## 7. Outgoing Dependencies
*   **Core:** Bergantung pada model global `User` dan `AttendanceLocation` yang diletakkan di direktori core.

---

## 8. Entry Points
*   **Web Routes:** Rute dideklarasikan di `routes/web.php` di bawah prefix `/yayasan/` dengan otorisasi peran administrator global.
*   **Dashboard Switch Unit:** Dropdown switch unit sekolah yang memicu route `/switch-unit`.

---

## 9. Public Interface
*   `App\Modules\Yayasan\Models\Unit` (Diimpor oleh seluruh modul).
*   `App\Modules\Yayasan\Models\AcademicYear` (Diimpor oleh seluruh modul).

---

## 10. Internal Structure
*   `app/Modules/Yayasan/Controllers/` berisi penanganan switch context unit, reset password user, persetujuan absensi.
*   `app/Modules/Yayasan/Models/` model data konfigurasi yayasan.
*   `resources/js/Pages/Yayasan/` antarmuka kontrol pusat yayasan.

---

## 11. Domain Findings
1.  **Ditemukan Fitur Switch Unit Context Global:**
    *   *Evidence:* Terdapat controller unit dengan route `switch-unit` (`[\App\Modules\Yayasan\Controllers\UnitController::class, 'switch']`) yang memungkinkan pengguna berpindah sekolah secara dinamis pada sesi aktif.
    *   *Confidence:* High.

---

## 12. Unknown Areas
*   Bagaimana pembagian hak akses admin_yayasan vs admin_unit secara terperinci saat berpindah unit context.

---

## 13. Confidence
*   **High:** Peran Yayasan sebagai control center global terdokumentasi dengan sangat kokoh melalui controller, middleware unit scope, dan dependensi impor dari modul lain.
