# 14_ACADEMIC_FORENSIC_VERIFICATION

## 1. Executive Summary
**Confidence Level: High**

Berdasarkan investigasi forensik mendalam terhadap seluruh source code controller, model, migrasi database, dan komponen halaman Vue di aplikasi Namira ERP, kami menyatakan bahwa **laporan audit sebelumnya (Stage 13) adalah 100% BENAR**. 

Fungsionalitas operasional akademik sekolah nyata hanya selesai di tingkat penginputan jurnal mengajar harian guru. Modul penilaian formal (UTS, UAS, Kurikulum Merdeka, e-Rapor, Penentuan Kelulusan Otomatis) **sama sekali tidak ditemukan (Not Found)** di dalam basis kode aplikasi. Modul penilaian yang ada saat ini murni terbatas pada penilaian tugas digital di modul LMS virtual.

---

## 2. Workflow Verification

Kami melakukan verifikasi rinci terhadap klaim audit sebelumnya berdasarkan bukti fisik kode:

### A. Kalender Akademik & Tahun Ajaran
*   *Status Audit:* **BENAR**
*   *Evidence:* 
    *   Tabel `academic_years` mengelola data semester aktif (`is_active`).
    *   Model `App\Modules\Yayasan\Models\AcademicYear.php` digunakan oleh modul akademis dan LMS untuk menyaring data rombel aktif (`where('academic_year_id', $activeYearId)`).

### B. Modul Penilaian Akademik (Nilai & Rapor)
*   *Status Audit:* **BENAR (Tidak Ada / Not Found)**
*   *Evidence:*
    *   Kueri kata kunci `rapor`, `uts`, `uas`, `sumatif`, `formatif`, `kkm` pada direktori `/app` menunjukkan **ketiadaan model dan pengontrol penilaian akademis**.
    *   Istilah **"Nilai"** hanya ditemukan pada model `App\Modules\LMS\Models\LmsSubmission.php` (kolom `grade`) untuk penilaian tugas LMS dan controller `LmsClassroomController.php:269` (`Nilai berhasil disimpan`).
    *   Istilah **"Rapor"** hanya merujuk pada "Rapor Absensi" karyawan (`Rapor_Absensi_...pdf`) pada `AttendanceDataController.php:534`.

### C. Alur Kenaikan Kelas (Class Promotion)
*   *Status Audit:* **BENAR**
*   *Evidence:*
    *   `ClassPromotionController.php:115-205` memproses pembaruan massal `classroom_id` pada model `Student` secara transaksional, namun **tidak ada kueri sama sekali** untuk memeriksa akumulasi nilai tugas LMS siswa, status BK, atau tagihan SPP.

### D. Alur Alumni & Kelulusan
*   *Status Audit:* **BENAR (Tidak Ada / Not Found)**
*   *Evidence:*
    *   Tidak ditemukan file model atau controller bernamakan `Alumni` atau `Graduation`. Siklus hidup siswa berhenti secara permanen di database setelah diubah statusnya di menu kenaikan kelas.

---

## 3. Workflow Gap (Belum Ada)

1.  **Penyusunan Rapor Akhir Semester Kurikulum Merdeka (e-Rapor):**
    *   *Bukti:* Tidak ada tabel database untuk menampung deskripsi Capaian Pembelajaran (CP) akhir, nilai rapor akhir, maupun pengesahan rapor digital oleh Kepala Sekolah.
2.  **Mesin Ujian Remedial & Pengayaan (Remedial Engine):**
    *   *Bukti:* Modul LMS (`LmsStudentController.php`) hanya menerima pengumpulan tugas reguler. Tidak ada alur perbaikan nilai terpadu bagi siswa yang berada di bawah KKM.
3.  **Portal Legalisir & Validasi Alumni:**
    *   *Bukti:* Entitas siswa yang lulus dilepas begitu saja tanpa pencatatan tracer study.

---

## 4. Broken Workflow (Terputus)

Silo data antar-modul digambarkan sebagai berikut:

```
[Academic Jurnal] (Mencatat TP / Absensi Harian)
       │
   (Terputus) -> Tidak terhubung ke tugas LMS
       ▼
   [LmsClassroom] (Mencatat Nilai Tugas Siswa)
       │
   (Terputus) -> Nilai tidak diakumulasi ke rapor akhir
       ▼
   [Rapor Akhir] (Tidak Ada / Not Found)
       │
   (Terputus) -> Kenaikan kelas manual tanpa prasyarat nilai
       ▼
[ClassPromotion] (Memindahkan siswa secara massal)
```

---

## 5. Duplicate Workflow

*   **Pencatatan Kehadiran Siswa:**
    *   *Bukti:* Sistem memiliki dua tabel penyimpanan presensi siswa aktif: tabel `student_attendances` (diolah via `StudentAttendanceController.php`) dan tabel `journal_attendance` (diolah via `TeachingJournalController.php`). 
    *   *Masalah:* Guru piket mencatat ketidakhadiran siswa di pagi hari di menu absensi, namun Guru Mapel tetap harus menginput manual status kehadiran siswa yang sama pada jurnal ajar kelas di jam pelajaran berikutnya. Tidak ada sinkronisasi asinkronus antara kedua tabel ini.

---

## 6. Manual Workflow

*   **Pembuatan Jadwal Pelajaran (`ScheduleController.php`):**
    *   *Bukti:* Meskipun admin dapat melakukan kloning jadwal tahun lalu, penentuan agar guru tidak mengajar di dua kelas berbeda pada jam yang sama (anti-bentrok) tidak divalidasi oleh sistem, melainkan masih diperiksa manual oleh admin kurikulum sebelum menekan submit.
*   **Evaluasi Rekomendasi Naik Kelas:**
    *   Wali kelas harus membuka menu kesiswaan BK secara manual untuk memeriksa total poin pelanggaran siswa satu per satu sebelum merekomendasikan kelulusan pada rapat pleno sekolah.

---

## 7. UX Workflow Problem (Context Switching)

*   **Silo Workspace Guru:**
    *   *Bukti:* Menu rute pada `routes/web.php` memisahkan rute Jurnal Mengajar (`yayasan.teaching-journal`) dengan rute LMS (`lms.teacher.classrooms`).
    *   *Masalah:* Guru yang mengajar kelas 10-A Fisika jam pertama harus membuka Jurnal untuk absen kelas dan TP, lalu melakukan klik menu terpisah dan pindah halaman ke LMS Fisika 10-A untuk merilis materi presentasi digital.

---

## 8. Business Risk

*   **Kerugian Keuangan Yayasan (Finansial Leakage):**
    *   *Bukti:* `ClassPromotionController.php:178` langsung mengalokasikan kelas baru tanpa memanggil relasi ke `StudentBill` untuk verifikasi piutang.
    *   *Risiko:* Siswa yang menunggak SPP berbulan-bulan tetap dapat naik ke kelas berikutnya tanpa kendala administratif di sistem ERP.
*   **Cacat Akuntabilitas Akademik:**
    *   Siswa dinaikkan kelasnya tanpa bukti kelulusan nilai rapor digital terintegrasi di ERP, memicu risiko audit eksternal dari dinas pendidikan jika administrasi nilai sekolah dipertanyakan.

---

## 9. Priority Matrix

| Urutan | Alur Bisnis | Prioritas | Dampak / Risiko Operasional |
| :--- | :--- | :--- | :--- |
| 1 | **Global Multi-Tenancy Security Lock** | **Critical** | Mencegah akses silang data siswa SD/SMP/SMA. |
| 2 | **Integrasi Validasi Kelulusan Kenaikan Kelas** | **Critical** | Memblokir kenaikan kelas siswa yang menunggak SPP / sanksi BK berat. |
| 3 | **Modul e-Rapor Kurikulum Merdeka** | **High** | Mengintegrasikan nilai harian menjadi dokumen legal rapor. |
| 4 | **Penyatuan Jurnal Ajar & LMS Workspace** | **High** | Memotong context switching navigasi mengajar guru. |
| 5 | **Otomatisasi Sinkronisasi Absensi Siswa** | **Medium** | Menghilangkan duplikasi input guru piket dan guru kelas. |
| 6 | **Portal Alumni & Tracer Study** | **Low** | Pencatatan data kelulusan jangka panjang. |
