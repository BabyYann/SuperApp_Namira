# 13_ACADEMIC_WORKFLOW

## 1. Academic Workflow Score: 52/100
**Confidence Level: High**

Evaluasi kematangan operasional akademik Namira ERP menunjukkan nilai **52/100**. Sistem berhasil mendistribusikan data harian mengajar guru (presensi siswa di kelas & pencatatan TP Kurikulum Merdeka) secara integratif di tingkat Jurnal. Namun, hilangnya modul penilaian akademik terpadu (Rapor Akhir, Remedial, dan Kelulusan) membuat sistem belum memenuhi kriteria standard ERP Akademik Sekolah Enterprise.

---

## 2. Workflow Maturity: Level 2 (Managed)
**Confidence Level: High**

*   **Evaluasi:** Proses akademik telah terkelola (*Managed*) secara digital untuk operasional harian kelas. Guru dapat mengisi absensi kelas dan materi ajar langsung dari jadwal harian. Namun, alur tersebut terputus pada akhir tahun ajaran (proses kenaikan kelas massal dilakukan manual oleh admin tanpa integrasi data nilai rapor siswa karena modul rapor belum ada).

---

## 3. Workflow Completeness: 48%
**Confidence Level: High**

Siklus Hidup Akademik (*Academic Life Cycle*):

| Tahapan Alur | Status | Deskripsi Integrasi |
| :--- | :--- | :--- |
| **Academic Year** | Ada (Lengkap) | Mengatur status aktif semester ganjil/genap yayasan. |
| **Semester** | Ada (Lengkap) | Terikat langsung pada objek Academic Year. |
| **Class** | Ada (Lengkap) | Data rombel fisik sekolah per tingkat. |
| **Student Placement**| Ada (Lengkap) | Alokasi manual siswa ke rombel fisik. |
| **Subject** | Ada (Lengkap) | Master mata pelajaran per unit. |
| **Teacher Assignment**| Ada (Lengkap) | Pengaitan guru wali kelas dan guru pengampu jadwal. |
| **Class Schedule** | Ada (Lengkap) | Jadwal mingguan guru (bisa kloning & reset). |
| **School Calendar** | Ada (Lengkap) | Kalender libur dinamis terintegrasi ke absensi karyawan. |
| **Daily Learning** | Ada (Lengkap) | Linimasa stream materi digital pada LMS. |
| **Attendance** | Ada (Lengkap) | Absensi harian kelas siswa (`journal_attendance`). |
| **Teaching Journal** | Ada (Lengkap) | Pencatatan jurnal dan ketercapaian TP secara instan. |
| **Assignment** | Ada (Lengkap) | Unggah tugas LMS oleh guru dan submit oleh siswa. |
| **Assessment** | Ada (Sebagian) | Penilaian tugas LMS dan gradebook rata-rata per mata pelajaran. |
| **Remedial** | **Not Found** | *Evidence Tidak Ditemukan* (Tidak ada penanganan remedial). |
| **Enrichment** | **Not Found** | *Evidence Tidak Ditemukan* (Tidak ada materi enrichment). |
| **Final Grade** | **Not Found** | *Evidence Tidak Ditemukan* (Hanya ada rata-rata nilai tugas LMS). |
| **Report Card (Rapor)**| **Not Found** | *Evidence Tidak Ditemukan* (Penyusunan Rapor Kurmer nihil). |
| **Promotion** | Ada (Lengkap) | Eksekusi kelulusan/kenaikan rombel massal via admin. |
| **Graduation** | **Not Found** | *Evidence Tidak Ditemukan* (Kelulusan akademik otomatis nihil). |
| **Alumni** | **Not Found** | *Evidence Tidak Ditemukan* (Pelacakan data alumni nihil). |

---

## 4. Flow Yang Sudah Baik
*   **Integrasi Schedule → Jurnal → Presensi Kelas:** Guru dapat membuka jadwal mengajar hari ini secara instan di menu `TeachingJournalController@index`. Pengisian jurnal ajar secara otomatis menampilkan daftar siswa di kelas tersebut (`Student::where('classroom_id')`) untuk input absensi harian kelas dan secara bersamaan memunculkan indikator bab pembelajaran (`Chapter::with('learningObjectives')`) untuk alokasi Tujuan Pembelajaran (TP) hari itu.

---

## 5. Flow Yang Belum Ada
*   **Penyusunan Rapor Akhir Semester (e-Rapor):** Tidak ada modul untuk menyusun, menilai, mengesahkan, dan mengunduh lembar Rapor Kurikulum Merdeka bagi siswa.
*   **Evaluasi Kelulusan Akademik Otomatis:** Keputusan kelulusan siswa tidak berbasis akumulasi nilai di sistem, melainkan berupa aksi pemindahan status manual oleh admin.
*   **Tracer Study & Forum Alumni:** Siklus alumni setelah lulus terputus sepenuhnya dari database.

---

## 6. Flow Yang Belum Terhubung
*   **Jurnal Mengajar & Nilai Tugas (LMS):** Jurnal mengajar mencatat TP yang diajarkan, namun tidak memiliki hubungan dengan tugas/assignment LMS yang diberikan pada hari yang sama. Guru mengelola jurnal di modul Akademik, namun memberi nilai tugas di modul LMS (Data Silo).
*   **Nilai Tugas & Rapor:** Rata-rata nilai pada LMS Gradebook tidak dapat di-pull ke dalam sistem nilai rapor karena ketiadaan modul rapor.
*   **Rapor & Kenaikan Kelas:** Aksi kenaikan kelas massal (`ClassPromotionController`) dijalankan secara independen oleh admin tanpa melakukan validasi apakah siswa tersebut memiliki nilai rapor tuntas atau masih menunggak tagihan SPP.

---

## 7. Flow Yang Masih Manual
*   **Penyusunan Jadwal Pelajaran Baru:** Meskipun sistem mendukung fitur duplikasi (*clone*), pembagian alokasi jam mengajar guru per hari agar tidak bentrok masih dihitung manual oleh staf kurikulum di luar sistem sebelum dimasukkan ke form satu per satu.
*   **Verifikasi Status Kelulusan BK:** Wali kelas mengevaluasi batas toleransi poin sanksi BK siswa asuhannya secara manual sebelum merekomendasikan kenaikan kelas.

---

## 8. Business Risk

*   **Risiko Kenaikan Kelas Siswa Bermasalah:** Siswa yang memiliki catatan poin pelanggaran BK di atas batas toleransi atau masih memiliki tunggakan SPP dapat dinaikkan kelasnya oleh admin secara bebas karena modul `ClassPromotion` tidak memvalidasi status keuangan dan catatan sanksi BK siswa.
*   **Risiko Kehilangan Hak Belajar (LMS):** Kenaikan kelas massal langsung memperbarui kolom `classroom_id` siswa ke kelas baru. Siswa yang baru naik kelas langsung kehilangan akses ke forum LMS kelas lamanya dan tidak dapat mengunduh materi ajar tahun lalu untuk remedial.

---

## 9. UX Problem (Flow Membingungkan)

*   **Dualisme Workspace Mengajar Guru:**  
    *   *Deskripsi:* Untuk kelas yang sama, Guru harus membuka menu **Jurnal Mengajar** (di modul Akademik) untuk mengisi absen kelas dan TP, lalu harus keluar ke menu **LMS Classrooms** untuk mengunggah materi pelajaran dan menilai tugas.
    *   *Dampak:* Guru mengeklik terlalu banyak menu dan berpindah halaman secara berulang untuk aktivitas KBM yang sejenis.

---

## 10. Duplicate Process
*   **Pencatatan Kehadiran Siswa Ganda:** Absensi siswa dapat dicatat di menu absensi harian siswa (`StudentAttendanceController`) oleh guru piket, tetapi juga wajib diisi kembali oleh guru mapel saat mengisi Jurnal Mengajar kelas (`TeachingJournalController`). Dua tabel ini (`student_attendances` dan `journal_attendance`) menyimpan status kehadiran siswa yang sama pada hari yang sama tanpa adanya sinkronisasi otomatis.

---

## 11. Automation Opportunity
*   **Sinkronisasi Absensi Harian dari Jurnal:** Pengisian absensi di Jurnal Mengajar jam pertama oleh Guru mapel secara otomatis menyinkronkan data ke tabel `student_attendances` hari itu untuk mengurangi beban input ganda guru piket.
*   **Auto-Grading Accumulator:** Mengakumulasikan nilai tugas LMS secara otomatis menjadi draf nilai rapor akhir semester berdasarkan persentase bobot yang ditentukan sekolah.

---

## 12. Rekomendasi Refactoring Alur Akademik (SOLID Perspective)

### Rekomendasi 1: Penggabungan Workspace Guru (Unified Classroom Workspace)
*   **Problem:** Guru harus berpindah modul antara Jurnal Akademik dan LMS virtual untuk satu rombel yang sama.
*   **Evidence:** `TeachingJournalController` dan `LmsClassroomController` terpisah modul rutenya.
*   **Impact:** Menurunkan kepuasan pengguna (Guru) akibat navigasi yang membingungkan.
*   **Recommendation:** Satukan halaman detail kelas LMS dan Jurnal Mengajar ke dalam satu Dasbor Kelas Mengajar terpadu. Guru cukup masuk ke dasbor kelas tersebut untuk melakukan absensi, menulis jurnal, mengunggah materi, dan menilai tugas secara linear.
*   **Priority:** High | **Confidence:** High

### Rekomendasi 2: Integrasi Validasi Kenaikan Kelas (Specification Pattern)
*   **Problem:** Kenaikan kelas massal rawan meloloskan siswa yang menunggak administrasi keuangan atau memiliki kasus BK berat.
*   **Evidence:** `ClassPromotionController::store()` memperbarui `classroom_id` siswa tanpa memeriksa tunggakan SPP (`student_bills`) dan sanksi (`violations`).
*   **Impact:** Yayasan mengalami kerugian finansial akibat piutang SPP macet yang terbawa ke kelas baru.
*   **Recommendation:** Terapkan Specification Pattern `IsEligibleForPromotionSpecification` yang memvalidasi kondisi `paid_status` tagihan SPP dan sisa batas poin BK siswa sebelum admin mengeksekusi kenaikan kelas.
*   **Priority:** Critical | **Confidence:** High

### Rekomendasi 3: Sinkronisasi Otomatis Absensi Siswa
*   **Problem:** Duplikasi input kehadiran siswa di tabel `student_attendances` dan `journal_attendance`.
*   **Evidence:** Adanya tabel terpisah dengan data redudan kehadiran siswa per hari.
*   **Impact:** Membuang waktu guru dan TU dalam mencatat data yang sama.
*   **Recommendation:** Buat Event Listener `SyncClassroomAttendance` yang otomatis memperbarui data absensi harian siswa jika guru mapel telah menginput absensi di jurnal mengajar kelas jam pertama.
*   **Priority:** High | **Confidence:** High
