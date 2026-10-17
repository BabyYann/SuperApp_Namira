# Feature Inventory (Inventaris Fitur Sistem SuperApp Namira)

Dokumen ini memetakan seluruh fitur fungsional yang tersedia di dalam SuperApp Namira beserta kode fitur, modul pemilik, peran pengguna target, bukti rute/controller, dan status implementasinya.

---

## 1. Modul Tata Kelola Yayasan & Multi-Tenancy (YAY)

| Kode Fitur | Nama Fitur | Peran Sasaran | Controller & Rute Terkait | Evidence ID | Status |
|---|---|---|---|---|---|
| **FEAT-YAY-01** | Multi-Unit School Switching | Super Admin, Pembina | `UserController::switchUnit` (`POST /yayasan/switch-unit`) | EV-CORE-004 | `IMPLEMENTED` |
| **FEAT-YAY-02** | Master Data Unit Sekolah | Super Admin | `UnitController` (`/yayasan/units`) | EV-CORE-005 | `IMPLEMENTED` |
| **FEAT-YAY-03** | Master Tahun Akademik | Super Admin, Admin Yayasan | `AcademicYearController` (`/yayasan/academic-years`) | EV-CORE-006 | `IMPLEMENTED` |
| **FEAT-YAY-04** | Manajemen Pengguna & Multi-Tenancy Roles | Super Admin, Admin Yayasan | `UserController` (`/yayasan/users`) | EV-CORE-007 | `IMPLEMENTED` |
| **FEAT-YAY-05** | Master Lokasi Presensi GPS (Geofence) | Admin Yayasan | `AttendanceLocationController` (`/yayasan/attendance-locations`) | EV-EMP-002 | `IMPLEMENTED` |
| **FEAT-YAY-06** | Kalender Libur Yayasan | Admin Yayasan | `HolidayController` (`/yayasan/holidays`) | EV-CORE-006 | `IMPLEMENTED` |
| **FEAT-YAY-07** | Persetujuan Absensi & Izin Pegawai | Pengawas, Atasan Unit | `AttendanceApprovalController` (`/yayasan/attendance-approvals`) | EV-EMP-003 | `IMPLEMENTED` |
| **FEAT-YAY-08** | Monitoring Kinerja Sistem (Pulse) | Super Admin | `/pulse` | EV-CORE-001 | `IMPLEMENTED` |
| **FEAT-YAY-09** | Pengaturan Global & Fitur Toggle | Super Admin | `SettingController` (`/yayasan/settings`) | EV-CORE-003 | `IMPLEMENTED` |

---

## 2. Modul Akademik & Kesiswaan (ACA)

| Kode Fitur | Nama Fitur | Peran Sasaran | Controller & Rute Terkait | Evidence ID | Status |
|---|---|---|---|---|---|
| **FEAT-ACA-01** | Master Direktori Siswa | Admin Unit, Wali Kelas | `StudentController` (`/yayasan/students`) | EV-ACAD-001 | `IMPLEMENTED` |
| **FEAT-ACA-02** | Master Direktori Guru | Admin Unit, Koordinator | `TeacherController` (`/yayasan/teachers`) | EV-ACAD-001 | `IMPLEMENTED` |
| **FEAT-ACA-03** | Manajemen Rombel & Kelas | Admin Unit | `ClassroomController` (`/yayasan/classrooms`) | EV-ACAD-001 | `IMPLEMENTED` |
| **FEAT-ACA-04** | Penyusunan Jadwal Pelajaran (Multi-Class) | Admin, Guru | `ScheduleController` (`/yayasan/schedules`) | EV-ACAD-002 | `IMPLEMENTED` |
| **FEAT-ACA-05** | Jurnal KBM Mengajar Guru | Guru Pengampu | `TeachingJournalController` (`/yayasan/teaching-journals`) | EV-ACAD-003 | `IMPLEMENTED` |
| **FEAT-ACA-06** | Presensi Siswa Kelas | Guru Pengampu | `StudentAttendanceController` (`/yayasan/student-attendances`) | EV-ACAD-003 | `IMPLEMENTED` |
| **FEAT-ACA-07** | Gerbang Presensi QR (Gate Scan) | Petugas Gerbang, Guru | `StudentCheckinController` (`/yayasan/student-checkin`) | EV-ACAD-005 | `IMPLEMENTED` |
| **FEAT-ACA-08** | Kenaikan & Kelulusan Kelas Massal | Kepala Sekolah, Admin | `ClassPromotionController` (`/yayasan/class-promotions`) | EV-ACAD-004 | `IMPLEMENTED` |
| **FEAT-ACA-09** | Cetak Kartu Pelajar & Rapor Siswa | Admin Unit | `StudentController::exportPdf` | EV-ACAD-001 | `PARTIAL` |

---

## 3. Modul Kepegawaian & Presensi Karyawan (EMP)

| Kode Fitur | Nama Fitur | Peran Sasaran | Controller & Rute Terkait | Evidence ID | Status |
|---|---|---|---|---|---|
| **FEAT-EMP-01** | Presensi Mobile GPS & Foto Selfie | Seluruh Karyawan | `AttendanceController::store` (`POST /employee/attendance`) | EV-EMP-001 | `IMPLEMENTED` |
| **FEAT-EMP-02** | Validasi Radius Geofencing Haversine | Sistem Otomatis | `AttendanceController::validateDistance` | EV-EMP-002 | `IMPLEMENTED` |
| **FEAT-EMP-03** | Rekap Log Giat Harian Karyawan | Seluruh Karyawan, Atasan | `EmployeeActivityLogController` (`/employee/activity-logs`) | EV-EMP-004 | `IMPLEMENTED` |
| **FEAT-EMP-04** | Direktori Staf Non-Guru | HRD, Admin Unit | `StaffController` (`/employee/staff`) | EV-EMP-001 | `IMPLEMENTED` |
| **FEAT-EMP-05** | Pengajuan Izin / Cuti / Sakit Mobile | Karyawan | `AttendanceController::requestLeave` | EV-EMP-003 | `IMPLEMENTED` |

---

## 4. Modul Keuangan & SPP (FIN)

| Kode Fitur | Nama Fitur | Peran Sasaran | Controller & Rute Terkait | Evidence ID | Status |
|---|---|---|---|---|---|
| **FEAT-FIN-01** | Master Pos Bayar & Biaya Sekolah | Staf Keuangan | `FinanceTypeController` (`/yayasan/finance-types`) | EV-FIN-001 | `IMPLEMENTED` |
| **FEAT-FIN-02** | Master Akun Rekening Bank Sekolah | Staf Keuangan | `FinanceAccountController` (`/yayasan/finance-accounts`) | EV-FIN-001 | `IMPLEMENTED` |
| **FEAT-FIN-03** | Penagihan SPP Bulanan & Diskon Khusus | Staf Keuangan | `StudentBillController` (`/yayasan/student-bills`) | EV-FIN-001 | `IMPLEMENTED` |
| **FEAT-FIN-04** | Pembayaran Tagihan & Cetak Kuitansi | Kasir, Wali Murid | `StudentBillController::pay` | EV-FIN-002 | `IMPLEMENTED` |
| **FEAT-FIN-05** | Unggah & Verifikasi Bukti Transfer | Wali Murid, Kasir | `StudentBillController::uploadPaymentProof` | EV-FIN-001 | `IMPLEMENTED` |
| **FEAT-FIN-06** | Rekap Laporan Tunggakan Siswa | Staf Keuangan, Yayasan | `FinanceReportController::arrears` | EV-FIN-003 | `IMPLEMENTED` |
| **FEAT-FIN-07** | Arus Kas & Mutasi Masuk/Keluar | Bendahara Yayasan | `TransactionController` (`/yayasan/finance-transactions`) | EV-FIN-003 | `IMPLEMENTED` |

---

## 5. Modul Bimbingan Konseling & Kesiswaan (BK)

| Kode Fitur | Nama Fitur | Peran Sasaran | Controller & Rute Terkait | Evidence ID | Status |
|---|---|---|---|---|---|
| **FEAT-BK-01** | Master Kategori & Poin Pelanggaran | Guru BK | `ViolationCategoryController` (`/counseling/categories`) | EV-COUN-001 | `IMPLEMENTED` |
| **FEAT-BK-02** | Pencatatan Insiden Pelanggaran Siswa | Guru Piket, Guru BK | `ViolationController` (`/counseling/violations`) | EV-COUN-001 | `IMPLEMENTED` |
| **FEAT-BK-03** | Pencatatan Prestasi & Sertifikat Siswa | Guru BK, Kesiswaan | `AchievementController` (`/counseling/achievements`) | EV-COUN-003 | `IMPLEMENTED` |
| **FEAT-BK-04** | Sesi Konseling Individu & Kelompok | Guru BK | `CounselingSessionController` (`/counseling/sessions`) | EV-COUN-002 | `IMPLEMENTED` |
| **FEAT-BK-05** | Notifikasi WA Pelanggaran ke Wali Murid | Sistem Otomatis | `WhatsAppHelper::send` via Violation Observer | EV-NOTIF-003 | `UNVERIFIED` |

---

## 6. Modul Sarana & Prasarana (SAR)

| Kode Fitur | Nama Fitur | Peran Sasaran | Controller & Rute Terkait | Evidence ID | Status |
|---|---|---|---|---|---|
| **FEAT-SAR-01** | Registrasi & Klasifikasi Aset Barang | Koordinator Sarpar | `InventoryController` (`/sarpar/inventories`) | EV-SARP-001 | `IMPLEMENTED` |
| **FEAT-SAR-02** | Cetak Stiker Label QR Code Aset | Koordinator Sarpar | `StickerController` (`/sarpar/stickers`) | EV-SARP-002 | `IMPLEMENTED` |
| **FEAT-SAR-03** | Peminjaman & Pengembalian Aset | Guru, Karyawan | `LoanController` (`/sarpar/loans`) | EV-SARP-004 | `IMPLEMENTED` |
| **FEAT-SAR-04** | Log Kerusakan & Pemeliharaan Aset | Koordinator Sarpar | `MaintenanceController` (`/sarpar/maintenance`) | EV-SARP-003 | `IMPLEMENTED` |
| **FEAT-SAR-05** | Mutasi Antar-Ruang & Disposal Aset | Koordinator Sarpar | `AssetActionController` (`/sarpar/transfers`) | EV-SARP-001 | `IMPLEMENTED` |

---

## 7. Modul Learning Management System (LMS)

| Kode Fitur | Nama Fitur | Peran Sasaran | Controller & Rute Terkait | Evidence ID | Status |
|---|---|---|---|---|---|
| **FEAT-LMS-01** | Ruang Kelas Daring Guru-Siswa | Guru, Siswa | `LmsClassroomController` (`/lms/classrooms`) | EV-LMS-001 | `IMPLEMENTED` |
| **FEAT-LMS-02** | Modul Materi Berkas & Tautan Video | Guru Pengampu | `LmsClassroomController::storeMaterial` | EV-LMS-002 | `IMPLEMENTED` |
| **FEAT-LMS-03** | Penugasan Berbatas Waktu (Deadline) | Guru Pengampu | `LmsClassroomController::storeAssignment` | EV-LMS-003 | `IMPLEMENTED` |
| **FEAT-LMS-04** | Pengumpulan Tugas Siswa (Upload) | Siswa | `LmsStudentController::submitAssignment` | EV-LMS-004 | `IMPLEMENTED` |
| **FEAT-LMS-05** | Buku Nilai & Rekap Nilai Tugas Guru | Guru Pengampu | `LmsClassroomController::gradebook` | EV-LMS-004 | `IMPLEMENTED` |

---

## 8. Modul Khusus: Daycare, SPMB, & Ekskul (SPC)

| Kode Fitur | Nama Fitur | Peran Sasaran | Controller & Rute Terkait | Evidence ID | Status |
|---|---|---|---|---|---|
| **FEAT-DAY-01** | Presensi Harian Balita | Pengasuh Daycare | `DaycareAttendanceController` (`/daycare/attendance`) | EV-SPEC-001 | `IMPLEMENTED` |
| **FEAT-DAY-02** | Log Harian Balita (Makan, Tidur, BAB) | Pengasuh Daycare | `DaycareDailyLogController` (`/daycare/daily-logs`) | EV-SPEC-001 | `IMPLEMENTED` |
| **FEAT-DAY-03** | Otorisasi Penjemput Balita (Foto) | Wali Murid, Pengasuh | `DaycareChildrenController` (`/daycare/children/{id}`) | EV-SPEC-002 | `IMPLEMENTED` |
| **FEAT-SPMB-01**| Pendaftaran Calon Siswa Baru Online | Calon Wali Murid | `PublicSpmbController` (`/spmb/daftar`) | EV-SPEC-003 | `IMPLEMENTED` |
| **FEAT-SPMB-02**| Portal Verifikasi Berkas Calon Siswa | Panitia SPMB | `AdminSpmbController` (`/spmb/admin`) | EV-SPEC-003 | `IMPLEMENTED` |
| **FEAT-EKS-01** | Master Klub Ekskul & Pelatih | Kesiswaan, Pelatih | `ExtracurricularController` (`/extracurricular`) | EV-SPEC-004 | `IMPLEMENTED` |
| **FEAT-EKS-02** | Presensi Latihan & Nilai Ekskul Siswa | Pelatih Ekskul | `ExtracurricularAttendanceController` | EV-SPEC-005 | `IMPLEMENTED` |
