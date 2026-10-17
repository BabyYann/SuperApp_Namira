# Feature Status Matrix (Matriks Status Implementasi Fitur)

Matriks ini merangkum status verifikasi faktual seluruh fitur SuperApp Namira berdasarkan audit kode sumber aktual per 16 Oktober 2026.

---

## 1. Legenda Status
- **`IMPLEMENTED`**: Fitur lengkap, teruji, rute aktif, antarmuka Vue terpasang, dan skema database mendukung penuh.
- **`PARTIAL`**: Fitur memiliki controller/antarmuka dasar, namun beberapa aksi bisnis belum terhubung tuntas.
- **`PLANNED`**: Kebutuhan masa depan yang telah dirumuskan namun belum memiliki kode implementasi.
- **`UNVERIFIED`**: Kode tersedia namun bergantung pada perangkat fisik eksternal yang belum dapat diverifikasi lokal.
- **`GAP`**: Terjadi perbedaan antara spesifikasi awal dengan implementasi kode sumber saat ini.

---

## 2. Matriks Status Fitur Komprehensif

| ID Fitur | Modul | Nama Fitur | Evidence ID | Komponen Kode Utama | Status Verifikasi |
|---|---|---|---|---|---|
| **F-01** | Yayasan | Multi-Tenancy Unit Switching | EV-CORE-004 | `UserController::switchUnit` | `IMPLEMENTED` |
| **F-02** | Yayasan | Master Data Unit Sekolah | EV-CORE-005 | `UnitController` | `IMPLEMENTED` |
| **F-03** | Yayasan | Master Tahun Akademik Aktif | EV-CORE-006 | `AcademicYearController` | `IMPLEMENTED` |
| **F-04** | Yayasan | Manajemen Pengguna & Multi-Tenancy Roles | EV-CORE-007 | `UserController` | `IMPLEMENTED` |
| **F-05** | Yayasan | Konfigurasi Lokasi Presensi Geofence | EV-EMP-002 | `AttendanceLocationController` | `IMPLEMENTED` |
| **F-06** | Yayasan | Kalender Libur Yayasan | EV-CORE-006 | `HolidayController` | `IMPLEMENTED` |
| **F-07** | Yayasan | Approval Absensi Pegawai | EV-EMP-003 | `AttendanceApprovalController` | `IMPLEMENTED` |
| **F-08** | Yayasan | Monitoring Sistem (Laravel Pulse) | EV-CORE-001 | `/pulse` Route | `IMPLEMENTED` |
| **F-09** | Yayasan | Audit Activity Log | EV-CORE-001 | `Spatie Activitylog` | `IMPLEMENTED` |
| **F-10** | Academic | Master Direktori Siswa | EV-ACAD-001 | `StudentController` | `IMPLEMENTED` |
| **F-11** | Academic | Master Direktori Guru | EV-ACAD-001 | `TeacherController` | `IMPLEMENTED` |
| **F-12** | Academic | Manajemen Rombel & Kelas | EV-ACAD-001 | `ClassroomController` | `IMPLEMENTED` |
| **F-13** | Academic | Penjadwalan Multi-Class | EV-ACAD-002 | `ScheduleController` | `IMPLEMENTED` |
| **F-14** | Academic | Jurnal KBM Mengajar Harian | EV-ACAD-003 | `TeachingJournalController` | `IMPLEMENTED` |
| **F-15** | Academic | Presensi Kehadiran Siswa di Kelas | EV-ACAD-003 | `StudentAttendanceController` | `IMPLEMENTED` |
| **F-16** | Academic | Gerbang Scan Barcode Siswa | EV-ACAD-005 | `StudentCheckinController` | `IMPLEMENTED` |
| **F-17** | Academic | Kenaikan Kelas Massal & Rollback | EV-ACAD-004 | `ClassPromotionController` | `IMPLEMENTED` |
| **F-18** | Academic | Cetak Kartu Pelajar & Rapor | EV-ACAD-001 | `StudentController` | `PARTIAL` |
| **F-19** | Employee | Presensi Mobile Selfie & GPS | EV-EMP-001 | `AttendanceController` | `IMPLEMENTED` |
| **F-20** | Employee | Validasi Radius Geofence Haversine | EV-EMP-002 | `AttendanceController` | `IMPLEMENTED` |
| **F-21** | Employee | Log Giat Harian Karyawan | EV-EMP-004 | `EmployeeActivityLogController` | `IMPLEMENTED` |
| **F-22** | Employee | Direktori Staf Non-Guru | EV-EMP-001 | `StaffController` | `IMPLEMENTED` |
| **F-23** | Employee | Pengajuan Cuti / Izin Mobile | EV-EMP-003 | `AttendanceController` | `IMPLEMENTED` |
| **F-24** | Finance | Master Pos Bayar & Rekening Bank | EV-FIN-001 | `FinanceTypeController` | `IMPLEMENTED` |
| **F-25** | Finance | Penagihan SPP Bulanan Massal | EV-FIN-001 | `StudentBillController` | `IMPLEMENTED` |
| **F-26** | Finance | Pembayaran Kasir & Kuitansi PDF | EV-FIN-002 | `StudentBillController::pay` | `IMPLEMENTED` |
| **F-27** | Finance | Unggah & Verifikasi Bukti Transfer | EV-FIN-001 | `StudentBillController` | `IMPLEMENTED` |
| **F-28** | Finance | Rekap Laporan Tunggakan Siswa | EV-FIN-003 | `FinanceReportController` | `IMPLEMENTED` |
| **F-29** | Finance | Buku Kas & Mutasi Masuk/Keluar | EV-FIN-003 | `TransactionController` | `IMPLEMENTED` |
| **F-30** | Finance | Payment Gateway Otomatis (Midtrans/Xendit) | - | - | `PLANNED` |
| **F-31** | Counseling | Master Kategori & Poin Pelanggaran | EV-COUN-001 | `ViolationCategoryController` | `IMPLEMENTED` |
| **F-32** | Counseling | Pencatatan Pelanggaran Siswa | EV-COUN-001 | `ViolationController` | `IMPLEMENTED` |
| **F-33** | Counseling | Pencatatan Prestasi & Piagam Siswa | EV-COUN-003 | `AchievementController` | `IMPLEMENTED` |
| **F-34** | Counseling | Sesi Bimbingan Konseling Privat | EV-COUN-002 | `CounselingSessionController` | `IMPLEMENTED` |
| **F-35** | Counseling | Notifikasi WA Pelanggaran ke Wali | EV-NOTIF-003 | `WhatsAppHelper` | `UNVERIFIED` |
| **F-36** | Sarpar | Master Aset Barang & Ruang | EV-SARP-001 | `InventoryController` | `IMPLEMENTED` |
| **F-37** | Sarpar | Cetak Lembar Stiker QR Code Aset | EV-SARP-002 | `StickerController` | `IMPLEMENTED` |
| **F-38** | Sarpar | Sirkulasi Peminjaman Aset | EV-SARP-004 | `LoanController` | `IMPLEMENTED` |
| **F-39** | Sarpar | Log Perbaikan Kerusakan Barang | EV-SARP-003 | `MaintenanceController` | `IMPLEMENTED` |
| **F-40** | Sarpar | Mutasi Ruang & Berita Acara Disposal | EV-SARP-001 | `AssetActionController` | `IMPLEMENTED` |
| **F-41** | LMS | Ruang Kelas Daring & Pengumuman | EV-LMS-001 | `LmsClassroomController` | `IMPLEMENTED` |
| **F-42** | LMS | Modul Materi Berkas & Tautan Video | EV-LMS-002 | `LmsClassroomController` | `IMPLEMENTED` |
| **F-43** | LMS | Penugasan Siswa Berbatas Waktu | EV-LMS-003 | `LmsClassroomController` | `IMPLEMENTED` |
| **F-44** | LMS | Pengumpulan Jawaban Tugas Siswa | EV-LMS-004 | `LmsStudentController` | `IMPLEMENTED` |
| **F-45** | LMS | Buku Rekapitulasi Nilai Guru | EV-LMS-004 | `LmsClassroomController` | `IMPLEMENTED` |
| **F-46** | Daycare | Presensi Harian Balita | EV-SPEC-001 | `DaycareAttendanceController` | `IMPLEMENTED` |
| **F-47** | Daycare | Log Makan, Tidur & BAB Balita | EV-SPEC-001 | `DaycareDailyLogController` | `IMPLEMENTED` |
| **F-48** | Daycare | Otorisasi Penjemput Berfoto | EV-SPEC-002 | `DaycareChildrenController` | `IMPLEMENTED` |
| **F-49** | Daycare | Grafik Tumbuh Kembang Fisik | EV-SPEC-001 | `DaycareGrowthController` | `IMPLEMENTED` |
| **F-50** | SPMB | Pendaftaran Calon Murid Baru Online | EV-SPEC-003 | `PublicSpmbController` | `IMPLEMENTED` |
| **F-51** | SPMB | Portal Status Pendaftaran Calon Wali | EV-SPEC-003 | `ApplicantPortalController` | `IMPLEMENTED` |
| **F-52** | SPMB | Seleksi & Verifikasi Berkas Panitia | EV-SPEC-003 | `AdminSpmbController` | `IMPLEMENTED` |
| **F-53** | Ekskul | Master Klub Ekskul & Pelatih | EV-SPEC-004 | `ExtracurricularController` | `IMPLEMENTED` |
| **F-54** | Ekskul | Presensi Sesi Latihan Mingguan | EV-SPEC-005 | `ExtracurricularAttendanceController` | `IMPLEMENTED` |
| **F-55** | Ekskul | Penginputan Nilai Rapor Ekskul | EV-SPEC-005 | `ExtracurricularGradeController` | `IMPLEMENTED` |
| **F-56** | PR | Publikasi Warta Berita & Agenda | EV-CORE-001 | `NewsController`, `EventController` | `IMPLEMENTED` |
| **F-57** | PR | Lowongan Karir & Lamaran Online | EV-CORE-001 | `JobVacancyController` | `IMPLEMENTED` |
| **F-58** | Notif | Real-time WebSocket Notifications | EV-NOTIF-004 | `Laravel Reverb` | `IMPLEMENTED` |
| **F-59** | Notif | Web Push Notification PWA (FCM) | EV-NOTIF-001 | `FcmService` | `IMPLEMENTED` |
| **F-60** | Auth | Registrasi Terbuka Siswa Publik | - | `RegisteredUserController` | `NOT_APPLICABLE` |
