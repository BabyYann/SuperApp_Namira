# 06_BUSINESS_FLOW

## 1. Flow: Student Registration & Rombel Synchronization
**Confidence Level: High**
(Diverifikasi dari route pendaftaran siswa dan query virtual class LMS/Portal)

### 1.1 Business Process
Pendaftaran Profil Siswa Baru → Integrasi VA Number → Alokasi Kelas Fisik → Sinkronisasi Otomatis LMS Daring → Akses Dashboard Portal Siswa → Audit Trail Terbentuk.

### 1.2 Actors
*   Admin Unit / Staf Kurikulum (Pendaftar)
*   Siswa (Pengguna Akhir)

### 1.3 Trigger
*   Input formulir manual oleh admin.
*   Unggah dokumen data siswa via Excel (*Import Excel*).

### 1.4 Preconditions
*   Tahun Ajaran Aktif (`AcademicYear`) telah diatur di tingkat yayasan.
*   Unit Sekolah (`Unit`) aktif pada sesi admin.
*   Akun pengguna global (`User`) dengan peran `siswa` telah diterbitkan.

### 1.5 Step by Step Execution
1.  Admin mengunggah file data siswa atau mengisi form pendaftaran di menu siswa.
2.  Sistem melakukan validasi kolom wajib (Nama Lengkap, NIS, NIK, Jenis Kelamin, dan Kelas).
3.  Sistem membuat entitas baru di tabel `students` dengan status *active* dan mengaitkannya ke `user_id` yang sesuai.
4.  Admin mengalokasikan siswa ke kelas fisik (`Classroom`) melalui menu `classrooms.add-student`.
5.  Saat siswa berhasil login, *Inertia* memproses pengalihan peran (`siswa`) ke Portal Siswa.
6.  Portal Siswa/LMS mendeteksi `student->classroom_id` yang aktif dan menampilkan data jadwal pelajaran, tugas, serta feed forum LMS secara *real-time*.

### 1.6 Database Impact
*   **Reads:** `users`, `units`, `academic_years`, `classrooms`
*   **Inserts:** `students`
*   **Updates:** `students` (alokasi `classroom_id`), `users` (jika foto diperbarui)

### 1.7 Cross Module Communication
*   **Academic → LMS:** Relasi `classroom_id` langsung memicu keikutsertaan siswa di ruang kelas LMS virtual yang dibuat guru mata pelajaran untuk kelas fisik tersebut.
*   **Academic → Student Portal:** Menu jadwal dan absensi siswa di portal membaca data langsung dari modul akademik.

### 1.8 Notification
*   **Log:** Tercatat histori aktivitas di audit trail (`activity_logs`).

### 1.9 Failure Scenario
*   *Validation Fail:* Admin gagal submit data jika format kolom tidak lengkap (NIS ganda atau kelas tidak valid).
*   *Database Fail:* Terjadi rollback data siswa jika pembuatan user pengait gagal di tingkat basis data.

### 1.10 Transaction Boundary
*   Pendaftaran massal via Excel diproses dalam `DB::transaction()` untuk menjamin seluruh baris data siswa sukses diimpor atau tidak sama sekali.

### 1.11 Business Rules
*   Siswa hanya boleh dialokasikan ke satu kelas aktif dalam satu periode tahun ajaran.
*   NIS siswa tidak boleh duplikat dalam satu unit sekolah.

### 1.12 Sequence Diagram (Text)
```
Admin Unit ──(Form/Excel)──> StudentController ──(Create)──> Student Model ──> MySQL
                                                                                │
Siswa ───────(Login)───────> StudentPortalController <──(Read Students)─────────┘
                                    │
                                    └──(Render Vue Pages)──> Browser Siswa
```

### 1.13 Improvement Opportunity
*   **Peluang Event-Driven:** Membuat pendaftaran akun user siswa otomatis melalui pemicuan Domain Event `StudentRegistered` yang didengarkan oleh modul LMS untuk setup registrasi kelas virtual di awal.

---

## 2. Flow: Bank Mutation CSV Reconciliation (Waterfall Payment)
**Confidence Level: High**
(Diverifikasi dari kode program di `TransactionController.php` baris 65–283)

### 2.1 Business Process
Unggah Mutasi Bank CSV → Deteksi Nomor VA Siswa → Pencarian Tagihan Tertua (Unpaid/Partial) → Alokasi Pembayaran Berjenjang (Waterfall) → Pembuatan Tiket Transaksi Pembayaran → Update Status Tagihan (Paid/Partial) → Log Saldo Lebih (Deposit).

### 2.2 Actors
*   Staf Administrasi Keuangan / Kasir Unit
*   Bendahara Yayasan

### 2.3 Trigger
*   Tombol impor mutasi bank (`Import.vue`) dengan unggahan file CSV.

### 2.4 Preconditions
*   Format file CSV mutasi bank berisi nomor Virtual Account (VA) di kolom deskripsi transaksi.
*   Siswa target memiliki nomor VA terdaftar (`va_number`).
*   Akun perkiraan keuangan penerima kas (`FinanceAccount`) aktif.

### 2.5 Step by Step Execution
1.  Kasir mengunggah file CSV mutasi rekening bank dan memilih akun perkiraan keuangan penerima.
2.  Sistem membersihkan file CSV dari format Byte Order Mark (BOM) jika ada.
3.  Sistem melakukan *delimiter probing* secara otomatis (mendeteksi koma, titik koma, tab, atau pipa).
4.  Sistem membaca baris per baris transaksi, mengekstrak nomor VA siswa (berukuran 8–20 digit) via Regex.
5.  Sistem mencocokkan data nomor VA ke database `students` milik unit aktif kasir.
6.  Sistem mengekstrak nominal uang dari deskripsi baris mutasi bank.
7.  Sistem memverifikasi duplikasi transaksi hari ini (nominal, VA, dan deskripsi sama).
8.  Sistem memanggil algoritma **Waterfall Payment**:
    *   Mengambil daftar tagihan siswa (`StudentBill`) berstatus *unpaid/partial* diurutkan dari jatuh tempo terlama (`due_date` ASC).
    *   Membuat satu header transaksi (`Transaction`).
    *   Mengalokasikan pembayaran ke tagihan terlama. Jika lunas, sisa dana dialokasikan ke tagihan berikutnya.
    *   Membuat detail transaksi pembayaran (`BillPayment`) untuk mencatat alokasi per tagihan.
    *   Memperbarui sisa nominal tagihan (`paid_amount`) dan status tagihan (`paid` atau `partial`).
9.  Sisa dana pembayaran yang berlebih dimasukkan ke dalam kolom `excess_amount` pada header transaksi.

### 2.6 Database Impact
*   **Reads:** `students`, `student_bills`, `transactions`, `finance_accounts`
*   **Inserts:** `transactions`, `bill_payments`
*   **Updates:** `student_bills` (status & paid_amount)

### 2.7 Cross Module Communication
*   **Finance → Academic:** Pencocokan transaksi finansial bank langsung membaca data profile siswa di modul akademis melalui relasi `student_id`.
*   **Finance → Student Portal:** Status tagihan SPP lunas langsung mengubah tampilan data tunggakan pada portal siswa secara otomatis.

### 2.8 Notification
*   **Log:** Sistem mencatat log impor sukses, duplikat dilewati, atau format salah ke log internal Laravel.

### 2.9 Failure Scenario
*   *Validation Fail:* File bukan CSV atau ukuran melebih 2MB akan memicu pesan error balik.
*   *Delimiter Error:* Delimiter CSV tidak terstandar akan memicu error "Gagal membaca file".
*   *Database Fail:* Jika satu baris data transaksi gagal diproses (misal error integritas relasi), sistem memicu rollback penuh database sehingga tidak ada mutasi yang masuk setengah-setengah.

### 2.10 Transaction Boundary
*   Proses *reconciliation* satu file utuh dilindungi di dalam `DB::beginTransaction()` dan dideklarasikan `DB::commit()` hanya setelah loop baris CSV selesai dibaca.

### 2.11 Business Rules
*   Algoritma alokasi pembayaran wajib melunasi tunggakan terlama terlebih dahulu (FIFO berdasarkan tanggal jatuh tempo tagihan).
*   Sisa kelebihan dana tidak boleh hangus, melainkan disimpan sebagai deposit historis transaksi (`excess_amount`).
*   Baris mutasi bank yang sudah pernah diimpor (duplikat transaksi berdasarkan nominal, deskripsi, dan hari yang sama) wajib diabaikan secara ketat.

### 2.12 Sequence Diagram (Text)
```
Kasir ──(Upload CSV)──> TransactionController ──(Regex VA)──> Student Model 
                                                                    │
                                                              (Get Student)
                                                                    ↓
Update MySQL <──(Save Status)── StudentBill Model <──(Waterfall)── Query Bills
```

### 2.13 Improvement Opportunity
*   **Bottleneck:** Penggunaan `set_time_limit(300)` menandakan proses pembacaan CSV dilakukan secara sinkronus (*synchronous processing*). Jika file CSV berisi ribuan baris transaksi, web browser akan mengalami *freeze/timeout*.
*   **Peluang Queue:** Proses parsing CSV dan alokasi pembayaran waterfall wajib dipindahkan ke antrean background job (`Queue`) dengan memberikan notifikasi email/web notification setelah selesai.
*   **Peluang Wallet:** Kelebihan uang pembayaran (`excess_amount`) saat ini baru dicatat secara statis. Perlu dibuat tabel saldo deposit siswa (`wallets`) untuk penggunaan otomatis sisa uang di tagihan bulan depan.

---

## 3. Flow: LMS Assignment Submission
**Confidence Level: High**
(Diverifikasi dari kode program di `LmsStudentController.php` baris 133–181)

### 3.1 Business Process
Siswa Buka Tugas Active → Unggah File Tugas Mentah → Validasi Batas Waktu (Late check) → Pembuatan Log Submission → Penghapusan File Tugas Lama (jika update tugas) → Simpan File ke Storage → Tugas Terkumpul.

### 3.2 Actors
*   Siswa (Pengirim Tugas)
*   Guru (Pemeriksa & Penilai Tugas)

### 3.3 Trigger
*   Klik tombol "Kumpulkan Tugas" (`Assignment/Show.vue`).

### 3.4 Preconditions
*   Siswa terdaftar aktif di rombel kelas virtual tersebut.
*   Tugas (`LmsAssignment`) berstatus *published*.
*   Ukuran file dokumen jawaban di bawah 10MB.

### 3.5 Step by Step Execution
1.  Siswa membuka halaman detail tugas di portal LMS siswa.
2.  Siswa mengunggah file (PDF/Word/ZIP) atau menuliskan teks tanggapan jawaban.
3.  Sistem melakukan cek waktu pengumpulan server saat ini dibandingkan dengan kolom `due_date` tugas:
    *   Jika `waktu_sekarang > due_date`, maka status diatur menjadi `late` (terlambat).
    *   Jika `waktu_sekarang <= due_date`, maka status diatur menjadi `submitted`.
4.  Sistem membuka transaksi database.
5.  Sistem membuat atau memperbarui record pengumpulan di tabel `lms_submissions` (menggunakan `updateOrCreate` untuk memfasilitasi pengumpulan ulang tugas).
6.  Jika siswa mengunggah file baru untuk tugas yang sudah pernah dikumpulkan sebelumnya, sistem secara otomatis mencari file lama di penyimpanan disk lokal (`public/lms/submissions`), menghapusnya dari server, dan menghapus pivot record lamanya di tabel database.
7.  Sistem menyimpan file jawaban baru ke folder `lms/submissions` dan membuat entri pivot di `lms_submission_files`.
8.  Siswa dialihkan kembali ke linimasa kelas virtual dengan pesan sukses.

### 3.6 Database Impact
*   **Reads:** `lms_assignments`, `lms_submissions`, `lms_submission_files`
*   **Inserts/Updates:** `lms_submissions`
*   **Deletes:** `lms_submission_files` (jika mengunggah ulang tugas lama)

### 3.7 Cross Module Communication
*   **LMS → Academic:** Sistem memverifikasi validitas keikutsertaan siswa di kelas melalui tabel profil akademis siswa (`students`).

### 3.8 Notification
*   **Log:** Tercatat histori pengumpulan tugas di audit trail.

### 3.9 Failure Scenario
*   *Validation Fail:* File di luar ekstensi yang dizinkan atau melebihi batas 10MB memicu pesan error validasi input browser.
*   *File Upload Fail:* Jika gagal menulis ke disk server, database di-rollback sehingga status tugas tetap belum dikumpulkan.

### 3.10 Transaction Boundary
*   Seluruh alur proses (pengecekan tenggat, update status pengumpulan, hapus file lama di DB, dan insert file baru) dibungkus menggunakan closure `DB::transaction()`.

### 3.11 Business Rules
*   Siswa diperbolehkan mengumpulkan ulang tugas, namun file jawaban lama wajib dihapus dari server guna mencegah penumpukan sampah data.
*   Tugas yang dikumpulkan melewati batas waktu (`due_date`) wajib ditandai statusnya secara otomatis sebagai `late`.

### 3.12 Sequence Diagram (Text)
```
Siswa ──(Submit Tugas)──> LmsStudentController ──(Check Due Date)──> Assignment Model
                                   │
                           (Start Transaction)
                                   ↓
Delete Old File <──(If Re-upload)─ Storage <──(Write File)── LmsSubmission Model
                                                                    │
                                                              (Commit Transaction)
                                                                    ↓
                                                                  MySQL
```

### 3.13 Improvement Opportunity
*   **Peluang Push Notification:** Perlu dibuat integrasi notifikasi real-time ke aplikasi browser Guru BK atau Guru Mapel saat siswa menyerahkan tugas yang terlambat (`late` status).

---

## 4. Flow: Staff GPS Check-In/Check-Out Attendance
**Confidence Level: High**
(Diverifikasi dari kode program di `AttendanceController.php` baris 58–236)

### 4.1 Business Process
Permintaan Lokasi GPS Handphone → Bandingkan Jarak via Haversine Formula → Validasi Jam Masuk Unit → Cek Toleransi Terlambat → Input Foto Selfie Base64 → Simpan Log Absensi (Hadir/Terlambat).

### 4.2 Actors
*   Staf Karyawan Non-Guru
*   Guru Pendidik

### 4.3 Trigger
*   Klik tombol "Check In" / "Check Out" di halaman Absensi Karyawan.

### 4.4 Preconditions
*   Pengguna mengaktifkan izin GPS browser (Geolocation API).
*   Radius toleransi (meter) dan titik koordinat unit sekolah (`AttendanceLocation`) sudah dikonfigurasi admin yayasan.
*   Jadwal toleransi keterlambatan (`late_tolerance_minutes`) dan jam masuk (`work_start_time`) telah terdaftar di unit aktif user.

### 4.5 Step by Step Execution
1.  Browser mendeteksi koordinat lintang (*latitude*) dan bujur (*longitude*) pengguna.
2.  Pengguna memilih jenis kehadiran (*present* / *business_trip* / *sick* / *permit*).
3.  Jika memilih *present* (Hadir), sistem mengevaluasi lokasi:
    *   Sistem mencari seluruh lokasi absensi aktif (`AttendanceLocation`) yang sesuai dengan unit tugas karyawan.
    *   Sistem menghitung jarak (meter) antara GPS user dan koordinat lokasi absensi resmi menggunakan **Rumus Haversine**.
    *   Jika jarak melebihi batas radius toleransi lokasi mana pun, proses dihentikan dan sistem mengembalikan error "Di Luar Radius".
4.  Sistem melakukan cek waktu masuk kerja:
    *   Mengambil jam masuk kerja unit (`work_start_time`) dan durasi toleransi terlambat (`late_tolerance_minutes`).
    *   Jika `waktu_sekarang > jam_masuk + toleransi`, sistem menghitung selisih keterlambatan dari jam masuk mula-mula (`work_start_time`) dalam hitungan menit (`late_minutes`) dan mengubah status menjadi `late`.
5.  Sistem mengonversi foto selfie bertipe Base64 yang diunggah dari kamera handphone, menyimpannya ke disk server (`attendance_photos/`), dan merujuk lokasinya di database.
6.  Sistem menyimpan data absensi masuk harian di tabel `employee_attendances` dengan `approval_status = not_required` (otomatis disetujui).
7.  Untuk absensi pulang, pengguna melakukan Check Out. Sistem melakukan validasi koordinat GPS ulang dan memperbarui kolom `check_out_time` di database.

### 4.6 Database Impact
*   **Reads:** `attendance_locations`, `employee_attendances`, `units`
*   **Inserts:** `employee_attendances` (saat Check In)
*   **Updates:** `employee_attendances` (saat Check Out)

### 4.7 Cross Module Communication
*   **Employee → Yayasan:** Rekapitulasi absensi harian staf langsung dikonsumsi menu pemantauan (*monitoring*) admin yayasan secara real-time.

### 4.8 Notification
*   **Log:** Log absensi masuk dan pulang dicatat ke database.

### 4.9 Failure Scenario
*   *GPS Spoofing / Radius Error:* Jika koordinat pengguna berada di luar radius lokasi resmi, absensi dibatalkan dan sistem memunculkan pop-up peringatan keras.
*   *Double Check-in:* Jika terdeteksi record absensi pada hari yang sama, Check In ditolak.

### 4.10 Transaction Boundary
*   Pencatatan absensi berupa operasi insert/update tunggal yang berjalan secara atomik di basis data.

### 4.11 Business Rules
*   Karyawan wajib berada di dalam radius toleransi GPS unit kerja yang terdaftar saat melakukan absen masuk maupun pulang.
*   Jika absen masuk dilakukan melebihi ambang batas toleransi, penghitungan keterlambatan dihitung mundur langsung ke jam masuk mula-mula (bukan dari jam toleransi berakhir).

### 4.12 Sequence Diagram (Text)
```
Browser ──(Coordinate GPS)──> AttendanceController ──(Haversine Distance)──> AttendanceLocation Model
   │                                   │
(Selfie Cam)                    (Verify Radius)
   ↓                                   ↓
Save to Disk <──(Base64 Dec)─── (Write Record) ──> EmployeeAttendance Model ──> MySQL
```

### 4.13 Improvement Opportunity
*   **Optimization:** Haversine formula dihitung di memori server setelah query seluruh lokasi unit aktif. Jika lokasi unit absensi bertambah sangat banyak, komputasi di memori PHP akan memicu beban CPU. Rumus Haversine sebaiknya dihitung langsung di tingkat query MySQL (spatially indexed query) jika performa melambat.
*   **Peluang Queue:** Pengolahan konversi Base64 gambar selfie dapat diproses di latar belakang untuk mempercepat respons pop-up absensi browser.
