# Finance Domain Overview

## 1. Domain Overview
*   **Domain Name:** Finance
*   **Purpose:** Mengelola penyiapan pos anggaran keuangan sekolah, pembagian jenis tagihan SPP/non-SPP siswa, kalkulasi keringanan biaya, pelacakan histori transaksi pembayaran, serta penyusunan laporan keuangan dan tunggakan tagihan.
*   **Responsibility:**
    *   Mengatur jenis-jenis akun perkiraan keuangan sekolah (`FinanceAccount`).
    *   Mencetakkan tagihan bulanan atau insidental untuk masing-masing siswa (`StudentBill`).
    *   Mengelola kebijakan pemotongan/diskon biaya sekolah untuk siswa beasiswa (`StudentDiscount`).
    *   Mencatat mutasi transaksi masuk dan keluar (`Transaction`, `BillPayment`).
    *   Menghasilkan laporan keuangan neraca, laporan arus kas, serta rekapitulasi tunggakan SPP (`FinanceReport`).
    *   Menyediakan fungsi impor transaksi mutasi bank (misal format CSV/Excel) untuk rekonsiliasi otomatis.
*   **Business Objective:** Membantu tim keuangan yayasan melakukan manajemen keuangan secara transparan, mempercepat penagihan piutang SPP, mempermudah pelaporan keuangan unit, serta menyinkronkan data pembayaran bank dengan tagihan siswa.

---

## 2. Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Definisi jenis biaya sekolah (SPP, Uang Pembangunan, Pendaftaran).
    *   Penerbitan faktur tagihan untuk siswa aktif.
    *   Pencatatan pembayaran tagihan (manual input oleh admin atau import mutasi bank).
    *   Pemrosesan diskon khusus beasiswa yayasan.
*   **Bukan Tanggung Jawab Domain:**
    *   Mengelola persediaan barang belanjaan sekolah (tanggung jawab domain *Sarpar*).
    *   Mengelola penggajian karyawan (tanggung jawab domain *Employee* - belum ada modul penggajian).
    *   Mengurusi pendaftaran siswa baru secara akademis (tanggung jawab domain *Academic*).

---

## 3. Owned Components
*   **Controllers (6):**
    *   `FinanceAccountController.php`, `FinanceDashboardController.php`, `FinanceReportController.php`, `FinanceTypeController.php`, `StudentBillController.php`, `TransactionController.php`
*   **Models (6):**
    *   `BillPayment.php`, `FinanceAccount.php`, `FinanceType.php`, `StudentBill.php`, `StudentDiscount.php`, `Transaction.php`
*   **Pages (9):**
    *   Vue files under `resources/js/Pages/Finance/`

---

## 4. Owned Entities
*   `FinanceAccount`
*   `FinanceType`
*   `StudentBill`
*   `StudentDiscount`
*   `Transaction`
*   `BillPayment`

---

## 5. Shared Components
*   `Student` (Academic Model - data target penagihan SPP).
*   `Unit` (Yayasan Model - alokasi rekening keuangan per sekolah).
*   `AcademicYear` (Yayasan Model - pengelompokan tagihan per tahun ajaran).
*   `User` (Core Model - merekam pembuat transaksi).

---

## 6. Incoming Dependencies
*   **Student (Portal):** Dashboard siswa memanggil informasi rincian tagihan belum lunas (`StudentBill`) dan riwayat pembayaran mereka.

---

## 7. Outgoing Dependencies
*   **Academic:** Memerlukan daftar siswa (`Student`) yang aktif untuk menerbitkan tagihan tahunan/bulanan.
*   **Yayasan:** Mengambil referensi `Unit` dan `AcademicYear` untuk memfilter data akun keuangan dan menaruh histori tagihan pada periode akademik yang tepat.
*   **Core:** Mengimpor `User` untuk verifikasi otorisasi kasir/staf keuangan.

---

## 8. Entry Points
*   **Web Routes:** Didaftarkan dalam `routes/web.php` di bawah prefix `/yayasan/finance/`.
*   **Sidebar Navigation:** Dashboard Keuangan bagi Bendahara Yayasan dan Staf Keuangan Unit.

---

## 9. Public Interface
*   Ekspos tagihan siswa ke modul Portal Siswa agar orang tua siswa dapat memantau status kewajiban pembayaran secara mandiri.

---

## 10. Internal Structure
*   `app/Modules/Finance/Controllers/` berisi penanganan impor CSV transaksi bank, pembuatan batch tagihan, dan laporan tunggakan.
*   `app/Modules/Finance/Models/` berisi representasi data perkiraan akun keuangan dan tagihan siswa.
*   `resources/js/Pages/Finance/` menyediakan antarmuka rekap transaksi dan grafik pendapatan kasir.

---

## 11. Domain Findings
1.  **Impor Mutasi Rekening Otomatis:**
    *   *Evidence:* Terdapat controller `TransactionController` dengan rute khusus untuk impor transaksi (`Import.vue`) untuk memadupadankan nomor referensi Virtual Account bank secara otomatis dengan tagihan siswa.
    *   *Confidence:* High.
2.  **Struktur Folder Routes yang Tertinggal:**
    *   *Evidence:* Folder `app/Modules/Finance/Routes` ditemukan kosong, mengindikasikan rencana desentralisasi routing modul keuangan yang dibatalkan.
    *   *Confidence:* High.

---

## 12. Unknown Areas
*   Apakah sistem terhubung dengan gateway pembayaran Virtual Account (VA) secara langsung via API web hook, atau sepenuhnya berbasis impor file CSV mutasi bank secara manual.

---

## 13. Confidence
*   **High:** Domain batas modul Finance sangat solid dengan sekumpulan tabel database, model transaksi, dan antarmuka khusus kasir sekolah.
