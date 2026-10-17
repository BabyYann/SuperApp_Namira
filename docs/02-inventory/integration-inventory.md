# Integration Inventory (Inventaris Integrasi Pihak Ketiga & Layanan Eksternal)

Dokumen ini memetakan seluruh integrasi pihak ketiga, library eksternal, dan protokol komunikasi yang dimanfaatkan oleh SuperApp Namira.

---

## 1. Matriks Integrasi Sistem

| Layanan / Pustaka | Tipe Integrasi | Tujuan Bisnis di SuperApp Namira | Konfigurasi Terkait | Status Faktual |
|---|---|---|---|---|
| **Laravel Reverb** | WebSocket Server | Siaran notifikasi instan real-time (approval absensi, gate scan kedatangan siswa, jurnal KBM). | `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_HOST`, `REVERB_PORT` | `IMPLEMENTED` |
| **Firebase Cloud Messaging (FCM)** | HTTP v1 REST API | Notifikasi web push PWA ke HP guru & staf saat aplikasi di latar belakang (*background*). | `FIREBASE_PROJECT_ID`, `FIREBASE_CLIENT_EMAIL`, `FIREBASE_PRIVATE_KEY` | `IMPLEMENTED` |
| **WhatsApp Gateway Service** | HTTP REST Outbound | Pengiriman tagihan SPP bulanan, notifikasi pelanggaran siswa ke nomor orang tua. | `WA_GATEWAY_URL`, `WA_GATEWAY_API_KEY` | `UNVERIFIED` |
| **Leaflet & OpenStreetMap** | Frontend SDK (JS) | Peta penentuan titik koordinat lokasi kantor unit dan visualisasi radius absensi geofence. | Tidak membutuhkan API Key (Open-Source Tiles) | `IMPLEMENTED` |
| **Barryvdh DomPDF** | Backend Library | Pembuatan berkas PDF server-side: jadwal pelajaran, kartu SPP, kuitansi, rekap absensi. | `config/dompdf.php` | `IMPLEMENTED` |
| **PhpSpreadsheet** | Backend Library | Impor data massal siswa, guru, inventaris, dan ekspor data buku besar kas ke file Excel (`.xlsx`). | Standar PHP Memory Limit | `IMPLEMENTED` |
| **Chart.js & Vue-Chartjs** | Frontend SDK (JS) | Visualisasi grafik keuangan, persentase kehadiran bulanan, status inventaris, grafik tumbuh kembang daycare. | Reaktif Vue State | `IMPLEMENTED` |
| **Barcode & QR Scanner Engines** | Frontend SDK (JS) | Pemindaian barcode kartu pelajar di pos gerbang, scan QR stiker sarpras, verifikasi penjemputan. | Browser MediaDevices API | `IMPLEMENTED` |
| **Vue-Quill Rich Text** | Frontend Component | Editor teks berformat untuk penulisan materi LMS, isi warta berita humas, dan catatan bimbingan konseling. | Komponen lokal `@vueup/vue-quill` | `IMPLEMENTED` |

---

## 2. Rincian Teknis Integrasi Utama

### 2.1 Laravel Reverb (Real-time Event Broadcasting)
- **Paket:** `laravel/reverb` & `laravel-echo`
- **Kanal Broadcast yang Aktif:**
  1. `student-gate-scan`: Menyiarkan event `StudentScannedAtGate` ketika siswa berhasil memindai barcode di gerbang sekolah (menampilkan status tepat waktu / terlambat ke layar pos satpam dan guru piket).
  2. `attendance-approvals`: Menyiarkan update saat pengajuan izin/sakit pegawai disetujui atasan.
  3. `teaching-journals`: Menyiarkan event `TeachingJournalSubmitted` ketika guru selesai mengisi jurnal mengajar di kelas.
  4. `sarpras-maintenance`: Menyiarkan event `SarprasMaintenanceReported` untuk laporan kerusakan barang inventaris.
  5. `payment-transactions`: Menyiarkan event `PaymentTransactionRecorded` saat kasir menerima pembayaran tagihan.
- **Fallback:** Polling periodik (Inertia reload) jika koneksi WebSocket terputus.

### 2.2 Google Firebase Cloud Messaging (Web Push Notifications)
- **Paket:** `@google-cloud` / Native HTTP v1 API (`app/Services/FcmService.php`)
- **Alur Kerja:**
  1. Perangkat guru/staf melakukan login pada browser/PWA.
  2. Service Worker (`public/build/sw.js`) mendaftarkan diri ke browser dan memperoleh Registration Token dari Google FCM.
  3. Token dikirim ke backend via `POST /push-token` dan disimpan pada tabel `user_device_tokens`.
  4. Ketika ada notifikasi mendesak (misal: approval absensi atau pengumuman yayasan), `NotificationDispatcher` memanggil `FcmService::sendPushNotification()`.

### 2.3 WhatsApp Notification Gateway
- **Komponen:** `app/Helpers/WhatsAppHelper.php` & model `WhatsAppQueue.php`
- **Tujuan Bisnis:** Mengirim pesan otomatis langsung ke nomor handphone orang tua/wali siswa.
- **Kasus Penggunaan:**
  - Pemberitahuan tagihan SPP baru dan konfirmasi pembayaran berhasil.
  - Peringatan insiden pelanggaran tata tertib siswa oleh tim BK.
  - Laporan harian stimulasi balita daycare kepada wali murid.
- **Status Faktual:** Kode helper dan antrean database terpasang lengkap di kode sumber (`IMPLEMENTED`), namun dependensi koneksi gateway fisik pihak ketiga berstatus `UNVERIFIED` di lingkungan testing lokal.

### 2.4 Leaflet & Geofencing Engine
- **Komponen:** `resources/js/Pages/Employee/Attendance/Index.vue` & `AttendanceController.php`
- **Logika Geofence:** Menggunakan formula trigonometri **Haversine** pada backend untuk menghitung jarak absolut (dalam meter) antara titik koordinat perangkat user (`lat`, `lng` dari GPS HTML5) dengan titik koordinat unit kerja (`AttendanceLocation`):
  $$\text{distance} = 2R \cdot \arcsin\left(\sqrt{\sin^2\left(\frac{\Delta\phi}{2}\right) + \cos(\phi_1)\cos(\phi_2)\sin^2\left(\frac{\Delta\lambda}{2}\right)}\right)$$
- **Ambang Batas Toleransi:** Ditentukan per lokasi (standar: 50 hingga 100 meter). Jika jarak melebihi batas, request absensi ditolak secara tegas oleh backend dengan pesan status *Di Luar Radius*.
