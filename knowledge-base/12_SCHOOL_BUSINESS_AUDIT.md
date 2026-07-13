# 12_SCHOOL_BUSINESS_AUDIT

**Dokumen:** Audit Alur Bisnis Sekolah (Enterprise ERP Perspective)  
**Versi:** Enterprise v1  
**Status:** Read-Only / Evidence-Based  
**Auditor:** Enterprise School ERP Consultant

---

## 1. Business Flow Completeness: 55%
**Confidence Level: High**

Berdasarkan investigasi menyeluruh terhadap kapabilitas sistem ERP Namira saat ini, cakupan fungsionalitas bisnis sekolah yang terdigitalisasi baru mencapai **55%**. 

Sistem saat ini sangat kuat pada area transaksi harian operasional (*core daily operations*) seperti:
*   Pencatatan Profil Akademik (Siswa, Kelas, Jadwal, Jurnal Guru).
*   Rekonsiliasi Keuangan SPP (Waterfall Payment via impor CSV mutasi bank).
*   Absensi Harian Karyawan (Geofencing GPS Radius).
*   Manajemen Inventaris & Peminjaman Sarana Prasarana.
*   Pencatatan Kasus BK & Konseling.

Namun, sistem memiliki gap besar pada area hulu (*intake/input*) dan hilir (*output/termination*) pada hampir setiap siklus hidup operasional sekolah (seperti PPDB, Portal Orang Tua, e-Rapor, Payroll, dan Manajemen Alumni).

---

## 2. Business Flow Maturity: Level 2 (Managed)
**Confidence Level: High**

Skala Kematangan Proses Bisnis (Maturity Level 1-5):
*   **Level 1 (Initial):** Proses bersifat ad-hoc, manual, tidak terstandar.
*   **Level 2 (Managed):** Proses terdigitalisasi dasar, ada pelacakan data, namun masih ada gap integrasi antar unit/fungsi. [**POSISI NAMIRA ERP**]
*   **Level 3 (Defined):** Proses terdokumentasi, terstandar di seluruh unit, terintegrasi penuh ujung-ke-ujung (end-to-end).
*   **Level 4 (Quantitatively Managed):** Proses dikendalikan menggunakan metrik kinerja kuantitatif.
*   **Level 5 (Optimizing):** Proses dioptimalkan terus-menerus menggunakan AI dan otomatisasi prediktif.

**Evaluasi:** Namira ERP berada di **Level 2**. Proses pencatatan kehadiran, keuangan SPP, dan inventaris sudah berbasis data dan terlacak secara digital. Namun, kematangannya terhambat oleh banyaknya intervensi manual (kasir harus mengunduh dan mengunggah CSV mutasi bank, guru mengumumkan tugas di LMS tetapi rapor akhir semester masih disusun manual di luar sistem).

---

## 3. Business Gap

Proses bisnis sekolah penting yang **belum terakomodasi** oleh sistem saat ini:

1.  **Penerimaan Peserta Didik Baru (PPDB / Penerimaan Siswa Baru):**
    *   *Deskripsi:* Tidak ada modul pendaftaran online bagi calon siswa baru, seleksi ujian masuk, verifikasi berkas administrasi oleh TU, dan pembayaran biaya pendaftaran awal. Saat ini data siswa langsung dimasukkan massal via Excel oleh admin.
2.  **Manajemen Alumni (Alumni Tracking):**
    *   *Deskripsi:* Siklus siswa terputus setelah kelulusan. Tidak ada database alumni, tracer study (pelacakan karir/kuliah), forum alumni, atau legalisasi ijazah digital.
3.  **Evaluasi Kinerja Pendidik (Teacher Performance & Evaluation):**
    *   *Deskripsi:* Tidak ada modul Penilaian Kinerja Guru (PKG) atau evaluasi staf oleh Kepala Sekolah / Yayasan.
4.  **Cuti Karyawan Terstruktur (Leave Management):**
    *   *Deskripsi:* Karyawan belum dapat mengajukan cuti terencana (cuti tahunan, melahirkan, dll.) melalui aplikasi dengan alur persetujuan (approval flow) berjenjang.
5.  **Slip Gaji & Payroll (Penggajian):**
    *   *Deskripsi:* Log absensi GPS karyawan tidak terhubung dengan modul penggajian. Penghitungan potongan keterlambatan, bonus mengajar, BPJS, dan PPh 21 masih dikerjakan manual oleh Keuangan Yayasan di Excel.
6.  **E-Rapor Kurikulum Merdeka:**
    *   *Deskripsi:* Guru dapat menginput jurnal dan absen, namun sistem belum memiliki mesin pengolah rapor akhir semester yang otomatis mengonversi kumpulan nilai harian menjadi lembar rapor PDF Kurikulum Merdeka.
7.  **Manajemen Buku Tamu & Keamanan (Security Log):**
    *   *Deskripsi:* Petugas Keamanan (Satpam) tidak memiliki akses untuk mencatat buku tamu digital, patroli harian, dan log serah terima barang/kendaraan.

---

## 4. Business Bottleneck

Proses bisnis yang sudah digital namun **membutuhkan intervensi manual intensif**:

1.  **Rekonsiliasi Pembayaran Bank (Kasir Keuangan):**
    *   *Bottleneck:* Kasir harus membuka e-banking bank mitra, mengunduh file CSV transaksi mutasi harian secara berkala, lalu mengunggahnya secara manual ke sistem ERP agar pembayaran SPP siswa terverifikasi lunas.
2.  **Penginputan Pelanggaran BK (Guru Piket & BK):**
    *   *Bottleneck:* Pencatatan poin pelanggaran BK membutuhkan upload bukti foto fisik secara manual. Apabila guru BK sedang sibuk di kelas, pencatatan tertunda dan notifikasi peringatan terlambat diterima orang tua.
3.  **Persetujuan Pengadaan & Kerusakan Aset (Sarpras):**
    *   *Bottleneck:* Laporan kerusakan inventaris tercatat di sistem, namun keputusan persetujuan perbaikan (*approval*) atau pengeluaran biaya perbaikan masih dilakukan secara verbal/fisik di luar aplikasi sebelum akhirnya status diubah manual oleh teknisi di sistem.

---

## 5. Business Risk

Risiko operasional akibat keterbatasan alur bisnis saat ini:

1.  **Risiko Kebocoran Kas (Fraud Keuangan):**
    *   *Deskripsi:* Tanpa fitur **Closing Book (Tutup Buku)** bulanan yang mengunci transaksi masa lalu, kasir yang nakal dapat memodifikasi nilai `paid_amount` atau `excess_amount` pada invoice SPP bulan lalu yang sudah lunas untuk kepentingan pribadi.
2.  **Risiko Kebocoran Data Multi-Unit (Data Privacy Breach):**
    *   *Deskripsi:* Pemilahan data sekolah (SD/SMP/SMA Namira) di tingkat database masih manual tanpa global filter. Kelalaian kecil staf TU unit dalam memfilter rute dapat menyebabkan data SPP atau rahasia BK siswa SD bocor ke layar staf unit SMA.
3.  **Risiko Keterlambatan Respon Darurat (BK & Absensi):**
    *   *Deskripsi:* Siswa alpa atau melakukan pelanggaran berat BK baru diketahui orang tua secara pasif/terlambat karena pengiriman notifikasi masih berjalan satu arah via WhatsApp sinkronus tanpa portal orang tua yang interaktif.

---

## 6. Missing Feature

Fitur yang **seharusnya wajib ada** untuk standard ERP Sekolah Enterprise:

*   **Payment Gateway Integration:** API bank langsung (Midtrans/Xendit) untuk pembuatan Virtual Account dinamis dan otomatisasi mutasi real-time tanpa unggah CSV manual.
*   **Parent Portal Application (Web/Mobile):** Portal khusus orang tua untuk memantau data absensi masuk sekolah real-time anak, tagihan SPP aktif, riwayat konseling BK, dan hasil rapor belajar.
*   **e-Signature Ijazah & Rapor:** Untuk otentikasi dokumen hasil belajar siswa oleh Kepala Sekolah secara digital.
*   **Multi-level Budgeting & Expense Approval:** Alur pengajuan biaya operasional kelas/unit yang memerlukan tanda tangan digital berjenjang dari Bendahara Unit → Kepala Sekolah → Keuangan Yayasan.

---

## 7. UX Gap (Flow Bisnis Membingungkan)

*   **Penyatuan Log Kehadiran Guru & Staf:**  
    *   *UX Gap:* Guru dan staf menggunakan form absen WFO GPS yang sama. Padahal, jam mengajar Guru bersifat dinamis berdasarkan jadwal pelajaran (`class_schedules`), sedangkan Staf TU memiliki jam kerja statis (`work_start_time`). Penyatuan parameter waktu terlambat ini membingungkan Guru yang jadwal mengajarnya baru dimulai di siang hari.
*   **Akses Portal Siswa Terhadap Menu BK:**  
    *   *UX Gap:* Siswa dapat melihat catatan pelanggaran dan skor BK mereka sendiri secara detail. Bagi sebagian siswa, visualisasi grafik skor BK yang menumpuk tanpa arahan konselor dapat menurunkan motivasi belajar atau memicu kecemasan di luar jam sekolah.

---

## 8. Automation Opportunity

1.  **Otomatisasi Tagihan SPP Bulanan (Billing Generator):**  
    *   *Peluang:* Sistem dapat menggunakan cron scheduler untuk menerbitkan tagihan SPP bulanan siswa secara otomatis setiap tanggal 1, alih-alih mengandalkan admin keuangan yayasan menekan tombol "Generate" manual di awal bulan.
2.  **Otomatisasi Status Terlambat Siswa:**  
    *   *Peluang:* Sinkronisasi log absensi siswa kelas fisik (`journal_attendance`) dengan alarm peringatan BK otomatis. Siswa yang alpa 3 hari berturut-turut langsung memicu tiket panggilan orang tua di modul BK.

---

## 9. Enterprise Readiness Assessment

*   **Skala 1 Sekolah:** **Layak (Ready)**. Sistem sangat siap digunakan untuk mengelola 1 sekolah secara internal.
*   **Skala 10 Sekolah (Multi-Unit Terbatas):** **Cukup Layak (Conditional Ready)**. Mampu memproses asalkan unit berada di bawah tata kelola yayasan yang sama dengan kontrol data unit (`active_unit_id`) yang diperketat.
*   **Skala 100 - 1000 Sekolah:** **Tidak Layak (Not Ready)**. Arsitektur modular monolith saat ini belum siap menangani multi-tenancy skala besar (bukan arsitektur SaaS multi-tenant dengan isolasi database per sekolah/tenant).

---

## 10. Prioritas Pengembangan

### Kategori: CRITICAL (Harus Segera Dibuat)
1.  **Integrasi Payment Gateway:** Menghilangkan proses manual unggah mutasi CSV untuk mencegah fraud keuangan dan timeout RAM server.
2.  **Global Multi-Tenancy Scope:** Mengunci database di tingkat model agar data sekolah SD, SMP, dan SMA Namira terisolasi secara mutlak dari risiko IDOR/kebocoran data.

### Kategori: HIGH (Kebutuhan Operasional Utama)
1.  **Portal Khusus Orang Tua (Parent Portal):** Menghubungkan komunikasi aktif sekolah-keluarga.
2.  **Modul Pengolahan & Cetak e-Rapor:** Menyelesaikan siklus belajar siswa secara digital.
3.  **Fitur Closing Book & Lock Transaction:** Mengamankan laporan keuangan historis dari modifikasi ilegal kasir.

### Kategori: MEDIUM (Peningkatan Efisiensi)
1.  **Modul Pengajuan Cuti Staf (Leave Management):** Mengurangi birokrasi manual kertas di ruang TU.
2.  **Automated Billing Scheduler:** Otomatisasi penerbitan invoice bulanan.

### Kategori: LOW (Pelengkap)
1.  **Portal Tracer Study Alumni:** Pelacakan masa depan alumni setelah lulus.
2.  **Buku Tamu Digital Satpam:** Pencatatan log kunjungan fisik.
