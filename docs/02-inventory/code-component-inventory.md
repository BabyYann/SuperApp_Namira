# Code Component Inventory (Inventaris Komponen Kode Sumber)

Dokumen ini memuat inventaris lengkap seluruh komponen kode sumber di dalam SuperApp Namira: 75 Model Eloquent, 95 Controller, Middleware, Service Layer, Event Broadcasting, dan 160 Komponen Antarmuka Vue 3.

---

## 1. Inventaris Model Eloquent (75 Model)

### 1.1 Model Inti (`app/Models/`) — 16 Model
1. `AttendanceLocation.php` — Master titik koordinat GPS geofence dan radius toleransi presensi karyawan.
2. `Banner.php` — Spanduk pengumuman visual di beranda portal.
3. `EmployeeAttendance.php` — Rekapitulasi absensi harian karyawan (jam masuk, jam pulang, koordinat, foto selfie).
4. `Event.php` — Agenda acara dan kegiatan yayasan/unit.
5. `JobApplicant.php` — Berkas pelamar lowongan kerja yayasan.
6. `JobVacancy.php` — Formasi lowongan kerja yang dibuka oleh yayasan.
7. `News.php` — Berita dan publikasi warta sekolah.
8. `Notification.php` — Notifikasi in-app untuk pengguna.
9. `Partner.php` — Lembaga mitra industri, universitas, atau sponsor.
10. `StudentAttendance.php` — Catatan kehadiran siswa di kelas per pertemuan.
11. `StudentPickupRequest.php` — Permintaan penjemputan santri/siswa saat kepulangan gerbang.
12. `StudentTask.php` — Tugas mandiri siswa di portal siswa.
13. `Testimonial.php` — Kesaksian/testimoni wali murid atau alumni.
14. `UniversityDestination.php` — Direktori alumni yang menempuh pendidikan di PTN/Kedinasan.
15. `User.php` — Entitas autentikasi utama (multi-role Spatie Permission, profile photo, phone).
16. `UserDeviceToken.php` — Token FCM perangkat browser/HP untuk push notification.

### 1.2 Model Modul Akademik (`app/Modules/Academic/Models/`) — 12 Model
17. `Chapter.php` — Bab/materi pokok mata pelajaran.
18. `ClassPromotion.php` — Riwayat eksekusi kenaikan/kelulusan kelas massal.
19. `Classroom.php` — Rombongan belajar (rombel) permanen/aktif.
20. `ClassSchedule.php` — Jadwal mengajar mingguan (guru, mapel, kelas, multi-class gabungan).
21. `JournalAttendance.php` — Presensi siswa yang terikat pada satu sesi KBM jurnal guru.
22. `LearningObjective.php` — Tujuan Pembelajaran (TP) kurikulum merdeka.
23. `Student.php` — Data induk profil siswa (NISN, NIK, wali, tanggal lahir, status).
24. `StudentCheckin.php` — Log pemindaian barcode/QR gerbang kedatangan siswa.
25. `StudentPickupRequest.php` — Otorisasi penjemputan gerbang (Academic namespace).
26. `Subject.php` — Master mata pelajaran.
27. `Teacher.php` — Data induk pendidik/guru (NIP, gelar, spesialisasi mapel).
28. `TeachingJournal.php` — Jurnal KBM harian guru di kelas.

### 1.3 Model Modul Bimbingan Konseling (`app/Modules/Counseling/Models/`) — 4 Model
29. `Achievement.php` — Prestasi, kejuaraan, dan piagam penghargaan siswa.
30. `CounselingSession.php` — Sesi konseling privat/kelompok guru BK dengan siswa.
31. `Violation.php` — Rekam insiden pelanggaran tata tertib siswa.
32. `ViolationCategory.php` — Kategori pelanggaran beserta bobot sanksi poin.

### 1.4 Model Modul Daycare (`app/Modules/Daycare/Models/`) — 7 Model
33. `DaycareAttendance.php` — Presensi harian anak balita daycare.
34. `DaycareAuthorizedPickup.php` — Wali penjemput balita terotorisasi lengkap dengan foto.
35. `DaycareCaregiverSchedule.php` — Jadwal tugas pengasuh balita (caregiver).
36. `DaycareChildProfile.php` — Profil anak daycare (golongan darah, riwayat alergi).
37. `DaycareDailyLog.php` — Log aktivitas harian (menu makan, tidur, BAB/BAK, mood).
38. `DaycareDevelopmentalJournal.php` — Catatan stimulasi tumbuh kembang motorik/sosial.
39. `DaycareGrowthRecord.php` — Catatan berkala fisik anak (tinggi badan, berat badan, lingkar kepala).

### 1.5 Model Modul Kepegawaian (`app/Modules/Employee/Models/`) — 2 Model
40. `EmployeeActivityLog.php` — Jurnal log giat kerja harian staf.
41. `Staff.php` — Profil staf kepegawaian non-guru (NIK, divisi, masa kerja).

### 1.6 Model Modul Ekstrakurikuler (`app/Modules/Extracurricular/Models/`) — 6 Model
42. `Extracurricular.php` — Data klub ekstrakurikuler.
43. `ExtracurricularAttendance.php` — Presensi sesi latihan mingguan ekskul.
44. `ExtracurricularGrade.php` — Penilaian akhir nilai rapor ekskul siswa.
45. `ExtracurricularInstructor.php` — Profil pelatih/instruktur ekskul.
46. `ExtracurricularMember.php` — Registrasi keanggotaan siswa pada ekskul.
47. `ExtracurricularSession.php` — Sesi pertemuan latihan ekskul.

### 1.7 Model Modul Keuangan (`app/Modules/Finance/Models/`) — 6 Model
48. `BillPayment.php` — Bukti transaksi pelunasan/cicilan tagihan SPP.
49. `FinanceAccount.php` — Rekening kas dan bank unit sekolah/yayasan.
50. `FinanceType.php` — Jenis pos tagihan (SPP, Gedung, Seragam, Ujian).
51. `StudentBill.php` — Surat tagihan pembayaran siswa per bulan/semester.
52. `StudentDiscount.php` — Pemotongan diskon/keringanan biaya atau beasiswa.
53. `Transaction.php` — Jurnal transaksi keuangan arus kas masuk dan kas keluar.

### 1.8 Model Modul LMS (`app/Modules/LMS/Models/`) — 7 Model
54. `LmsAnnouncement.php` — Pengumuman kelas daring.
55. `LmsAssignment.php` — Penugasan terstruktur dari guru ke siswa.
56. `LmsClassroom.php` — Ruang kelas digital LMS.
57. `LmsMaterial.php` — Materi pokok modul pembelajaran daring.
58. `LmsMaterialFile.php` — Lampiran berkas PDF/dokumen modul ajar.
59. `LmsSubmission.php` — Pengumpulan jawaban tugas dari siswa.
60. `LmsSubmissionFile.php` — Berkas lampiran jawaban tugas siswa.

### 1.9 Model Modul Sarpras (`app/Modules/Sarpar/Models/`) — 8 Model
61. `Category.php` — Kategori jenis barang aset (elektronik, mebel, kendaraan).
62. `Disposal.php` — Berita acara penghapusan aset rusak/dijual.
63. `Inventory.php` — Master aset barang inventaris sekolah.
64. `Loan.php` — Formulir peminjaman dan pengembalian aset.
65. `MaintenanceLog.php` — Riwayat perbaikan dan biaya servis barang rusak.
66. `Room.php` — Master ruang dan gedung unit sekolah.
67. `Transfer.php` — Mutasi perpindahan aset antar-ruang atau antar-unit.
68. `UsageLog.php` — Log penggunaan ruang laboratorium/aula.

### 1.10 Model Modul SPMB & Yayasan — 7 Model
69. `SpmbApplicant.php` (`App\Modules\SPMB\Models`) — Formulir calon siswa pendaftar baru.
70. `SpmbSetting.php` (`App\Modules\SPMB\Models`) — Konfigurasi gelombang dan kuota PPDB.
71. `AcademicYear.php` (`App\Modules\Yayasan\Models`) — Tahun akademik aktif sekolah.
72. `Holiday.php` (`App\Modules\Yayasan\Models`) — Kalender libur nasional dan yayasan.
73. `SystemSetting.php` (`App\Modules\Yayasan\Models`) — Nilai parameter global sistem.
74. `Unit.php` (`App\Modules\Yayasan\Models`) — Multi-tenancy unit sekolah (KB, TK, SD, SMP, Daycare).
75. `WhatsAppQueue.php` (`App\Modules\Yayasan\Models`) — Antrean pesan keluar WhatsApp.

---

## 2. Inventaris Controller (95 Controller)

### 2.1 API Controllers (`app/Http/Controllers/Api/`) — 13 Controller
- `AcademicApiController.php` — Endpoints mobile jadwal, siswa, presensi kelas, jurnal.
- `AdminManagementApiController.php` — Endpoints manajemen user dan roles mobile.
- `AttendanceController.php` — Endpoints check-in GPS selfie dan riwayat absensi pegawai.
- `AuthController.php` — Login, logout, user profile, dan switch unit via Sanctum.
- `CounselingApiController.php` — Pelanggaran, sesi konseling, prestasi siswa.
- `DashboardController.php` — Agregat metrik dashboard multi-role.
- `EmployeeApiController.php` — Direktori staf dan rekap absensi.
- `FinanceApiController.php` — Tagihan SPP, rincian bill, dan mutasi kas.
- `LmsApiController.php` — Kelas LMS, materi, tugas, upload, dan penilaian guru.
- `NotificationApiController.php` — Daftar notifikasi in-app dan status keterbacaan.
- `PublicRelationsApiController.php` — Berita, agenda kegiatan, destinasi alumni.
- `SarparApiController.php` — Aset sarpras, log pemeliharaan, peminjaman barang.
- `StudentPortalApiController.php` — Portal siswa: tugas, jadwal, nilai, permintaan jemput gerbang.

### 2.2 Core & Auth Controllers (`app/Http/Controllers/`) — 12 Controller
- `Controller.php` — Base controller Laravel.
- `ProfileController.php` — Profil user, ganti password, avatar.
- `PublicCareerController.php` — Lowongan karir dan lamaran pelamar.
- `PushTokenController.php` — Pendaftaran web push FCM token.
- `StudentPortalController.php` — Antarmuka web portal siswa.
- `StudentTaskController.php` — CRUD tugas mandiri siswa.
- `AuthenticatedSessionController.php` — Login web Inertia.
- `ConfirmablePasswordController.php` — Konfirmasi sandi aman.
- `EmailVerificationNotificationController.php` & `EmailVerificationPromptController.php` & `VerifyEmailController.php` — Alur verifikasi email.
- `NewPasswordController.php` & `PasswordController.php` & `PasswordResetLinkController.php` — Alur lupa sandi.
- `RegisteredUserController.php` — Registrasi akun.

### 2.3 Module Controllers (`app/Modules/*/Controllers/`) — 70 Controller
- **Academic (10):** `ClassPromotionController`, `ClassroomController`, `LearningObjectiveController`, `ScheduleController`, `StudentAttendanceController`, `StudentCheckinController`, `StudentController`, `SubjectController`, `TeacherController`, `TeachingJournalController`.
- **Counseling (4):** `AchievementController`, `CounselingSessionController`, `ViolationCategoryController`, `ViolationController`.
- **Daycare (6):** `DaycareAttendanceController`, `DaycareChildrenController`, `DaycareDailyLogController`, `DaycareGrowthController`, `DaycareJournalController`, `DaycareReportController`.
- **Employee (3):** `AttendanceController`, `EmployeeActivityLogController`, `StaffController`.
- **Extracurricular (5):** `ExtracurricularController`, `ExtracurricularGradeController`, `ExtracurricularInstructorController`, `ExtracurricularMemberController`, `ExtracurricularSessionController`.
- **Finance (6):** `FinanceAccountController`, `FinanceDashboardController`, `FinanceReportController`, `FinanceTypeController`, `StudentBillController`, `TransactionController`.
- **LMS (2):** `LmsClassroomController`, `LmsStudentController`.
- **PublicRelations (6):** `BannerController`, `EventController`, `NewsController`, `PartnerController`, `TestimonialController`, `UniversityDestinationController`.
- **Sarpar (9):** `AssetActionController`, `CategoryController`, `DashboardController`, `InventoryController`, `LoanController`, `MaintenanceController`, `RoomController`, `StickerController`, `UsageLogController`.
- **SPMB (3):** `AdminSpmbController`, `ApplicantPortalController`, `PublicSpmbController`.
- **Yayasan (16):** `AcademicYearController`, `AttendanceApprovalController`, `AttendanceDataController`, `AttendanceLocationController`, `HolidayController`, `JobApplicantController`, `JobVacancyController`, `MonitoringController`, `PembinaDashboardController`, `SettingController`, `UnitController`, `UserController`, dll.

---

## 3. Inventaris Komponen Antarmuka Vue 3 (160 Berkas)

### 3.1 App Layouts (`resources/js/Layouts/`) — 4 Layout
1. `AuthenticatedLayout.vue` — Layout desktop master (sidebar glassmorphism, topbar, toast listener real-time WebSocket Reverb).
2. `MobileAppShell.vue` — Layout mobile PWA (app header, unit selector pill, bottom navigation bar terpadu).
3. `GuestLayout.vue` — Shell halaman login, reset password, dan formulir publik.
4. `StudentLayout.vue` — Shell khusus portal mandiri siswa dan wali santri.

### 3.2 Shared UI Components (`resources/js/Components/`) — 25 Komponen
Komponen atomik dan molekul siap pakai:
`Modal.vue`, `Dropdown.vue`, `PrimaryButton.vue`, `SecondaryButton.vue`, `DangerButton.vue`, `TextInput.vue`, `InputLabel.vue`, `InputError.vue`, `Checkbox.vue`, `Pagination.vue`, `OfflineStatusBanner.vue`, `BarcodeScannerModal.vue`, dll.

### 3.3 Halaman Inertia (`resources/js/Pages/`) — 131 Halaman
- **Academic (19 Halaman):** `Schedules/Index.vue`, `Classrooms/Index.vue`, `Students/Index.vue`, `Teachers/Index.vue`, `TeachingJournals/Index.vue`, `Attendance/Index.vue`, `Promotions/Index.vue`, dll.
- **Yayasan (18 Halaman):** `Users/Index.vue`, `Units/Index.vue`, `AcademicYears/Index.vue`, `Attendance/Approval.vue`, `Monitoring/Index.vue`, `Settings/Index.vue`, dll.
- **Finance (11 Halaman):** `Bills/Index.vue`, `Transactions/Index.vue`, `Accounts/Index.vue`, `Reports/Index.vue`, `Arrears/Index.vue`, dll.
- **Employee (9 Halaman):** `Attendance/Index.vue`, `ActivityLogs/Index.vue`, `Staff/Index.vue`, dll.
- **Counseling (8 Halaman):** `Violations/Index.vue`, `Sessions/Index.vue`, `Achievements/Index.vue`, `Categories/Index.vue`, dll.
- **Sarpar (12 Halaman):** `Inventories/Index.vue`, `Rooms/Index.vue`, `Loans/Index.vue`, `Maintenance/Index.vue`, `Stickers/Print.vue`, dll.
- **LMS (10 Halaman):** `Classrooms/Index.vue`, `Materials/Show.vue`, `Assignments/Show.vue`, `Submissions/Show.vue`, `Gradebook.vue`, dll.
- **Daycare (7 Halaman):** `Children/Index.vue`, `DailyLogs/Index.vue`, `Attendance/Index.vue`, `Growth/Index.vue`, dll.
- **Extracurricular (6 Halaman):** `Index.vue`, `Members.vue`, `Sessions.vue`, `Attendance.vue`, `Grades.vue`.
- **SPMB (4 Halaman):** `Register.vue`, `Portal.vue`, `Admin/Index.vue`.
- **Public & PR (14 Halaman):** `News/Index.vue`, `Events/Index.vue`, `Career/Index.vue`, `Testimonials/Index.vue`, `Destinations/Index.vue`.
- **Student Portal (8 Halaman):** `Dashboard.vue`, `Tasks/Index.vue`, `Bills/Index.vue`, `Grades/Index.vue`, `Pickup/Index.vue`.
- **Auth & Profile (5 Halaman):** `Login.vue`, `ForgotPassword.vue`, `ResetPassword.vue`, `Profile/Edit.vue`.
