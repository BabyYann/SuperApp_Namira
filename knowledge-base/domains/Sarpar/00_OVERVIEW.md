# Sarpar Domain Overview

## 1. Domain Overview
*   **Domain Name:** Sarpar (Sarana & Prasarana)
*   **Purpose:** Mengelola aset fisik sekolah, klasifikasi kategori barang, pemeliharaan ruangan/sarana belajar, peminjaman barang oleh guru/staf, checkout ruang praktikum, serta pencatatan log penggunaan aset sekolah.
*   **Responsibility:**
    *   Mengatur profil inventaris barang sekolah (`Inventory`).
    *   Mengelompokkan barang berdasarkan kategori (`Category`).
    *   Mengelola daftar ruangan fisik sekolah (`Room`).
    *   Mencatat histori dan pengajuan peminjaman barang (`Loan`).
    *   Mencatat agenda pemeliharaan/perbaikan inventaris rusak (`MaintenanceLog`).
    *   Mencatat log harian pemakaian ruangan/alat (`UsageLog`).
    *   Menghasilkan nomor kode barang unik secara otomatis (`InventoryCodeGenerator`).
*   **Business Objective:** Memantau ketersediaan barang inventaris, mencegah kehilangan aset sekolah, merapikan log pemeliharaan fasilitas, serta mengotomatisasi pencetakan label kode barang sekolah.

---

## 2. Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Manajemen status kelayakan barang (bagus, rusak, diperbaiki).
    *   Pencatatan tanggal pinjam dan tanggal kembali aset sekolah.
    *   Log perbaikan sarana rusak oleh staf teknis.
*   **Bukan Tanggung Jawab Domain:**
    *   Penyediaan dana pembelian aset baru (tanggung jawab domain *Finance*).
    *   Manajemen ruang kelas digital untuk KBM (tanggung jawab domain *LMS*).

---

## 3. Owned Components
*   **Controllers (7):**
    *   `CategoryController.php`, `DashboardController.php`, `InventoryController.php`, `LoanController.php`, `MaintenanceController.php`, `RoomController.php`, `UsageLogController.php`
*   **Models (6):**
    *   `Category.php`, `Inventory.php`, `Loan.php`, `MaintenanceLog.php`, `Room.php`, `UsageLog.php`
*   **Services (1):**
    *   `InventoryCodeGenerator.php` (Digunakan untuk menformulasikan nomor seri barang).
*   **Pages (7):**
    *   Vue files under `resources/js/Pages/Sarpar/`

---

## 4. Owned Entities
*   `Category`
*   `Room`
*   `Inventory`
*   `MaintenanceLog`
*   `Loan`
*   `UsageLog`

---

## 5. Shared Components
*   `User` (Core Model - merekam peminjam barang / penanggung jawab ruangan).
*   `Unit` (Yayasan Model - membatasi kepemilikan aset per sekolah).

---

## 6. Incoming Dependencies
*   *Status:* **None** (Saat ini belum ada modul lain yang secara langsung memanggil model/log milik Sarpar).

---

## 7. Outgoing Dependencies
*   **Yayasan:** Membutuhkan data unit sekolah (`Unit`) untuk melabeli lokasi fisik barang.
*   **Core:** Memerlukan model `User` untuk merekam data peminjam dan staf pemeriksa.

---

## 8. Entry Points
*   **Web Routes:** Rute terdaftar di `routes/web.php` di bawah prefix `/yayasan/sarpar`.
*   **Sidebar Navigation:** Dashboard Sarana & Prasarana bagi Kepala Sarpar Unit dan Teknisi.

---

## 9. Public Interface
*   `App\Modules\Sarpar\Services\InventoryCodeGenerator` (Generator kode internal).
*   Ekspor data barang dalam format excel via `InventoryExport.php`.

---

## 10. Internal Structure
*   `app/Modules/Sarpar/Controllers/` berisi penanganan pengajuan pinjaman dan log kerusakan.
*   `app/Modules/Sarpar/Models/` model data relasional barang dan pemeliharaan.
*   `app/Modules/Sarpar/Services/` generator kode aset.
*   `resources/js/Pages/Sarpar/` antarmuka monitoring status inventaris.

---

## 11. Domain Findings
1.  **Ditemukan Service Khusus untuk Kode Aset:**
    *   *Evidence:* Terdapat kelas `InventoryCodeGenerator.php` yang didedikasikan khusus untuk merumuskan kode penomoran barang terstandardisasi (misalnya kombinasi kode unit, tahun beli, dan nomor urut).
    *   *Confidence:* High.

---

## 12. Unknown Areas
*   Apakah peminjaman barang oleh siswa didukung, atau hanya dibatasi untuk guru dan staf karyawan saja.

---

## 13. Confidence
*   **High:** Arsitektur modul ini mandiri dengan relasi tabel, kontroler, serta antarmuka monitoring aset yang sangat komprehensif.
