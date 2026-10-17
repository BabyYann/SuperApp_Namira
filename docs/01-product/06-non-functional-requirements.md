# Non-Functional Requirements (Kebutuhan Non-Fungsional Sistem)

Dokumen ini mendefinisikan standar mutu, keandalan, kinerja, keamanan, dan kegunaan sistem SuperApp Namira.

---

## 1. Kinerja & Waktu Respons (Performance & Latency)

- **NFR-PERF-01 (Waktu Muat Navigasi SPA):** Waktu respons navigasi halaman melalui Inertia.js (P95) HARUS kurang dari **500 milidetik** pada koneksi jaringan standar 4G (10 Mbps).
- **NFR-PERF-02 (Pemrosesan Laporan & Export PDF):** Waktu generasi dokumen PDF (jadwal, kuitansi, rekap absensi) oleh DomPDF HARUS selesai dalam waktu kurang dari **3,0 detik** untuk dokumen hingga 50 halaman.
- **NFR-PERF-03 (Penyajian Feed Real-Time):** Latensi siaran event WebSocket (Reverb) dari server ke client browser pos satpam/guru piket (P99) HARUS kurang dari **1,5 detik** setelah pemindaian barcode gerbang berhasil.
- **NFR-PERF-04 (Optimasi Ukuran Bundle Frontend):** Ukuran chunk aset JavaScript utama yang dikompilasi oleh Vite tidak boleh melebihi **500 kB** per lazy-loaded route chunk (dengan kompresi Gzip/Brotli).

---

## 2. Keamanan & Kepatuhan Data (Security & Data Protection)

- **NFR-SEC-01 (Enkripsi Data Transit):** Seluruh lalu lintas data antara browser/klien mobile dan server HARUS dienkripsi menggunakan protokol **TLS/HTTPS** versi 1.3 (atau minimal 1.2).
- **NFR-SEC-02 (Penyimpanan Kredensial):** Kata sandi seluruh pengguna HARUS di-hash menggunakan algoritma **Bcrypt** dengan cost factor minimal 12. Tidak ada password mentah yang disimpan di database atau log.
- **NFR-SEC-03 (Proteksi Cross-Site Request Forgery - CSRF):** Seluruh rute mutasi web (`POST`, `PUT`, `PATCH`, `DELETE`) HARUS divalidasi menggunakan token CSRF Laravel yang valid.
- **NFR-SEC-04 (Sanitasi Masukan & Proteksi XSS/SQL Injection):** Seluruh input pengguna pada form dan editor rich-text (Vue-Quill) HARUS disanitasi dari tag skrip berbahaya sebelum disimpan, dan seluruh query database HARUS menggunakan parameter binding Eloquent/PDO.
- **NFR-SEC-05 (Isolasi Multi-Tenancy Ketat):** Akses data antar-unit sekolah HARUS dicegah di lapisan backend melalui query scope atau Spatie Teams `team_id`. Kegagalan menyembunyikan tombol di antarmuka frontend TIDAK BOLEH menjadi celah bocornya data unit lain (*Defense in Depth*).
- **NFR-SEC-06 (Pembatasan Laju Akses - Rate Limiting):** Endpoint login (`/login` dan `/api/login`) HARUS dibatasi maksimal **5 percobaan per menit** per alamat IP untuk mencegah serangan *brute force*.

---

## 3. Ketersediaan & Keandalan (Availability & Reliability)

- **NFR-REL-01 (Target Uptime Jam Operasional):** Sistem HARUS memiliki tingkat ketersediaan (*availability SLA*) minimal **99,5%** selama jam operasional aktif sekolah (Senin s.d. Jumat pukul 06.30 - 16.30 WIB dan Sabtu 07.00 - 13.00 WIB).
- **NFR-REL-02 (Integritas Transaksi Atomik):** Seluruh mutasi multi-tabel (seperti proses kenaikan kelas massal, pembayaran cicilan SPP, dan peminjaman inventaris) HARUS dieksekusi di dalam blok **Database Transaction** (`DB::transaction`) untuk mencegah status data inkonsisten (*partial state / data corruption*).
- **NFR-REL-03 (Graceful Degradation Notifikasi):** Jika layanan gateway eksternal (FCM atau WhatsApp) mengalami *downtime*, sistem inti HARUS tetap berjalan normal tanpa melempar fatal error 500 ke pengguna. Pesan yang gagal dikirim HARUS masuk ke dalam antrean database untuk dicoba ulang (*retry mechanism*).

---

## 4. Kegunaan & Aksesibilitas Mobile (Usability & PWA Mobile-First)

- **NFR-USE-01 (Desain Responsif Multi-Device):** Seluruh antarmuka utama (terutama presensi, jadwal mengajar, dan jurnal KBM) HARUS dioptimalkan untuk tampilan smartphone (viewport 360px, 390px, 430px) tanpa menimbulkan scroll horizontal yang tidak disengaja.
- **NFR-USE-02 (Ukuran Target Sentuh - Tap Targets):** Setiap tombol interaktif dan kontrol navigasi pada tampilan mobile PWA HARUS memiliki area sentuh minimal **44 × 44 piksel** untuk mencegah salah klik saat pengguna berjalan.
- **NFR-USE-03 (Konsistensi Identitas Visual):** Antarmuka pengguna HARUS konsisten menerapkan palet warna Namira School Foundation: Namira Teal (`#0B7068`), Navy Slate (`#0f172a`), dan surface abu-abu terang dengan radius rounded yang seragam.

---

## 5. Skalabilitas & Operasional (Scalability & Maintainability)

- **NFR-SCA-01 (Kapasitas Pengguna Bersamaan):** Arsitektur backend dan database HARUS mampu menangani minimal **500 pengguna aktif bersamaan** (*concurrent users*) saat jam presensi pagi (06.45 - 07.30 WIB) dengan utilisasi CPU server < 70%.
- **NFR-SCA-02 (Pemisahan Beban Tugas Asinkron):** Pengiriman email, notifikasi push FCM massal, dan antrean WhatsApp HARUS diproses secara asinkron melalui antrean database/Redis (`queue:work`) agar tidak membebani siklus request-response HTTP pengguna.
