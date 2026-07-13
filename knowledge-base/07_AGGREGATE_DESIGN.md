# 07_AGGREGATE_DESIGN

## 1. Aggregate Identification

Berdasarkan analisis struktur source code, entitas utama dikelompokkan ke dalam batas Aggregate Root berikut untuk menegakkan integritas transaksi dan konsistensi data:

### Domain: Yayasan (Core Admin)
*   **Unit (Aggregate Root):**
    *   *Alasan:* Menjadi penentu batasan multi-tenancy di tingkat sekolah (SD, SMP, SMA). Seluruh data transaksi di bawahnya diisolasi berdasarkan identitas Unit.
    *   *Lifecycle:* Dibuat saat pendaftaran sekolah baru, berubah saat pembaruan profil unit, tidak pernah dihapus karena dependensi data historis yang sangat luas.
    *   *Confidence:* High.
*   **AcademicYear (Aggregate Root):**
    *   *Alasan:* Mengontrol masa aktif operasional sekolah. Menentukan keabsahan transaksi kelas, jadwal, dan tagihan keuangan.
    *   *Lifecycle:* Dibuat saat pembukaan tahun ajaran baru, diubah saat status di-set aktif/nonaktif, tidak dihapus karena merekam riwayat historis.
    *   *Confidence:* High.
*   **User (Aggregate Root):**
    *   *Alasan:* Mengelola identitas pengguna global, kredensial login, dan otorisasi peran (Spatie RBAC).
    *   *Lifecycle:* Dibuat saat pendaftaran staf/guru/siswa, berubah saat edit data/reset sandi, dinonaktifkan (soft delete) bila keluar.
    *   *Confidence:* High.

### Domain: Academic
*   **Student (Aggregate Root):**
    *   *Alasan:* Mengoordinasikan seluruh informasi diri siswa, histori kenaikan kelas (`ClassPromotion`), absensi kelas, dan rekam BK.
    *   *Lifecycle:* Dibuat oleh admin, diperbarui saat mutasi/kenaikan kelas, dinonaktifkan jika keluar/lulus.
    *   *Confidence:* High.
*   **Classroom (Aggregate Root):**
    *   *Alasan:* Mengelola relasi rombel fisik, alokasi siswa, mata pelajaran, dan jadwal pelajaran (`ClassSchedule`).
    *   *Lifecycle:* Dibuat permanen per tingkat, diperbarui saat guru wali kelas berganti, tidak pernah dihapus.
    *   *Confidence:* High.
*   **Teacher (Aggregate Root):**
    *   *Alasan:* Mengelola profil guru pendidik dan kepemilikan jurnal mengajar (`TeachingJournal`).
    *   *Confidence:* High.

### Domain: Finance
*   **StudentBill (Aggregate Root):**
    *   *Alasan:* Mengontrol siklus keuangan siswa: tagihan terbit, pemotongan diskon (`StudentDiscount`), dan alokasi pembayaran (`BillPayment`).
    *   *Lifecycle:* Dibuat otomatis per bulan/tahun, berubah status saat kasir memproses pembayaran waterfall, dihapus jika terjadi kesalahan input kasir sebelum dibayar.
    *   *Confidence:* High.

### Domain: LMS
*   **LmsClassroom (Aggregate Root):**
    *   *Alasan:* Mengelola forum kelas digital, pengunggahan materi pelajaran (`LmsMaterial`), pembuatan tugas (`LmsAssignment`), dan pengumpulan jawaban siswa BK (`LmsSubmission`).
    *   *Lifecycle:* Dibuat otomatis saat guru mengaktifkan sinkronisasi kelas fisik, berubah saat ada materi/tugas baru, diarsipkan di akhir tahun ajaran.
    *   *Confidence:* High.

### Domain: Counseling (BK)
*   **Violation (Aggregate Root):**
    *   *Alasan:* Mengontrol log sanksi perilaku siswa beserta perhitungan skor poin BK.
    *   *Confidence:* High.

### Domain: Sarpar
*   **Inventory (Aggregate Root):**
    *   *Alasan:* Mengoordinasikan status aset sekolah, mutasi log peminjaman (`Loan`), dan riwayat perbaikan kerusakan barang (`MaintenanceLog`).
    *   *Lifecycle:* Dibuat saat barang baru datang, berubah status (tersedia, dipinjam, rusak), dihapus jika diputihkan/dijual.
    *   *Confidence:* High.
*   **Room (Aggregate Root):**
    *   *Alasan:* Mengelola data ruang fisik dan log pemakaian ruangan (`UsageLog`).
    *   *Confidence:* High.

### Domain: Employee
*   **Staff (Aggregate Root):**
    *   *Alasan:* Mengelola data karyawan non-guru dan absensi harian GPS (`EmployeeAttendance`).
    *   *Confidence:* High.

---

## 2. Aggregate Structure

Representasi visual pohon hierarki kepemilikan Aggregate Root terhadap entitas anak (*Child Entity*) dan referensi eksternal:

### 2.1 Student Aggregate
```
Student (Aggregate Root)
├── Student Profile Info [Internal Entity]
├── StudentDiscount [Child Entity] (Ownership: 1-to-Many)
├── StudentTask [Child Entity] (Ownership: 1-to-Many)
├── User [External Reference] (Identity link to users.id)
├── Classroom [External Reference] (Relationship to classrooms.id)
└── AcademicYear [External Reference] (Relationship to academic_years.id)
```

### 2.2 Classroom Aggregate
```
Classroom (Aggregate Root)
├── ClassSchedule [Child Entity] (Ownership: 1-to-Many)
├── LearningObjective [Child Entity] (Ownership: 1-to-Many)
├── Chapter [Child Entity] (Ownership: 1-to-Many)
├── Subject [External Reference] (Relationship to subjects.id)
├── Teacher (Homeroom) [External Reference] (Relationship to teachers.id)
└── Student Roster [External Reference] (Collection of students in this classroom)
```

### 2.3 StudentBill Aggregate
```
StudentBill (Aggregate Root)
├── BillPayment [Child Entity] (Pivot record for transactions)
├── StudentDiscount [Child Entity] (Discount allocated to this bill)
├── FinanceType [External Reference] (Billing Category)
├── AcademicYear [External Reference] (Fiscal period of the bill)
└── Student [External Reference] (Bill target)
```

### 2.4 LmsClassroom Aggregate
```
LmsClassroom (Aggregate Root)
├── LmsAnnouncement [Child Entity] (Ownership: 1-to-Many)
├── LmsMaterial [Child Entity] (Ownership: 1-to-Many)
│   └── LmsMaterialFile [Child Entity]
├── LmsAssignment [Child Entity] (Ownership: 1-to-Many)
│   └── LmsSubmission [Child Entity] (Ownership: 1-to-Many)
│       └── LmsSubmissionFile [Child Entity]
└── Classroom [External Reference] (Sync link to physical classroom)
```

### 2.5 Inventory Aggregate
```
Inventory (Aggregate Root)
├── Loan [Child Entity] (Log peminjaman barang)
├── MaintenanceLog [Child Entity] (Log perawatan perbaikan)
├── Category [External Reference] (Inventory classification)
├── Room [External Reference] (Physical storage location)
└── User (Borrower) [External Reference] (Peminjam barang)
```

---

## 3. Entity Discovery

| Entity | Owner (Root) | Identity Key | Lifecycle / Mutability | Shared Status |
| :--- | :--- | :--- | :--- | :--- |
| **Student** | Student | `id` (int) | Mutable (dapat dimutasi kelas/tahun ajaran) | Shared (LMS, Finance, BK) |
| **Teacher** | Teacher | `id` (int) | Mutable (dapat memperbarui data diri/foto) | Shared (LMS, Academic) |
| **ClassSchedule** | Classroom | `id` (int) | Mutable (dapat direstorasi/diedit jadwal) | Not Shared |
| **LmsAssignment** | LmsClassroom | `id` (int) | Mutable (tenggat waktu/konten dapat diubah) | Shared (Student Portal) |
| **LmsSubmission** | LmsClassroom | `id` (int) | Mutable (siswa dapat mengedit jawaban) | Not Shared |
| **Loan** | Inventory | `id` (int) | Mutable (status berubah saat barang kembali/hilang) | Not Shared |
| **MaintenanceLog** | Inventory | `id` (int) | Mutable (status penanganan oleh teknisi) | Not Shared |
| **Staff** | Staff | `id` (int) | Mutable (profil staf) | Shared (Yayasan Monitoring) |

---

## 4. Value Object Discovery

Untuk merapikan desain domain, objek-objek berikut diidentifikasi sebagai **Value Object** karena tidak memiliki siklus hidup independen dan hanya bertindak sebagai deskriptif:

1.  **Money (Uang):** Menyimpan nominal biaya tagihan, pemotongan diskon, dan jumlah alokasi bayar (`amount`, `final_amount`, `allocated_amount`). *Evidence: StudentBill dan Transaction.*
2.  **GPSCoordinate (Koordinat Geografis):** Menyimpan koordinat lintang dan bujur lokasi absen (`latitude`, `longitude`). *Evidence: AttendanceLocation dan EmployeeAttendance.*
3.  **GPSRadius (Radius Toleransi):** Batasan jarak jangkauan absensi WFO (dalam satuan meter). *Evidence: AttendanceLocation.*
4.  **VANumber (Virtual Account):** String unik identitas pembayaran bank siswa (`va_number`). *Evidence: Student.*
5.  **ScheduleTime (Waktu Jadwal):** Jam mulai dan jam selesai mengajar/bekerja (`start_time`, `end_time`, `work_start_time`). *Evidence: ClassSchedule dan Unit.*
6.  **DueDate (Tanggal Jatuh Tempo):** Tanggal limitasi pembayaran atau pengumpulan tugas (`due_date`). *Evidence: StudentBill dan LmsAssignment.*
7.  **LateMinutes (Menit Terlambat):** Durasi keterlambatan absensi karyawan (`late_minutes`). *Evidence: EmployeeAttendance.*

---

## 5. Aggregate Rules (Invariant)

Setiap Aggregate wajib menjaga aturan bisnis (*Invariant*) agar database selalu konsisten:

### Student Aggregate Invariants
*   Siswa wajib dikaitkan dengan satu `Unit` sekolah aktif dan satu `AcademicYear` aktif.
*   Siswa hanya diperbolehkan terdaftar di satu `Classroom` fisik aktif pada periode berjalan.

### StudentBill Aggregate Invariants
*   Jumlah terbayar (`paid_amount`) pada `StudentBill` tidak boleh melebihi nilai tagihan akhir (`final_amount`).
*   Nilai tagihan akhir (`final_amount`) harus berupa kalkulasi dari `tagihan_awal - diskon`.

### LmsClassroom Aggregate Invariants
*   Tugas (`LmsAssignment`) hanya dapat menerima pengumpulan (`LmsSubmission`) jika tugas tersebut telah dirilis statusnya menjadi `published`.
*   Submission yang dikirim melewati batas `due_date` wajib secara otomatis diatur statusnya menjadi `late`.

### Staff Aggregate Invariants
*   Absensi harian (`EmployeeAttendance`) hanya boleh di-Check In satu kali per hari per staf.
*   Check Out absensi hanya diperbolehkan jika Check In hari ini sudah dilakukan sebelumnya.

---

## 6. Lifecycle Management

### Pembuatan (Creation)
*   **Student / Classroom:** Dibuat secara administratif oleh Admin Unit/Yayasan.
*   **StudentBill:** Diterbitkan secara massal oleh sistem keuangan yayasan di awal semester/bulan.
*   **LmsClassroom:** Dipicu oleh Guru saat menginisiasi sinkronisasi rombel fisik ke sistem LMS virtual.
*   **GPS Attendance:** Dipicu secara mandiri oleh Staf/Guru melalui browser perangkat seluler setiap hari kerja.

### Modifikasi (Mutation)
*   Perubahan kelas siswa (`Student`) hanya diizinkan melalui proses resmi promosi kelas (`ClassPromotionController`) yang melacak histori pemindahan.
*   Perubahan status `StudentBill` hanya dipicu oleh transaksi pembayaran lunas/sebagian yang diproses kasir atau mutasi CSV.

### Penghapusan (Deletion)
*   Entitas yang merekam jejak keuangan (`StudentBill`, `Transaction`) dan riwayat akademis (`Student`, `Classroom`) **dilarang dihapus** (menggunakan Soft Delete atau dinonaktifkan statusnya) untuk menjaga integritas audit laporan tahunan.

---

## 7. Aggregate Dependencies Diagram

Arah dependensi antara Aggregate Root dalam sistem:

```
[User Aggregate] <────── [Staff Aggregate]
    ▲
    │
[Student Bill Aggregate] ──> [Student Aggregate] <── [LmsClassroom Aggregate]
                                  │
                                  ▼
                        [Classroom Aggregate]
```

---

## 8. Domain Services

Logika bisnis yang bersifat lintas entitas dan tidak cocok ditempatkan langsung di Model atau Controller diklasifikasikan sebagai **Domain Services**:

1.  **WaterfallPaymentEngine:** Layanan untuk mendistribusikan nominal dana transaksi mutasi bank secara berjenjang ke tagihan tertua siswa (*FIFO due date*). *Evidence: Terletak di TransactionController.*
2.  **InventoryCodeGenerator:** Layanan khusus untuk memformulasi nomor seri barang inventaris berdasarkan kategori, unit, dan tanggal perolehan barang. *Evidence: Terletak di Sarpar/Services/InventoryCodeGenerator.php.*
3.  **GPSDistanceCalculator:** Penghitungan jarak spasial antara koordinat GPS karyawan dengan lokasi kantor menggunakan rumus Haversine. *Evidence: Terletak di Employee/Controllers/AttendanceController.php.*

---

## 9. Domain Events (Implicit)

Walaupun aplikasi belum mengimplementasikan sistem Laravel Event Dispatcher secara eksplisit, terdapat kejadian bisnis penting (*Domain Events*) yang dipicu oleh alur kerja:

*   **StudentRegistered:** Dipicu saat siswa baru berhasil di-insert ke database.
*   **ClassPromoted:** Dipicu setelah proses kelulusan/kenaikan kelas massal selesai dieksekusi.
*   **PaymentReceived:** Dipicu ketika transaksi pembayaran mutasi bank berhasil dicocokkan dengan tagihan siswa.
*   **AssignmentSubmitted:** Dipicu saat file jawaban tugas LMS siswa berhasil disimpan di server.
*   **AttendanceRecorded:** Dipicu ketika koordinat GPS staf valid dan absensi disetujui masuk basis data.
*   **MaintenanceResolved:** Dipicu setelah teknisi menandai kerusakan barang inventaris telah selesai diperbaiki.

---

## 10. Repository Candidates

Aggregate Root berikut sangat direkomendasikan memiliki kelas **Repository** untuk mengisolasi logika query database dari model Eloquent:

1.  **StudentRepository:** Karena siswa dikonsumsi oleh modul Akademik, Keuangan, LMS, BK, dan Portal. Pemisahan query status aktif dan rombel sangat mendesak.
2.  **StudentBillRepository:** Mengelola query tagihan jatuh tempo, tunggakan, dan kalkulasi pembayaran waterfall yang rumit.
3.  **LmsClassroomRepository:** Mengelola pencarian forum stream kelas daring, filter tugas aktif, dan rekap gradebook.

---

## 11. Factory Candidates

Aggregate yang layak dibuat menggunakan **Domain Factory**:
*   **StudentBillFactory:** Mengingat tagihan memiliki dependensi kompleks dengan tahun ajaran aktif, jenis tagihan, beasiswa beasiswa, dan data siswa. Factory akan menjamin pembuatan draf invoice tagihan selalu konsisten.
*   **ClassScheduleFactory:** Untuk menangani pengkloningan jadwal mingguan antar kelas secara aman tanpa duplikasi jam mengajar guru.

---

## 12. Specification Candidates

Validasi aturan bisnis kompleks yang layak diekstrak menjadi **Specification Pattern**:
*   **IsEligibleForPromotionSpecification:** Memvalidasi apakah siswa diperbolehkan naik kelas (memeriksa ketiadaan tunggakan SPP di modul Finance dan batas aman poin pelanggaran BK di modul Counseling).
*   **IsWithinAttendanceRadiusSpecification:** Memvalidasi apakah posisi lintang/bujur karyawan saat absen berada di dalam batas radius lokasi absensi unit kerjanya.

---

## 13. Aggregate Boundary Rules
*   **Akses Publik (Public):** Hanya Aggregate Root yang boleh diakses langsung dari luar (misalnya controller luar memanggil `Student::find()`).
*   **Akses Privat (Private):** Entitas anak seperti `BillPayment` atau `LmsSubmissionFile` tidak boleh dimanipulasi langsung tanpa melalui Aggregate Root terkait (`StudentBill` dan `LmsClassroom`).

---

## 14. Refactoring Opportunity & DDD Recommendation

Berdasarkan temuan arsitektur internal, berikut adalah rekomendasi refactoring tingkat enterprise:

1.  **Fat Controller & Missing Domain Services:**
    *   *Bottleneck:* Controller keuangan (`TransactionController`) dan promosi kelas (`ClassPromotionController`) bertindak sebagai *fat controllers* yang menampung logika bisnis waterfall payment dan kalkulasi kenaikan kelas di dalam method controller.
    *   *Rekomendasi:* Pindahkan logika waterfall ke `App\Domain\Finance\Services\WaterfallPaymentService` dan logika kenaikan kelas ke `App\Domain\Academic\Services\PromotionService`. Controller hanya bertindak sebagai penangkap request dan pengirim respons.
2.  **Duplikasi Model Subjek dan Controller Akademik:**
    *   *Bottleneck:* Ditemukannya `Subject.php` model dan `SubjectController.php` di luar folder modul (misplaced path). Hal ini melanggar batas Aggregate Boundary.
    *   *Rekomendasi:* Hapus file redundan di luar modul dan pastikan seluruh rute serta import hanya mengarah ke kelas di bawah namespace `App\Modules\Academic\Models\Subject` dan `App\Modules\Academic\Controllers\SubjectController`.
3.  **Penerapan Laravel Events & Listeners:**
    *   *Bottleneck:* Modul BK (`Counseling`) memanggil `WhatsAppHelper` secara sinkronus di dalam method controller pelanggaran.
    *   *Rekomendasi:* Picu Laravel event `ViolationRecorded`. Buat Listener `SendParentWhatsAppAlert` yang mendengarkan event tersebut dan mengirim pesan WhatsApp di background menggunakan antrean (`Queue`). Hal ini menghilangkan dependensi ketat antara modul BK dengan modul pengiriman pesan.
