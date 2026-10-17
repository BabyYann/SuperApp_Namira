# Functional Requirements (Spesifikasi Kebutuhan Fungsional Sistem)

Dokumen ini mendefinisikan seluruh kebutuhan fungsional (*Functional Requirements - FR*) yang diterapkan pada SuperApp Namira berdasarkan audit kode sumber aktual.

---

## 1. Modul Multi-Tenancy & Tata Kelola Yayasan (FR-CORE)

- **FR-CORE-001:** Sistem HARUS mendukung isolasi data multi-tenancy di mana entitas operasional (siswa, guru, rombel, keuangan, inventaris) terikat pada `unit_id` atau Spatie Teams `team_id`.
- **FR-CORE-002:** Pengguna dengan peran global yayasan HARUS dapat beralih konteks unit aktif secara instan (`POST /yayasan/switch-unit`), yang mengubah nilai `active_unit_id` di sesi dan cookie tanpa merusak sesi autentikasi.
- **FR-CORE-003:** Pengguna dengan peran unit (misal: guru atau admin SD) HARUS dibatasi secara mutlak hanya dapat mengakses data milik unit sekolah tempat ia bertugas (`CheckUnitScope.php`).
- **FR-CORE-004:** Sistem HARUS menyediakan manajemen master data unit sekolah (KB Kraksaan, KB Dringu, TK Kraksaan, TK Dringu, SD, SMP, Pavlov Center, Daycare) lengkap dengan koordinat latitude/longitude, jam operasional, dan profil kepala sekolah.
- **FR-CORE-005:** Sistem HARUS mendukung manajemen tahun akademik aktif yang membatasi siklus penjadwalan KBM dan tagihan keuangan. Hanya boleh ada 1 tahun akademik berstatus `is_active = true` per rentang waktu.
- **FR-CORE-006:** Sistem HARUS mencatat jejak audit (*Activity Log*) untuk setiap aksi mutasi data penting (tambah user, hapus jadwal, ubah status bayar, hapus aset).

---

## 2. Modul Akademik & KBM (FR-ACAD)

- **FR-ACAD-001:** Sistem HARUS menyediakan manajemen master siswa (NISN, NIK, nama lengkap, jenis kelamin, tempat/tanggal lahir, nama orang tua/wali, kontak, dan status aktif).
- **FR-ACAD-002:** Sistem HARUS mengelola master guru dan staf pengajar lengkap dengan NIP, gelar akademik, dan mata pelajaran yang diampu.
- **FR-ACAD-003:** Sistem HARUS mendukung pembagian rombongan belajar (kelas) permanen per tingkat jenjang (misal: 1A, 1B, 7A).
- **FR-ACAD-004:** Sistem HARUS memungkinkan penyusunan jadwal pelajaran mingguan (Senin s.d. Sabtu) dengan validasi pencegahan jadwal bentrok guru atau ruang.
- **FR-ACAD-005:** Sistem HARUS mendukung fitur **Sesi Kelas Gabungan (Multi-Classroom Joint Session)** di mana 1 guru dapat mengajar beberapa kelas sekaligus dalam satu slot jam yang sama (misal: PJOK gabungan 4A dan 4B).
- **FR-ACAD-006:** Guru HARUS dapat mengisi Jurnal Mengajar KBM harian yang mencakup materi pembelajaran, capaian TP, catatan kelas, dan absensi siswa per sesi.
- **FR-ACAD-007:** Sistem HARUS menyediakan antarmuka **Gate Check-in Scanner** untuk memindai barcode/QR kartu pelajar saat siswa tiba di gerbang, secara otomatis mencatat waktu check-in, dan memicu event real-time `StudentScannedAtGate`.
- **FR-ACAD-008:** Sistem HARUS menyediakan mesin **Kenaikan Kelas Massal (Class Promotion Engine)** dengan alur validasi prasyarat (cek kelengkapan nilai, absensi), preview mutasi kelas, eksekusi atomik database transaction, dan opsi pembatalan aman (*rollback*).

---

## 3. Modul Kepegawaian & Presensi Mobile (FR-EMP)

- **FR-EMP-001:** Karyawan HARUS dapat melakukan presensi masuk dan pulang melalui aplikasi mobile PWA dengan menyertakan koordinat GPS perangkat dan swafoto (*selfie*) kamera depan.
- **FR-EMP-002:** Sistem HARUS memvalidasi jarak koordinat perangkat terhadap master `AttendanceLocation` menggunakan formula Haversine. Jika jarak > radius meter izin (standar 50-100 meter), request presensi HARUS ditolak.
- **FR-EMP-003:** Sistem HARUS menghitung menit keterlambatan secara otomatis berdasarkan jam masuk yang ditetapkan pada master `Unit` atau `AttendanceLocation`.
- **FR-EMP-004:** Karyawan HARUS dapat mengajukan permohonan dinas luar, cuti, atau sakit dengan menyertakan surat dokter/bukti lampiran.
- **FR-EMP-005:** Pimpinan unit / pengawas yayasan HARUS memiliki antarmuka khusus untuk menyetujui (*approve*) atau menolak (*reject*) pengajuan izin absensi bawahan.
- **FR-EMP-006:** Karyawan HARUS dapat mencatat log giat aktivitas harian (*activity logbook*) beserta foto hasil pekerjaan dan jam pelaksanaan.

---

## 4. Modul Keuangan & SPP (FR-FIN)

- **FR-FIN-001:** Staf keuangan HARUS dapat membuat master jenis pos bayar (SPP Bulanan, Uang Gedung, Seragam, Kegiatan) dan akun rekening kas/bank sekolah.
- **FR-FIN-002:** Sistem HARUS dapat menghasilkan (*generate*) tagihan massal untuk seluruh siswa aktif pada kelas/unit tertentu untuk periode bulan yang dipilih.
- **FR-FIN-003:** Sistem HARUS mendukung penerapan potongan diskon/beasiswa siswa yang mengurangi nominal tagihan pokok secara otomatis.
- **FR-FIN-004:** Kasir HARUS dapat mencatat transaksi pembayaran tunai atau transfer, memperbarui status tagihan (`paid` / `partial`), dan mencetak kuitansi resmi PDF.
- **FR-FIN-005:** Wali murid HARUS dapat mengunggah berkas foto bukti transfer bank melalui portal pembayaran.
- **FR-FIN-006:** Kasir HARUS dapat memverifikasi atau menolak bukti transfer yang diunggah wali murid.
- **FR-FIN-007:** Sistem HARUS menyediakan laporan rekapitulasi tunggakan siswa (*arrears report*) per kelas dan per rentang waktu penagihan.
- **FR-FIN-008:** Bendahara HARUS dapat mencatat transaksi arus kas masuk dan kas keluar yayasan dengan kategorisasi pos akun anggaran.

---

## 5. Modul Bimbingan Konseling & Kesiswaan (FR-COUN)

- **FR-COUN-001:** Guru BK HARUS dapat mengelola master kategori pelanggaran tata tertib beserta sanksi poin kumulatifnya.
- **FR-COUN-002:** Guru piket atau guru BK HARUS dapat mencatat insiden pelanggaran siswa dengan menyertakan kronologi, barang bukti, dan tindakan pembinaan.
- **FR-COUN-003:** Sistem HARUS mencatat riwayat prestasi kejuaraan siswa (akademik, olahraga, seni, tahfidz) beserta tingkat kejuaraan dan piagam sertifikat.
- **FR-COUN-004:** Guru BK HARUS dapat menjadwalkan dan mendokumentasikan sesi konseling bimbingan siswa secara rahasia (*confidential*).

---

## 6. Modul Sarana & Prasarana (FR-SARP)

- **FR-SARP-001:** Koordinator sarpras HARUS dapat mendaftarkan aset barang inventaris lengkap dengan kode unik, nomor seri, kondisi fisik, ruang penyimpanan, dan nilai perolehan.
- **FR-SARP-002:** Sistem HARUS dapat menghasilkan stiker label berisikan QR Code inventaris untuk dicetak secara massal dalam format PDF siap tempel.
- **FR-SARP-003:** Sistem HARUS mencatat peminjaman dan pengembalian barang aset oleh guru/staf beserta tenggat waktu pengembalian.
- **FR-SARP-004:** Koordinator sarpras HARUS dapat mendokumentasikan log pemeliharaan dan perbaikan barang rusak beserta estimasi biaya dan vendor servis.
- **FR-SARP-005:** Sistem HARUS mendukung pencatatan mutasi perpindahan aset antar-ruangan dan berita acara penghapusan (*disposal*) aset afkir.

---

## 7. Modul LMS & Pembelajaran Digital (FR-LMS)

- **FR-LMS-001:** Guru HARUS dapat membuat ruang kelas digital LMS yang terhubung dengan rombel siswa.
- **FR-LMS-002:** Guru HARUS dapat mempublikasikan pengumuman penting di ruang kelas digital.
- **FR-LMS-003:** Guru HARUS dapat mengunggah berkas modul bahan ajar (PDF, presentasi) atau menyematkan tautan video YouTube.
- **FR-LMS-004:** Guru HARUS dapat membuat penugasan terstruktur dengan tenggat waktu (*due date*) dan bobot nilai maksimal.
- **FR-LMS-005:** Siswa HARUS dapat mengunggah berkas jawaban tugas sebelum batas tenggat berakhir. Jika melewati batas, sistem menandai sebagai pengumpulan terlambat (*late submission*).
- **FR-LMS-006:** Guru HARUS dapat memeriksa berkas tugas siswa, memberikan skor nilai numerik, dan menulis catatan feedback evaluasi.

---

## 8. Modul Khusus: Daycare, SPMB, & Ekskul (FR-SPEC)

- **FR-SPEC-001 (Daycare):** Pengasuh HARUS dapat mencatat presensi kedatangan dan kepulangan balita daycare.
- **FR-SPEC-002 (Daycare):** Pengasuh HARUS dapat mencatat log aktivitas harian balita: jam makan, porsi habis/sisa, durasi tidur siang, frekuensi BAB/BAK, dan mood emosi anak.
- **FR-SPEC-003 (Daycare):** Sistem HARUS menampilkan direktori penjemput terotorisasi (*authorized pickup*) lengkap dengan foto identitas untuk verifikasi satpam.
- **FR-SPEC-004 (SPMB):** Calon wali murid baru HARUS dapat mendaftar secara mandiri melalui formulir online, memilih unit sekolah tujuan, dan mengunggah berkas persyaratan.
- **FR-SPEC-005 (SPMB):** Panitia SPMB HARUS dapat memverifikasi berkas, menginput nilai tes seleksi, dan menerbitkan surat status penerimaan santri baru.
- **FR-SPEC-006 (Ekskul):** Pembina HARUS dapat mengelola master klub ekstrakurikuler, menugaskan pelatih, mencatat presensi sesi latihan mingguan, dan menginput nilai rapor ekskul.
