# Module Inventory (Inventaris 11 Modul Aplikasi)

Dokumen ini memetakan seluruh 11 modul fungsional yang beroperasi di dalam SuperApp Namira beserta direktori, namespace, model, controller, halaman antarmuka, dan status implementasinya.

---

## 1. Modul Yayasan & Tata Kelola Inti (`App\Modules\Yayasan`)
- **Tanggung Jawab:** Tata kelola multi-tenancy unit sekolah, pengelolaan tahun ajaran aktif, manajemen direktori pengguna global, pemantauan status sistem (Pulse), audit trail log, kalender libur, konfigurasi radius presensi geofence, dan approval absensi pegawai.
- **Model (5):** `AcademicYear`, `Holiday`, `SystemSetting`, `Unit`, `WhatsAppQueue` (+ Core `AttendanceLocation`, `User`).
- **Controller (12):** `AcademicYearController`, `AttendanceApprovalController`, `AttendanceDataController`, `AttendanceLocationController`, `HolidayController`, `JobApplicantController`, `JobVacancyController`, `MonitoringController`, `PembinaDashboardController`, `SettingController`, `UnitController`, `UserController`.
- **Rute:** `routes/yayasan.php` (182 endpoint web).
- **Halaman Vue:** `resources/js/Pages/Yayasan/*` (18 halaman termasuk Users, Units, AcademicYears, Attendance, Monitoring, Settings).
- **Status Implementasi:** `IMPLEMENTED`

---

## 2. Modul Akademik & Kesiswaan (`App\Modules\Academic`)
- **Tanggung Jawab:** Master data siswa, guru pengampu, mata pelajaran, rombel/kelas, penyusunan jadwal pelajaran, jurnal KBM mengajar, presensi siswa di kelas, gerbang scan QR kedatangan siswa (Gate Scan), dan mesin kenaikan kelas/kelulusan massal (*Class Promotion*).
- **Model (12):** `Chapter`, `ClassPromotion`, `Classroom`, `ClassSchedule`, `JournalAttendance`, `LearningObjective`, `Student`, `StudentCheckin`, `StudentPickupRequest`, `Subject`, `Teacher`, `TeachingJournal`.
- **Controller (10):** `ClassPromotionController`, `ClassroomController`, `LearningObjectiveController`, `ScheduleController`, `StudentAttendanceController`, `StudentCheckinController`, `StudentController`, `SubjectController`, `TeacherController`, `TeachingJournalController`.
- **Rute:** Ditampung di dalam grup `routes/yayasan.php` & `routes/api.php`.
- **Halaman Vue:** `resources/js/Pages/Academic/*` (19 halaman termasuk Classrooms, Schedules, Journals, Attendance, Promotions, Students, Teachers).
- **Status Implementasi:** `IMPLEMENTED`

---

## 3. Modul Kepegawaian (`App\Modules\Employee`)
- **Tanggung Jawab:** Manajemen staf non-guru, absensi mobile PWA berbasis radius GPS & foto selfie (geofencing Haversine), rekap log giat harian pegawai, dan pengajuan izin/cuti/sakit.
- **Model (2):** `EmployeeActivityLog`, `Staff` (+ Core `EmployeeAttendance`).
- **Controller (3):** `AttendanceController`, `EmployeeActivityLogController`, `StaffController`.
- **Rute:** `routes/employee.php` (6 endpoint web) & `routes/api.php`.
- **Halaman Vue:** `resources/js/Pages/Employee/*` (9 halaman termasuk Attendance, ActivityLogs, Staff Directory).
- **Status Implementasi:** `IMPLEMENTED`

---

## 4. Modul Keuangan (`App\Modules\Finance`)
- **Tanggung Jawab:** Master pos jenis bayar, master rekening bank yayasan/unit, penagihan SPP bulanan siswa, pengelolaan diskon/beasiswa, pencatatan transaksi penerimaan kas, verifikasi bukti transfer pembayaran dari wali murid, dan laporan buku besar/arus kas.
- **Model (6):** `BillPayment`, `FinanceAccount`, `FinanceType`, `StudentBill`, `StudentDiscount`, `Transaction`.
- **Controller (6):** `FinanceAccountController`, `FinanceDashboardController`, `FinanceReportController`, `FinanceTypeController`, `StudentBillController`, `TransactionController`.
- **Rute:** Ditampung di dalam grup `routes/yayasan.php` & `routes/api.php`.
- **Halaman Vue:** `resources/js/Pages/Finance/*` (11 halaman termasuk Bills, Payments, Accounts, Reports, Arrears/Tunggakan).
- **Status Implementasi:** `IMPLEMENTED`

---

## 5. Modul Bimbingan & Konseling (`App\Modules\Counseling`)
- **Tanggung Jawab:** Pencatatan dan pembinaan sesi konseling siswa (individu/kelompok), master kategori pelanggaran tata tertib, pencatatan insiden pelanggaran siswa dengan sanksi poin, riwayat prestasi/kejuaraan siswa, dan notifikasi otomatis orang tua via WhatsApp.
- **Model (4):** `Achievement`, `CounselingSession`, `Violation`, `ViolationCategory`.
- **Controller (4):** `AchievementController`, `CounselingSessionController`, `ViolationCategoryController`, `ViolationController`.
- **Rute:** `routes/counseling.php` (28 endpoint web) & `routes/api.php`.
- **Halaman Vue:** `resources/js/Pages/Counseling/*` (8 halaman termasuk Sessions, Violations, Achievements, Categories).
- **Status Implementasi:** `IMPLEMENTED`

---

## 6. Modul Sarana & Prasarana (`App\Modules\Sarpar`)
- **Tanggung Jawab:** Registrasi inventaris aset sekolah, penempatan lokasi ruang/gedung, cetak label stiker QR code aset, pencatatan log pemeliharaan/perbaikan barang rusak, sirkulasi peminjaman barang, dan log mutasi/disposal aset yang tidak layak pakai.
- **Model (8):** `Category`, `Disposal`, `Inventory`, `Loan`, `MaintenanceLog`, `Room`, `Transfer`, `UsageLog`.
- **Controller (9):** `AssetActionController`, `CategoryController`, `DashboardController`, `InventoryController`, `LoanController`, `MaintenanceController`, `RoomController`, `StickerController`, `UsageLogController`.
- **Rute:** `routes/sarpar.php` (29 endpoint web) & `routes/api.php`.
- **Halaman Vue:** `resources/js/Pages/Sarpar/*` (12 halaman termasuk Inventories, Rooms, Loans, Maintenance, QR Stickers).
- **Status Implementasi:** `IMPLEMENTED`

---

## 7. Modul Learning Management System (`App\Modules\LMS`)
- **Tanggung Jawab:** Pembelajaran daring interaktif, ruang kelas digital guru-siswa, publikasi pengumuman kelas, modul unggah materi pelajaran (PDF/berkas), pembuatan tugas siswa berbatas waktu, upload tugas siswa, dan rekap penilaian tugas (gradebook).
- **Model (7):** `LmsAnnouncement`, `LmsAssignment`, `LmsClassroom`, `LmsMaterial`, `LmsMaterialFile`, `LmsSubmission`, `LmsSubmissionFile`.
- **Controller (2):** `LmsClassroomController`, `LmsStudentController` (+ API `LmsApiController`).
- **Rute:** `routes/lms.php` (13 endpoint web) & `routes/api.php`.
- **Halaman Vue:** `resources/js/Pages/LMS/*` (10 halaman termasuk Classrooms, Materials, Assignments, Submissions, Gradebook).
- **Status Implementasi:** `IMPLEMENTED`

---

## 8. Modul Hubungan Masyarakat / PR (`App\Modules\PublicRelations`)
- **Tanggung Jawab:** Publikasi warta & berita sekolah, agenda kegiatan humas, direktori mitra industri/lembaga, galeri alumni yang diterima di perguruan tinggi negeri (PTN), testimoni wali murid, spanduk/banner promo, dan lowongan karir yayasan.
- **Model (Core):** `News`, `Event`, `Partner`, `UniversityDestination`, `Testimonial`, `Banner`, `JobVacancy`, `JobApplicant`.
- **Controller (6):** `BannerController`, `EventController`, `NewsController`, `PartnerController`, `TestimonialController`, `UniversityDestinationController`.
- **Rute:** `routes/public-relations.php` (47 endpoint web) & `routes/public.php`.
- **Halaman Vue:** `resources/js/Pages/PublicRelations/*` & `resources/js/Pages/Public/*` (14 halaman).
- **Status Implementasi:** `IMPLEMENTED`

---

## 9. Modul Penitipan Anak / Daycare (`App\Modules\Daycare`)
- **Tanggung Jawab:** Manajemen khusus unit Daycare Namira: presensi balita, otorisasi penjemput balita (*authorized pickup* dengan foto), rekap log harian (asupan makan, durasi tidur siang, frekuensi buang air), pemantauan grafik tumbuh kembang (TB/BB), dan jurnal stimulasi anak.
- **Model (7):** `DaycareAttendance`, `DaycareAuthorizedPickup`, `DaycareCaregiverSchedule`, `DaycareChildProfile`, `DaycareDailyLog`, `DaycareDevelopmentalJournal`, `DaycareGrowthRecord`.
- **Controller (6):** `DaycareAttendanceController`, `DaycareChildrenController`, `DaycareDailyLogController`, `DaycareGrowthController`, `DaycareJournalController`, `DaycareReportController`.
- **Rute:** `routes/daycare.php` (16 endpoint web).
- **Halaman Vue:** `resources/js/Pages/Daycare/*` (7 halaman termasuk Children, DailyLogs, Growth, Attendance, Reports).
- **Status Implementasi:** `IMPLEMENTED`

---

## 10. Modul SPMB / PPDB (`App\Modules\SPMB`)
- **Tanggung Jawab:** Sistem Penerimaan Murid Baru secara online: formulir registrasi calon siswa baru, pemilihan unit sekolah tujuan, pengunggahan dokumen prasyarat (KK, Akta), portal status pendaftaran calon wali murid, seleksi verifikasi panitia, dan otomatisasi penetapan siswa diterima.
- **Model (2):** `SpmbApplicant`, `SpmbSetting`.
- **Controller (3):** `AdminSpmbController`, `ApplicantPortalController`, `PublicSpmbController`.
- **Service (1):** `SpmbService`.
- **Rute:** `routes/spmb.php` (6 endpoint web & public portal).
- **Halaman Vue:** `resources/js/Pages/SPMB/*` (4 halaman termasuk Register, Portal, Admin Verification).
- **Status Implementasi:** `IMPLEMENTED`

---

## 11. Modul Ekstrakurikuler (`App\Modules\Extracurricular`)
- **Tanggung Jawab:** Pengelolaan klub bakat/minat (Pramuka, Futsal, Tari, Robotik, Tahfidz), master pelatih/instruktur luar, pendaftaran keanggotaan siswa, jadwal sesi latihan mingguan, absensi kehadiran sesi latihan, dan penilaian rapor ekskul siswa.
- **Model (6):** `Extracurricular`, `ExtracurricularAttendance`, `ExtracurricularGrade`, `ExtracurricularInstructor`, `ExtracurricularMember`, `ExtracurricularSession`.
- **Controller (5):** `ExtracurricularController`, `ExtracurricularGradeController`, `ExtracurricularInstructorController`, `ExtracurricularMemberController`, `ExtracurricularSessionController`.
- **Rute:** `routes/extracurricular.php` (12 endpoint web).
- **Halaman Vue:** `resources/js/Pages/Extracurricular/*` (6 halaman termasuk Clubs, Members, Sessions, Attendance, Grades).
- **Status Implementasi:** `IMPLEMENTED`
