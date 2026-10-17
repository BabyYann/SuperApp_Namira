# Evidence Register (Register Bukti Faktual Kode Sumber)

Register ini memetakan setiap klaim arsitektural dan fungsional ke bukti kode sumber nyata (*source-code evidence*) di dalam repositori SuperApp Namira.

---

## 1. Indeks Bukti Arsitektur Inti & Multi-Tenancy (EV-CORE)

| Evidence ID | Komponen / Berkas Terverifikasi | Baris / Simbol Kode | Fakta Teknis yang Terbukti | Status Verifikasi |
|---|---|---|---|---|
| **EV-CORE-001** | `composer.json` | L8-L21 | PHP 8.2+, Laravel 12.0, Inertia 3.0, Sanctum 4.0, Spatie Permission 6.24, Spatie Activitylog, Laravel Reverb, Laravel Pulse. | VERIFIED |
| **EV-CORE-002** | `package.json` | L10-L48 | Vue 3.5.40, Tailwind CSS 4.3.3, Vite 8.2.0, Vite PWA 1.3.0, Leaflet 1.9.4, Chart.js 4.5.1, SweetAlert2 11.26. | VERIFIED |
| **EV-CORE-003** | `bootstrap/app.php` | L17-L32 | Global Web Middleware: `CheckUnitScope`, `HandleInertiaRequests`, `CheckMaintenanceMode`. Middleware Alias: `role`, `permission`, `feature`. | VERIFIED |
| **EV-CORE-004** | `app/Http/Middleware/CheckUnitScope.php` | L15-L55 | Multi-Tenancy Unit Scoping: Menyaring data per `unit_id` atau Spatie team scope berdasarkan unit aktif user di session/cookie. | VERIFIED |
| **EV-CORE-005** | `app/Modules/Yayasan/Models/Unit.php` | L1-L45 | Model Unit Sekolah: Membedakan kategori (formal/non-formal), level (TK, SD, SMP, DAYCARE, YAYASAN), koordinat lokasi, dan jam kerja. | VERIFIED |
| **EV-CORE-006** | `app/Modules/Yayasan/Models/AcademicYear.php` | L1-L40 | Model Tahun Akademik: Menandai status aktif (`is_active`), semester ganjil/genap, dan tanggal mulai/selesai. | VERIFIED |
| **EV-CORE-007** | `database/seeders/RolesAndPermissionsSeeder.php` | L16-L51 | Definisi 6 Role Global Yayasan & 14 Role Unit Scoped. Super Admin Yayasan memegang seluruh hak akses (`Permission::all()`). | VERIFIED |

---

## 2. Indeks Bukti Modul Akademik & Siswa (EV-ACAD)

| Evidence ID | Komponen / Berkas Terverifikasi | Baris / Simbol Kode | Fakta Teknis yang Terbukti | Status Verifikasi |
|---|---|---|---|---|
| **EV-ACAD-001** | `app/Modules/Academic/Models/Student.php` | L1-L85 | Relasi siswa dengan `Classroom`, `Unit`, `AcademicYear`, riwayat kehadiran, tagihan, dan catatan BK. Mendukung NISN & NIK. | VERIFIED |
| **EV-ACAD-002** | `app/Modules/Academic/Models/ClassSchedule.php` | L1-L60 | Relasi jadwal pelajaran per hari, jam mulai-selesai, mata pelajaran, ruang kelas, dan multi-classroom (sesi kelas gabungan). | VERIFIED |
| **EV-ACAD-003** | `app/Modules/Academic/Models/TeachingJournal.php` | L1-L65 | Jurnal KBM guru memuat materi bahasan, capaian TP/bab, catatan kelas, dan absensi terikat (`JournalAttendance`). | VERIFIED |
| **EV-ACAD-004** | `app/Modules/Academic/Services/Promotion/PromotionExecutionService.php` | L1-L120 | Eksekusi kenaikan kelas massal dengan riwayat `class_promotions`, validasi prasyarat, dan kemampuan rollback aman. | VERIFIED |
| **EV-ACAD-005** | `app/Modules/Academic/Controllers/StudentCheckinController.php` | L1-L95 | Pemindaian barcode/QR gerbang sekolah (`StudentScannedAtGate`), pencatatan waktu tiba, dan status keterlambatan otomatis. | VERIFIED |

---

## 3. Indeks Bukti Kepegawaian & Absensi GPS (EV-EMP)

| Evidence ID | Komponen / Berkas Terverifikasi | Baris / Simbol Kode | Fakta Teknis yang Terbukti | Status Verifikasi |
|---|---|---|---|---|
| **EV-EMP-001** | `app/Models/EmployeeAttendance.php` | L1-L75 | Pencatatan absensi pegawai: jam masuk/pulang, foto selfie, koordinat GPS (lat, lng), status (hadir/terlambat/izin/sakit), radius meter. | VERIFIED |
| **EV-EMP-002** | `app/Modules/Employee/Controllers/AttendanceController.php` | L40-L130 | Validasi absensi radius geofencing haversine terhadap master `AttendanceLocation` dengan penolakan jika berada di luar radius izin. | VERIFIED |
| **EV-EMP-003** | `app/Modules/Yayasan/Controllers/AttendanceApprovalController.php` | L1-L85 | Workflow persetujuan pengajuan absensi, dinas luar, lembur, dan sakit oleh atasan unit atau HRD yayasan. | VERIFIED |
| **EV-EMP-004** | `app/Modules/Employee/Models/EmployeeActivityLog.php` | L1-L50 | Log giat harian karyawan: judul kegiatan, uraian kerja, foto bukti, waktu pelaksanaan, dan verifikasi pimpinan. | VERIFIED |

---

## 4. Indeks Bukti Keuangan & Pembayaran (EV-FIN)

| Evidence ID | Komponen / Berkas Terverifikasi | Baris / Simbol Kode | Fakta Teknis yang Terbukti | Status Verifikasi |
|---|---|---|---|---|
| **EV-FIN-001** | `app/Modules/Finance/Models/StudentBill.php` | L1-L75 | Tagihan SPP/biaya pendidikan: nominal, tanggal jatuh tempo, sisa tagihan, status (`unpaid`, `partial`, `paid`), dan bukti transfer. | VERIFIED |
| **EV-FIN-002** | `app/Modules/Finance/Models/BillPayment.php` | L1-L60 | Riwayat transaksi pembayaran tagihan: metode pembayaran (tunai, transfer, VA), tanggal bayar, referensi kuitansi, dan user pencatat. | VERIFIED |
| **EV-FIN-003** | `app/Modules/Finance/Controllers/TransactionController.php` | L1-L140 | Mutasi kas masuk & kas keluar yayasan/unit dengan kategorisasi pos akun anggaran (`FinanceAccount`) dan export PDF/Excel. | VERIFIED |
| **EV-FIN-004** | `app/Events/PaymentTransactionRecorded.php` | L1-L35 | Broadcast event real-time saat transaksi pembayaran terekam via kanal WebSocket Reverb `payment-transactions`. | VERIFIED |

---

## 5. Indeks Bukti Konseling & Kesiswaan (EV-COUN)

| Evidence ID | Komponen / Berkas Terverifikasi | Baris / Simbol Kode | Fakta Teknis yang Terbukti | Status Verifikasi |
|---|---|---|---|---|
| **EV-COUN-001** | `app/Modules/Counseling/Models/Violation.php` | L1-L55 | Rekam pelanggaran tata tertib: kategori pelanggaran, bobot poin hukuman, guru pelapor, tindak lanjut, dan notifikasi orang tua. | VERIFIED |
| **EV-COUN-002** | `app/Modules/Counseling/Models/CounselingSession.php` | L1-L65 | Sesi konseling BK: tanggal, topik masalah, pendekatan konseling, kesepakatan komitmen siswa, dan status kerahasiaan. | VERIFIED |
| **EV-COUN-003** | `app/Modules/Counseling/Models/Achievement.php` | L1-L50 | Prestasi siswa: tingkat (sekolah, kecamatan, kota, provinsi, nasional, internasional), juara, piagam penghargaan, dan poin apresiasi. | VERIFIED |

---

## 6. Indeks Bukti Sarana & Prasarana (EV-SARP)

| Evidence ID | Komponen / Berkas Terverifikasi | Baris / Simbol Kode | Fakta Teknis yang Terbukti | Status Verifikasi |
|---|---|---|---|---|
| **EV-SARP-001** | `app/Modules/Sarpar/Models/Inventory.php` | L1-L80 | Aset barang inventaris: kode unik, nomor inventaris, kondisi (baik/rusak ringan/rusak berat), lokasi ruang, dan sumber perolehan. | VERIFIED |
| **EV-SARP-002** | `app/Modules/Sarpar/Controllers/StickerController.php` | L1-L70 | Generator label stiker QR Code inventaris untuk pemindaian cepat menggunakan kamera perangkat HP. | VERIFIED |
| **EV-SARP-003** | `app/Modules/Sarpar/Models/MaintenanceLog.php` | L1-L55 | Pencatatan perbaikan barang rusak: deskripsi kerusakan, biaya reparasi, vendor pelaksana, dan verifikasi selesai. | VERIFIED |
| **EV-SARP-004** | `app/Modules/Sarpar/Models/Loan.php` | L1-L65 | Peminjaman barang aset: nama peminjam, tanggal pinjam, batas kembali, kondisi saat kembali, dan status approval. | VERIFIED |

---

## 7. Indeks Bukti LMS & Pembelajaran Digital (EV-LMS)

| Evidence ID | Komponen / Berkas Terverifikasi | Baris / Simbol Kode | Fakta Teknis yang Terbukti | Status Verifikasi |
|---|---|---|---|---|
| **EV-LMS-001** | `app/Modules/LMS/Models/LmsClassroom.php` | L1-L60 | Ruang kelas LMS digital terikat dengan rombel kurikulum atau kelas khusus, guru pembimbing, dan siswa terdaftar. | VERIFIED |
| **EV-LMS-002** | `app/Modules/LMS/Models/LmsMaterial.php` | L1-L55 | Modul materi ajar digital dengan dukungan lampiran berkas PDF/dokumen (`LmsMaterialFile`) dan tautan video. | VERIFIED |
| **EV-LMS-003** | `app/Modules/LMS/Models/LmsAssignment.php` | L1-L70 | Tugas digital: batas tenggat waktu (*deadline*), instruksi, kriteria nilai, dan lampiran soal. | VERIFIED |
| **EV-LMS-004** | `app/Modules/LMS/Models/LmsSubmission.php` | L1-L65 | Pengumpulan tugas siswa: berkas unggahan, waktu kirim, status keterlambatan (*late submission*), skor nilai guru, dan feedback. | VERIFIED |

---

## 8. Indeks Bukti Modul Khusus: Daycare, SPMB, & Ekskul (EV-SPEC)

| Evidence ID | Komponen / Berkas Terverifikasi | Baris / Simbol Kode | Fakta Teknis yang Terbukti | Status Verifikasi |
|---|---|---|---|---|
| **EV-SPEC-001** | `app/Modules/Daycare/Models/DaycareDailyLog.php` | L1-L70 | Pencatatan detail harian balita: menu makan pagi/siang, durasi tidur siang, frekuensi BAB/BAK, dan mood anak. | VERIFIED |
| **EV-SPEC-002** | `app/Modules/Daycare/Models/DaycareAuthorizedPickup.php` | L1-L50 | Daftar wali penjemput terverifikasi balita lengkap dengan foto identitas dan verifikasi nomor telepon. | VERIFIED |
| **EV-SPEC-003** | `app/Modules/SPMB/Models/SpmbApplicant.php` | L1-L85 | Calon siswa baru: nomor pendaftaran unik, jalur seleksi, unit tujuan, kelengkapan berkas, nilai tes, dan status penerimaan. | VERIFIED |
| **EV-SPEC-004** | `app/Modules/Extracurricular/Models/Extracurricular.php` | L1-L60 | Master kegiatan ekskul (Pramuka, Futsal, Robotik, Tahfidz), pelatih, kuota anggota, dan lokasi sesi latihan. | VERIFIED |
| **EV-SPEC-005** | `app/Modules/Extracurricular/Models/ExtracurricularAttendance.php` | L1-L50 | Presensi kehadiran santri/siswa pada sesi latihan mingguan ekstrakurikuler. | VERIFIED |

---

## 9. Indeks Bukti Notifikasi & Real-time (EV-NOTIF)

| Evidence ID | Komponen / Berkas Terverifikasi | Baris / Simbol Kode | Fakta Teknis yang Terbukti | Status Verifikasi |
|---|---|---|---|---|
| **EV-NOTIF-001** | `app/Services/FcmService.php` | L1-L85 | Integrasi Google Firebase Cloud Messaging HTTP v1 API untuk web push notification PWA ke perangkat guru & staf. | VERIFIED |
| **EV-NOTIF-002** | `app/Services/NotificationDispatcher.php` | L1-L110 | Dispatcher notifikasi terpadu: menyimpan ke database in-app, mengirim push notification FCM, dan antrian WhatsApp. | VERIFIED |
| **EV-NOTIF-003** | `app/Helpers/WhatsAppHelper.php` | L1-L75 | Helper pengiriman pesan WhatsApp melalui REST API pihak ketiga untuk tagihan SPP dan pelanggaran siswa. | VERIFIED |
| **EV-NOTIF-004** | `resources/js/Layouts/AuthenticatedLayout.vue` | L190-L270 | Real-time listeners via Laravel Echo: approval absensi, gate scanner kedatangan, submit jurnal guru, dan maintenance sarpras. | VERIFIED |
