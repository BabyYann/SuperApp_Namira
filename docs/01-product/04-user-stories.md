# User Stories (Kumpulan Cerita Pengguna Sistem SuperApp Namira)

Dokumen ini mendokumentasikan spesifikasi kebutuhan fungsional dalam format *Agile User Stories* baku (*Sebagai [Peran], saya ingin [Fitur/Kebutuhan] agar [Tujuan/Nilai Bisnis]*) untuk seluruh 11 modul SuperApp Namira.

---

## 1. Modul Tata Kelola Yayasan & Multi-Tenancy (YAY)

- **US-YAY-01 (Must-Have):**
  *Sebagai* Super Admin Yayasan,
  *Saya ingin* beralih konteks unit sekolah aktif (*switch active unit*) melalui dropdown di navbar,
  *Agar* saya dapat mengelola data operasional KB, TK, SD, SMP, atau Daycare tanpa perlu logout akun.

- **US-YAY-02 (Must-Have):**
  *Sebagai* Admin Yayasan,
  *Saya ingin* mendaftarkan titik koordinat GPS kantor/sekolah beserta radius toleransinya,
  *Agar* sistem dapat memvalidasi presensi geofence seluruh karyawan secara otomatis.

- **US-YAY-03 (Must-Have):**
  *Sebagai* Pimpinan Yayasan / Kepala Sekolah,
  *Saya ingin* meninjau dan memberikan persetujuan (*approve / reject*) atas pengajuan izin, cuti, atau sakit karyawan,
  *Agar* rekap kehadiran bulanan pegawai akurat untuk perhitungan tunjangan kerja.

- **US-YAY-04 (Should-Have):**
  *Sebagai* Super Admin,
  *Saya ingin* memantau beban CPU, memory, query lambat, dan job queue melalui dashboard Laravel Pulse,
  *Agar* saya dapat mengantisipasi lonjakan trafik sebelum terjadi *downtime* pada jam presensi pagi.

---

## 2. Modul Akademik & KBM (ACA)

- **US-ACA-01 (Must-Have):**
  *Sebagai* Koordinator Kurikulum,
  *Saya ingin* menyusun jadwal mengajar mingguan per kelas dan mapel, termasuk sesi multi-kelas gabungan (misal PJOK),
  *Agar* tidak terjadi jadwal bentrok antar-guru dan ruang kelas teralokasi optimal.

- **US-ACA-02 (Must-Have):**
  *Sebagai* Guru Pengampu,
  *Saya ingin* mengisi jurnal mengajar KBM harian (materi, TP, catatan) dan mencentang absensi siswa langsung dari HP,
  *Agar* rekam capaian kurikulum dan kehadiran siswa terdokumentasi rapi tanpa berkas kertas.

- **US-ACA-03 (Must-Have):**
  *Sebagai* Petugas Gerbang / Satpam,
  *Saya ingin* memindai barcode kartu pelajar siswa saat tiba di sekolah,
  *Agar* waktu kedatangan tercatat otomatis dan orang tua menerima status apakah siswa tepat waktu atau terlambat.

- **US-ACA-04 (Must-Have):**
  *Sebagai* Kepala Sekolah,
  *Saya ingin* menjalankan proses kenaikan kelas massal (*class promotion*) di akhir tahun ajaran,
  *Agar* status rombel siswa naik ke jenjang berikutnya secara otomatis dengan opsi rollback jika ada koreksi.

---

## 3. Modul Kepegawaian & Presensi (EMP)

- **US-EMP-01 (Must-Have):**
  *Sebagai* Guru / Karyawan Sekolah,
  *Saya ingin* melakukan presensi masuk dan pulang melalui aplikasi mobile PWA dengan foto selfie dan GPS,
  *Agar* kehadiran saya tercatat sah hanya jika saya telah berada di dalam area sekolah yang diizinkan.

- **US-EMP-02 (Must-Have):**
  *Sebagai* Karyawan Sekolah,
  *Saya ingin* mencatat log giat harian (*daily activity logbook*) beserta foto dokumentasi hasil kerja,
  *Agar* kinerja harian saya terukur dan dapat diverifikasi oleh pimpinan unit.

- **US-EMP-03 (Should-Have):**
  *Sebagai* Karyawan Sekolah,
  *Saya ingin* melihat riwayat absensi bulanan saya lengkap dengan status terlambat dan akumulasi menit keterlambatan,
  *Agar* saya dapat mengevaluasi kedisiplinan kerja saya sendiri.

---

## 4. Modul Keuangan Sekolah & SPP (FIN)

- **US-FIN-01 (Must-Have):**
  *Sebagai* Staf Keuangan,
  *Saya ingin* menerbitkan tagihan SPP bulanan secara massal ke seluruh siswa aktif pada rombel tertentu,
  *Agar* proses penagihan biaya pendidikan efisien dan terjadwal setiap awal bulan.

- **US-FIN-02 (Must-Have):**
  *Sebagai* Kasir Sekolah,
  *Saya ingin* mencatat penerimaan pembayaran tagihan secara tunai dan mencetak kuitansi resmi PDF,
  *Agar* wali murid mendapatkan bukti bayar sah berlogo yayasan saat bertransaksi di loket.

- **US-FIN-03 (Must-Have):**
  *Sebagai* Wali Santri / Siswa,
  *Saya ingin* mengunggah foto bukti transfer bank atas tagihan SPP saya,
  *Agar* kasir sekolah dapat memverifikasi pembayaran saya tanpa saya harus datang langsung ke sekolah.

- **US-FIN-04 (Must-Have):**
  *Sebagai* Bendahara Yayasan,
  *Saya ingin* memantau daftar siswa yang menunggak pembayaran (*arrears report*) beserta mutasi kas masuk/keluar,
  *Agar* likuiditas keuangan yayasan tetap terkendali dan laporan kas bulanan dapat dipertanggungjawabkan.

---

## 5. Modul Bimbingan Konseling & Kesiswaan (COU)

- **US-COU-01 (Must-Have):**
  *Sebagai* Guru Piket / Guru BK,
  *Saya ingin* mencatat insiden pelanggaran tata tertib siswa beserta bobot poin sanksinya,
  *Agar* rekam jejak kedisiplinan siswa tercatat dan orang tua mendapat pemberitahuan resmi.

- **US-COU-02 (Must-Have):**
  *Sebagai* Guru BK,
  *Saya ingin* mencatat jadwal dan hasil sesi konseling pembinaan siswa secara rahasia,
  *Agar* perkembangan psikologis dan komitmen perbaikan sikap siswa terdokumentasi berkesinambungan.

- **US-COU-03 (Should-Have):**
  *Sebagai* Tim Kesiswaan,
  *Saya ingin* mendokumentasikan sertifikat dan piagam prestasi kejuaraan yang diraih siswa,
  *Agar* portofolio prestasi sekolah terdata lengkap untuk akreditasi dan publikasi.

---

## 6. Modul Sarana & Prasarana (SAR)

- **US-SAR-01 (Must-Have):**
  *Sebagai* Koordinator Sarpar,
  *Saya ingin* mendaftarkan aset barang sekolah ke ruang tertentu dan mencetak stiker QR codenya,
  *Agar* setiap inventaris memiliki identitas unik yang dapat dipindai saat inventarisasi fisik.

- **US-SAR-02 (Must-Have):**
  *Sebagai* Guru / Karyawan,
  *Saya ingin* mengajukan peminjaman alat/barang aset (misal proyektor atau kamera),
  *Agar* penggunaan aset terkontrol dan penanggung jawab barang tercatat jelas.

- **US-SAR-03 (Must-Have):**
  *Sebagai* Koordinator Sarpar,
  *Saya ingin* mencatat riwayat perbaikan barang rusak beserta estimasi biayanya,
  *Agar* anggaran pemeliharaan gedung dan peralatan terpantau secara transparan.

---

## 7. Modul LMS & Pembelajaran Digital (LMS)

- **US-LMS-01 (Must-Have):**
  *Sebagai* Guru Pengampu,
  *Saya ingin* membuat ruang kelas digital dan mengunggah modul bahan ajar (PDF/link video),
  *Agar* siswa dapat mempelajari materi pelajaran kapan saja secara mandiri.

- **US-LMS-02 (Must-Have):**
  *Sebagai* Guru Pengampu,
  *Saya ingin* memberikan penugasan mandiri dengan tenggat waktu (*deadline*) tertentu,
  *Agar* siswa mengumpulkan tugas tepat waktu dan tugas yang terlambat ditandai otomatis oleh sistem.

- **US-LMS-03 (Must-Have):**
  *Sebagai* Siswa,
  *Saya ingin* mengunggah berkas jawaban tugas saya ke kelas LMS,
  *Agar* tugas saya diterima guru dan saya memperoleh nilai serta catatan evaluasi.

---

## 8. Modul Khusus: Daycare, SPMB, & Ekskul

- **US-DAY-01 (Must-Have):**
  *Sebagai* Pengasuh Daycare,
  *Saya ingin* mencatat log harian balita (jadwal makan, durasi tidur, buang air, mood),
  *Agar* orang tua balita dapat memantau kenyamanan dan rutinitas anaknya sepanjang hari.

- **US-DAY-02 (Must-Have):**
  *Sebagai* Pengasuh Daycare / Satpam,
  *Saya ingin* memverifikasi foto dan identitas wali yang menjemput balita (*authorized pickup*),
  *Agar* keamanan anak terjamin dan balita tidak diserahkan kepada pihak yang tidak dikenal.

- **US-SPMB-01 (Must-Have):**
  *Sebagai* Calon Wali Murid Baru,
  *Saya ingin* mendaftar secara online dan mengunggah berkas persyaratan (KK, Akta Lahir),
  *Agar* anak saya dapat mengikuti seleksi penerimaan murid baru tanpa harus antre fisik.

- **US-EKS-01 (Must-Have):**
  *Sebagai* Pelatih Ekstrakurikuler,
  *Saya ingin* mengabsen kehadiran anggota ekskul pada sesi latihan mingguan dan menginput nilai akhir,
  *Agar* rekap kehadiran dan nilai ekskul masuk ke rapor siswa secara akurat.
