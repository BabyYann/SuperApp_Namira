# Acceptance Criteria (Kriteria Keberterimaan Fitur)

Dokumen ini mendefinisikan kriteria keberterimaan (*Acceptance Criteria - AC*) formal menggunakan format standar industri **Given-When-Then (GWT)** untuk alur-alur kerja kritikal di SuperApp Namira.

---

## 1. Fitur Presensi Mobile GPS & Selfie Geofencing (US-EMP-01)

### AC-EMP-01.1: Presensi Berhasil di Dalam Radius Izin
- **Given:** Karyawan telah login ke akun mereka di aplikasi PWA, mengaktifkan GPS perangkat, dan berada pada jarak 35 meter dari koordinat kantor unit (batas toleransi lokasi: 50 meter).
- **When:** Karyawan mengambil foto selfie kamera depan dan menekan tombol "Kirim Presensi Masuk".
- **Then:** Sistem menghitung jarak menggunakan rumus Haversine (< 50 meter), menyimpan rekaman pada tabel `employee_attendances`, status kehadiran tercatat `hadir` (atau `terlambat` jika lewat jam masuk), dan notifikasi sukses muncul di layar.

### AC-EMP-01.2: Presensi Ditolak Karena di Luar Radius
- **Given:** Karyawan mencoba presensi dari rumah atau lokasi berjarak 350 meter dari titik kantor unit.
- **When:** Karyawan mengambil foto dan menekan tombol presensi masuk.
- **Then:** Backend menolak permintaan dengan status HTTP 422, data kehadiran TIDAK disimpan, dan antarmuka menampilkan pesan peringatan merah: *"Anda berada di luar radius lokasi presensi yang diizinkan (Jarak Anda: 350m, Toleransi: 50m)"*.

---

## 2. Fitur Pengisian Jurnal Mengajar KBM (US-ACA-02)

### AC-ACA-02.1: Submit Jurnal KBM dengan Presensi Siswa Lengkap
- **Given:** Guru pengampu telah memilih jadwal mengajar aktif pada hari ini.
- **When:** Guru mengisi materi pokok, memilih Tujuan Pembelajaran (TP) yang dicapai, mencentang kehadiran seluruh siswa (hadir, sakit, izin, alpa), lalu menekan tombol "Simpan Jurnal".
- **Then:** Sistem menyimpan record `TeachingJournal`, menyimpan seluruh entri kehadiran di `JournalAttendance`, memicu event WebSocket `TeachingJournalSubmitted`, dan menampilkan ringkasan jurnal berhasil diterbitkan.

### AC-ACA-02.2: Validasi Kelengkapan Field Wajib
- **Given:** Guru membuka formulir jurnal mengajar.
- **When:** Guru mencoba menekan tombol simpan tanpa mengisi judul materi pelajaran atau tanpa mencatat absensi siswa.
- **Then:** Sistem mencegah submit formulir, menyorot input yang belum terisi dengan pesan validasi error, dan mencegah pengiriman data kosong ke server.

---

## 3. Fitur Sesi Kelas Gabungan / Multi-Classroom (US-ACA-01)

### AC-ACA-01.1: Pembuatan Jadwal Kelas Gabungan
- **Given:** Administrator membuka modal "Tambah Jadwal Pelajaran" untuk mata pelajaran PJOK.
- **When:** Administrator mengaktifkan tombol saklar *"Sesi Kelas Gabungan (Multi-Class)"* dan mencentang rombel `Kelas 4A` dan `Kelas 4B`, lalu memilih guru dan jam pelajaran yang sama.
- **Then:** Sistem menyimpan record jadwal dengan referensi kedua rombel tersebut, dan jadwal tersebut muncul secara sinkron di tabel jadwal kedua kelas tanpa konflik bentrok.

---

## 4. Fitur Penagihan & Pembayaran SPP Siswa (US-FIN-02 & US-FIN-03)

### AC-FIN-01.1: Pembayaran Tunai di Loket Kasir
- **Given:** Wali santri datang ke kantor keuangan untuk membayar tagihan SPP sebesar Rp 350.000 secara tunai.
- **When:** Kasir mencari data siswa, memilih tagihan yang belum lunas, memasukkan nominal Rp 350.000, memilih metode `Cash`, dan mengonfirmasi pembayaran.
- **Then:** Status tagihan `student_bills` berubah menjadi `paid`, record transaksi `bill_payments` terbuat, kas masuk bertambah di buku kas, dan kuitansi pembayaran resmi berformat PDF terbuka siap cetak.

### AC-FIN-01.2: Verifikasi Bukti Transfer Wali Santri
- **Given:** Wali santri telah mentransfer biaya SPP via m-Banking dan mengunggah foto struk transfer ke sistem.
- **When:** Kasir membuka menu verifikasi pembayaran dan menekan tombol "Verifikasi & Sahkan".
- **Then:** Sistem memperbarui status tagihan menjadi `paid`, mengirim notifikasi tanda terima ke wali santri, dan mencatat transaksi ke kas bank terkait.

---

## 5. Fitur Kenaikan Kelas Massal & Rollback (US-ACA-04)

### AC-ACA-04.1: Eksekusi Kenaikan Kelas Siswa
- **Given:** Tahun ajaran baru telah dibuka dan seluruh nilai siswa pada kelas 1A telah lengkap.
- **When:** Kepala Sekolah memilih kelas asal `Kelas 1A`, kelas tujuan `Kelas 2A`, meninjau daftar siswa yang naik kelas, dan menekan tombol "Eksekusi Kenaikan Kelas".
- **Then:** Sistem memindahkan data rombel siswa di dalam transaksi database yang aman, membuat log audit pada tabel `class_promotions`, dan menampilkan status berhasil.

### AC-ACA-04.2: Pembatalan Kenaikan Kelas (Rollback)
- **Given:** Terjadi kesalahan pemilihan kelas tujuan saat eksekusi promosi.
- **When:** Kepala Sekolah memilih riwayat promosi tersebut dan menekan tombol "Rollback Kenaikan Kelas".
- **Then:** Seluruh siswa yang dipromosikan dikembalikan ke kelas asalnya secara otomatis, riwayat promosi ditandai `rolled_back = true`, dan data kelas konsisten seperti semula.

---

## 6. Fitur Pemindaian Barcode Gerbang / Gate Scan (US-ACA-03)

### AC-ACA-03.1: Pemindaian Kartu Siswa di Gerbang
- **Given:** Petugas satpam mengarahkan barcode scanner ke kartu pelajar siswa bernama "Ahmad" pada pukul 06.45 WIB.
- **When:** Barcode berhasil dipindai dan dikirim ke endpoint `/yayasan/student-checkin`.
- **Then:** Sistem mengenali identitas Ahmad, mencatat waktu tiba pukul 06.45 WIB, menetapkan status `tepat_waktu` (karena jam batas masuk 07.00 WIB), membunyikan nada sukses (*chime*), dan menampilkan foto Ahmad di layar monitor gerbang.
