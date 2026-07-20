# SUPERAPP NAMIRA - MOBILE APP PRD
# Product Requirements Document v1.0
# Platform: Flutter (Android primary, iOS secondary)
# Backend: Laravel 12 REST API (Sanctum)
# Target: 15 roles in single app
# NOTE: No emoji as UI icons. Use Heroicons v2 outline only.

============================================================
SECTION 1. PRODUCT OVERVIEW
============================================================

SuperApp Namira adalah aplikasi mobile untuk Yayasan Namira yang
mengelola multi-unit sekolah (SD, SMP, SMA, plus unit non-formal).
Aplikasi ini menyatukan seluruh alur kerja sekolah: akademik,
presensi karyawan & siswa, keuangan, konseling, sarana prasarana,
pembelajaran (LMS), humas, portal siswa, dan kepegawaian.

Single app, multi-role. Login menghasilkan token Sanctum. Setelah
login, role + unit aktif user menentukan menu yang tampil dan data
yang di-filter (unit scoping wajib: team_id = unit_id).

Tech stack mobile:
- Flutter 3.x, Dart
- State management: Riverpod (Providers per feature)
- Routing: GoRouter
- HTTP: Dio + interceptor (inject token, handle 401)
- Storage lokal: shared_preferences (token, active_unit_id, profile)
- Maps: flutter_map / leaflet (GPS presensi)
- Camera: camera package (bukti foto presensi)
- Charts: fl_chart (dashboard keuangan/akademik)

============================================================
SECTION 2. BRAND IDENTITY & DESIGN SYSTEM
============================================================

2.1 BRAND COLORS
- Primary (Namira Teal):    #009688
- Secondary (Namira Navy):  #003366
- Accent (Amber):           #FF6F00
- Success:                  #2E7D32
- Warning:                  #F57F17
- Error:                    #C62828
- Info:                     #0277BD
- Background:               #F5F7FA
- Surface:                  #FFFFFF
- Border:                   #E8ECF0
- Text Primary:            #1A1F36
- Text Secondary:          #6B7280

2.2 TYPOGRAPHY (Plus Jakarta Sans)
- H1: 24px / 700
- H2: 20px / 700
- H3: 16px / 600
- Body: 14px / 400
- Caption: 12px / 400
- Label: 11px / 600 (uppercase, letter-spacing 0.5)

2.3 SPACING SCALE (base 4px)
- xs 4, sm 8, md 12, lg 16, xl 20, 2xl 24, 3xl 32, 4xl 48

2.4 BORDER RADIUS
- sm 8, md 12, lg 16, xl 24, full 9999

2.5 SHADOW
- sm: 0 1 2 rgba(0,0,0,0.06)
- md: 0 4 12 rgba(0,0,0,0.08)
- lg: 0 8 24 rgba(0,0,0,0.12)

2.6 ATOM WIDGETS (15 shared)
AppButton, AppTextField, AppCard, AppBadge, AppAvatar,
AppBottomNav, AppDrawer, AppAppBar, AppShimmer, AppEmptyState,
AppErrorState, AppLoadingState, AppStatCard, AppListItem, AppChip

2.7 ICON RULE
NO emoji. Use Heroicons v2 outline SVG. For status use CSS badges
with color (Alumni=blue, Kunjungan=green, Nasional=amber,
Internasional=purple).

============================================================
SECTION 3. NAVIGATION (HYBRID)
============================================================

3.1 BOTTOM NAV (5 tabs, convex center)
- Tab 1: Beranda      -> /home
- Tab 2: Akademik     -> /academic
- Tab 3: Presensi     -> /attendance  (CENTER, convex raised button,
                                        fingerprint icon, employee check-in)
- Tab 4: Keuangan     -> /finance
- Tab 5: Akun         -> /profile
Package: convex_bottom_bar

3.2 DRAWER (hamburger)
- LMS
- Konseling
- Sarpar
- Humas (Public Relations)
- Kepegawaian (Employee)
- Notifikasi
- Pengaturan (unit switcher inside)

3.3 ROUTES (GoRouter)
/splash
/login
/home
/academic
/academic/students
/academic/student/:id
/academic/schedule
/academic/journal
/academic/journal/create
/academic/student-attendance
/academic/recap
/attendance
/attendance/history
/finance
/finance/bills
/finance/bill/:id
/finance/transactions
/counseling
/counseling/session/:id
/lms
/lms/classroom/:id
/lms/material/:id
/lms/assignment/:id
/lms/assignment/:id/submit
/lms/gradebook
/pr
/pr/news/:id
/pr/events
/pr/destinations
/student (student portal dashboard)
/student/tasks
/student/pickup
/employee
/employee/staff
/employee/attendance
/notifications
/profile
/settings

============================================================
SECTION 4. ROLE ACCESS MATRIX (15 roles)
============================================================

Global (bypass unit scope):
- super_admin_yayasan : all modules
- admin_yayasan       : all modules
- staff_yayasan       : admin modules (limited)

Unit-scoped:
- admin_unit          : all unit modules
- kepala_sekolah      : all unit modules + approve
- teacher             : Akademik, Presensi, Konseling(view), LMS, Kepegawaian(personal)
- wali_kelas          : + Student Attendance, Recap, Student Portal view
- bk                 : + full Konseling
- siswa              : Akademik(jadwal saya), Keuangan(tagihan), Konseling(personal), LMS, Student Portal
- finance            : full Keuangan
- staff_admin_keuangan: Keuangan (entry)
- koordinator_sarpar : full Sarpar
- staff_sarpar       : Sarpar (limited)
- humas              : full PR
- staff_kepegawaian  : Employee data

After login, /dashboard auto-redirects siswa -> /student.

============================================================
SECTION 5. FEATURE MODULES & SCREENS (~40 screens)
============================================================

5.1 AUTH
- Splash: logo teal/navy, check token, route by role
- Login: email, password, "Masuk" button, error state

5.2 HOME (role-based dashboard)
- AppBar: unit switcher, avatar, notification bell
- Stat cards: jumlah siswa, guru, tagihan bulan ini, presensi hari ini
- Quick actions: Presensi, Input Jurnal, Cek Tagihan, Lihat Jadwal
- Recent activity: log terakhir per module
- Empty state jika tidak ada data

5.3 ATTENDANCE (Presensi Karyawan)
- Check-in/out screen: map (GPS current location), kamera bukti,
  tombol "Presensi Masuk" / "Presensi Pulang"
- Validasi radius dari lokasi sekolah (config)
- History: list presensi bulan ini, status (tepat/terlambat)

5.4 ACADEMIC
- Schedule: jadwal mengajar/hari ini (guru) atau jadwal kelas (siswa)
- Students: list siswa per kelas, search, filter
- Student Detail: profil, nilai, presensi, catatan
- Journal: list jurnal mengajar, form input (mapel, materi, hadir)
- Student Attendance: input kehadiran siswa per pertemuan
- Recap: rekap presensi & nilai per periode

5.5 FINANCE
- Dashboard: chart pemasukan/pengeluaran, total tagihan, kolektibilitas
- Bills: list tagihan siswa, filter status (lunas/belum)
- Bill Detail: rincian, VA number, status bayar
- Transactions: mutasi keuangan unit

5.6 COUNSELING (BK)
- Tabs: Pelanggaran, Sesi Konseling, Prestasi
- List per tab, search
- Session Detail: siswa, kategori, catatan, tindak lanjut

5.7 SARPAR (Sarana Prasarana)
- Dashboard: ringkasan aset, rusak, dipinjam
- Inventory: list barang, detail (kondisi, lokasi, qr)
- Rooms: list ruangan, jadwal pakai
- Maintenance: list perbaikan, status
- Loans: peminjaman barang/ruangan, form

5.8 LMS
- Classrooms: kelas yang diampu/diikuti
- Classroom Detail: materi + tugas
- Material Detail: isi materi (teks/file)
- Assignment Detail: soal tugas, deadline
- Submission: kirim tugas (file)
- Gradebook: nilai per siswa

5.9 PUBLIC RELATIONS (Humas)
- News: list berita, detail (HTML)
- Events: agenda kegiatan
- Destinations: tujuan kunjungan/sekolah mitra

5.10 STUDENT PORTAL
- Dashboard: tagihan saya, tugas saya, jadwal
- Tasks: daftar tugas, deadline
- Pickup Request: permintaan jemput (orang tua)

5.11 EMPLOYEE (Kepegawaian)
- Staff List: pegawai unit, detail
- Attendance: rekap presensi karyawan

5.12 NOTIFICATIONS
- List notifikasi (push/in-app), read/unread, mark all read

5.13 PROFILE & SETTINGS
- Profile: data diri, role, unit
- Settings: ganti unit aktif (unit switcher), logout, theme

============================================================
SECTION 6. API PLAN (68 total = 31 existing + 37 new)
============================================================

Base URL: http://127.0.0.1:8000/api
Auth: Bearer Sanctum token in header.
All list responses: { data: [...], meta: { current_page, last_page, total } }
Error: { message: "...", errors: {...} } with proper HTTP code.

6.1 EXISTING ENDPOINTS (31, NO change)
- POST   /auth/login
- POST   /auth/logout
- GET    /auth/me
- POST   /auth/refresh (optional)
- GET    /dashboard            (role-based stats)
- Attendance:
  - GET  /attendance/config
  - POST /attendance/check-in
  - POST /attendance/check-out
  - GET  /attendance/history
  - GET  /attendance/today
- Academic:
  - GET  /academic/schedule
  - GET  /academic/students
  - GET  /academic/students/:id
  - GET  /academic/journal
  - POST /academic/journal
- Finance:
  - GET  /finance/dashboard
  - GET  /finance/bills
  - GET  /finance/bills/:id
  - GET  /finance/transactions
- Counseling:
  - GET  /counseling/violations
  - GET  /counseling/sessions
  - GET  /counseling/sessions/:id
  - GET  /counseling/achievements
  - POST /counseling/session (store)
- Sarpar:
  - GET  /sarpras/dashboard
  - GET  /sarpras/inventory
  - GET  /sarpras/inventory/:id
  - GET  /sarpras/rooms
  - GET  /sarpras/maintenance
  - POST /sarpras/loan

6.2 NEW ENDPOINTS (37)

--- LMS (13) ---
- GET    /lms/classrooms            -> list kelas diampu/diikuti
- GET    /lms/classrooms/:id        -> detail kelas + materi + tugas
- GET    /lms/materials/:id         -> detail materi
- POST   /lms/materials             -> buat materi (guru)
- GET    /lms/assignments/:id       -> detail tugas
- POST   /lms/assignments           -> buat tugas (guru)
- POST   /lms/assignments/:id/submit-> kirim jawaban siswa
- GET    /lms/submissions/:id       -> detail submission
- POST   /lms/submissions/:id/grade -> nilai (guru)
- GET    /lms/gradebook/:classroom  -> nilai per siswa
- GET    /lms/announcements         -> pengumuman kelas
- POST   /lms/announcements         -> buat pengumuman
- GET    /lms/my-tasks              -> tugas saya (siswa)

--- PUBLIC RELATIONS (6) ---
- GET    /pr/news                   -> list berita
- GET    /pr/news/:id               -> detail berita
- GET    /pr/events                 -> list agenda
- GET    /pr/events/:id             -> detail agenda
- GET    /pr/destinations           -> list tujuan kunjungan
- GET    /pr/destinations/:id       -> detail

--- STUDENT PORTAL (8) ---
- GET    /student/dashboard         -> tagihan+tugas+jadwal saya
- GET    /student/tasks             -> daftar tugas saya
- POST   /student/tasks/:id/complete
- GET    /student/pickup            -> list permintaan jemput
- POST   /student/pickup            -> buat permintaan jemput
- GET    /student/grades            -> nilai saya
- GET    /student/schedule          -> jadwal saya
- GET    /student/profile           -> profil siswa saya

--- ADMIN MANAGEMENT (5) ---
- GET    /admin/users               -> list user (paginate, filter role/unit)
- POST   /admin/users               -> buat user + assign role
- PUT    /admin/users/:id           -> update user
- GET    /admin/roles               -> list role + permission
- POST   /admin/units/:id/switch    -> set active_unit_id (return new token/session)

--- EMPLOYEE DATA (2) ---
- GET    /employee/staff            -> list pegawai unit
- GET    /employee/attendance       -> rekap presensi karyawan

--- NOTIFICATIONS (3) ---
- GET    /notifications             -> list notif
- POST   /notifications/:id/read    -> mark read
- POST   /notifications/read-all    -> mark all read

============================================================
SECTION 7. FLUTTER ARCHITECTURE (~150 files)
============================================================

lib/
  config/
    env.dart            (baseUrl, timeout)
    routes.dart         (GoRouter)
    theme.dart          (AppColors, AppTheme, typography, spacing)
    api_endpoints.dart  (all 68 endpoints constants)
  core/
    api/
      api_client.dart   (Dio + interceptor token/401)
      api_exceptions.dart
      api_response.dart (generic parse)
    services/
      auth_service.dart
      storage_service.dart (token, active_unit_id, profile)
      location_service.dart (GPS)
    utils/
      formatters.dart   (currency id_ID, date)
      validators.dart
    constants/
      app_constants.dart
    widgets/  (15 atom widgets listed in 2.6)
      app_button.dart, app_text_field.dart, app_card.dart,
      app_badge.dart, app_avatar.dart, app_bottom_nav.dart,
      app_drawer.dart, app_app_bar.dart, app_shimmer.dart,
      app_empty_state.dart, app_error_state.dart,
      app_loading_state.dart, app_stat_card.dart,
      app_list_item.dart, app_chip.dart
  shared/
    models/  (34 typed models, fromJson/toJson)
      user.dart, unit.dart, role.dart, student.dart,
      teacher.dart, employee.dart, schedule.dart, journal.dart,
      student_attendance.dart, bill.dart, transaction.dart,
      finance_dashboard.dart, violation.dart, counseling_session.dart,
      achievement.dart, inventory_item.dart, room.dart,
      maintenance.dart, loan.dart, classroom.dart, material.dart,
      assignment.dart, submission.dart, grade.dart, announcement.dart,
      news.dart, event.dart, destination.dart, pickup_request.dart,
      notification.dart, attendance_log.dart, academic_year.dart,
      dashboard_stats.dart, task.dart
  features/
    auth/        (splash, login, providers, repository)
    home/        (dashboard screen + provider)
    attendance/  (check-in/out, history, provider)
    academic/    (schedule, students, journal, student_attendance, recap)
    finance/     (dashboard, bills, transactions)
    counseling/  (tabs, session detail)
    sarpar/      (dashboard, inventory, rooms, maintenance, loans)
    lms/         (classrooms, material, assignment, submission, gradebook)
    pr/          (news, events, destinations)
    student_portal/ (dashboard, tasks, pickup)
    employee/    (staff, attendance)
    notifications/ (list)
    profile/     (profile, settings, unit switcher)

Each feature folder:
  screens/  widgets/  providers/  repository.dart

============================================================
SECTION 8. IMPLEMENTATION PHASES
============================================================

Phase 0 - Backend: build 37 new API endpoints (Laravel controllers,
         routes in lms.php / public-relations.php / student-portal.php /
         employee.php / api.php, permission seeds).
Phase 1 - Foundation: models (34), api_client, design system (theme),
         15 atom widgets, routes, auth (splash+login), storage service.
Phase 2 - Core shell: Home dashboard, Profile, Attendance, BottomNav+Drawer.
Phase 3 - Academic module.
Phase 4 - Finance module.
Phase 5 - Counseling module.
Phase 6 - Sarpar module.
Phase 7 - Advanced: LMS, PR, Student Portal, Employee, Notifications.

============================================================
SECTION 9. KEY USER FLOWS
============================================================

Flow A - Login -> Dashboard
  Splash cek token -> (ada) /dashboard redirect by role
  -> (kosong) /login -> POST /auth/login -> simpan token+profile+unit
  -> /home

Flow B - Employee Presensi
  Tap center tab -> /attendance -> GPS + kamera ->
  POST /attendance/check-in (lat,lng,photo) -> success -> history update

Flow C - Teacher Input Jurnal
  Akademik -> Jurnal -> form (mapel, materi, hadir) ->
  POST /academic/journal -> list refresh

Flow D - Student Cek Tagihan
  Keuangan -> Bills -> filter belum lunas -> Bill Detail (VA)

Flow E - Unit Switch (Super Admin)
  Settings -> pilih unit -> POST /admin/units/:id/switch ->
  set session active_unit_id -> semua query filter unit baru

============================================================
SECTION 10. GOTCHAS / CONSTRAINTS
============================================================

- Unit scope wajib: setiap query filter unit_id = active_unit_id.
- Super admin bypass unit scope & feature flags.
- Cache: system_settings pakai Cache::rememberForever -> cache:clear
  setelah ubah setting.
- Storage: php artisan storage:link untuk upload foto.
- Android device: adb reverse tcp:8000 tcp:8000 (artisan serve --host=0.0.0.0).
- No emoji in code or UI. Heroicons only.
- Spatie teams: team_id = unit_id. Global role team_id = NULL.
- Font Plus Jakarta Sans via Google Fonts.

END OF PRD v1.0
