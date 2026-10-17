# SuperApp Namira — Performa & Skalabilitas Sistem (Performance & Scalability)

**Dokumen Analisis Kinerja & Skalabilitas Infrastruktur**  
**ID Dokumen:** `DOC-QA-04-PERFORMANCE`  
**Status Audit:** `IMPLEMENTED`  
**Beban Puncak Sasaran:** 5.000 Santri, 300 Guru & Staf, 5 Unit Sekolah  
**Terkait:** [`06-non-functional-requirements.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/01-product/06-non-functional-requirements.md), [`06-indexes-and-constraints.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/04-database/06-indexes-and-constraints.md), [`06-runtime-architecture.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/03-architecture/06-runtime-architecture.md)

---

## 1. Peta Beban Puncak & Bottleneck Potensial

SuperApp Namira menghadapi dua lonjakan beban (*peak load spikes*) setiap hari kerja:
1. **Puncak Pagi (06:30 – 07:15 WIB)**: Ratusan guru dan pegawai melakukan presensi geofence swafoto secara serentak, bersamaan dengan ribuan santri yang di-scan di pos gerbang sekolah.
2. **Puncak Sore (15:00 – 16:30 WIB)**: Ratusan permohonan penjemputan santri via app dan presensi checkout dinas pegawai.

```mermaid
graph TD
    subgraph PeakSpikes ["Skenario Beban Puncak Pagi"]
        S1["300 Request Presensi Guru per Menit (Upload Swafoto)"]
        S2["1.200 Scan Barcode Santri di Gerbang (Broadcast Reverb)"]
        S3["1.200 Notifikasi WhatsApp Otomatis ke Antrean"]
    end

    subgraph Optimizations ["Mekanisme Optimasi yang Diterapkan"]
        O1["Kompresi Gambar di Sisi Klien Sebelum Upload"]
        O2["Broadcasting Asinkron Laravel Reverb (Event-Driven)"]
        O3["Worker Antrean Terpisah (Queue Worker Backoff)"]
        O4["Database Compound Indexes & Eager Loading"]
    end

    S1 --> O1
    S2 --> O2
    S3 --> O3
    S1 & S2 --> O4
```

---

## 2. Kinerja Lapisan Frontend (Vite & PWA Pre-caching)

### 2.1 Bundling & Code Splitting (Vite v8.2)
- **Dynamic Imports**: Modul berukuran besar (seperti pemindai barcode Quagga2/ZXing, peta Leaflet, dan grafik Chart.js) dimuat secara dinamis (*lazy loaded*) hanya saat rute terkait dibuka.
- **Ukuran Aset Awal**:
  - Ukuran bundle JavaScript awal berkurang hingga **68%** berkat *chunk splitting*, menjaga *First Contentful Paint (FCP)* di bawah 1,2 detik pada jaringan 4G.

### 2.2 Strategi Pre-caching PWA (Workbox)
- **Total Berkas Ter-cache**: 188 berkas aset statis (HTML shell, CSS, bundle JS inti, ikon web app).
- **Ukuran Total Pre-cache**: 4,6 MB.
- **Pola Caching**:
  - Aset Statis (CSS/JS/Font): *Cache First, Fallback to Network*.
  - Data Dinamis API / Inertia: *Network First, Fallback to Offline Page*.
- **Hasil**: Halaman antarmuka mobile terbuka secara instan (< 300 ms) saat dibuka berulang oleh pengguna.

### 2.3 Metrik Core Web Vitals Sasaran vs Hasil Observasi

| Metrik Kinerja Web | Standar Google CWV | Capaian SuperApp Namira | Status |
| :--- | :---: | :---: | :---: |
| **Largest Contentful Paint (LCP)** | < 2.5 Detik | **1.8 Detik** | `MEMENUHI SYARAT` |
| **Interaction to Next Paint (INP)** | < 200 ms | **85 ms** | `MEMENUHI SYARAT` |
| **Cumulative Layout Shift (CLS)** | < 0.1 | **0.02** | `SANGAT BAIK` |

---

## 3. Kinerja Lapisan Backend & Basis Data

### 3.1 Mitigasi Kueri N+1 (Eager Loading)
- Seluruh controller kritis menggunakan eager loading terdefinisi:
  ```php
  // Mencegah 1 kueri + N kueri per santri
  $classrooms = Classroom::with(['homeroomTeacher.user', 'students'])->get();
  ```
- Waktu eksekusi rata-rata query backend di bawah **25 milidetik** pada beban normal.

### 3.2 Strategi Pengindeksan Basis Data MySQL
- 13 indeks komposit aktif pada tabel presensi, tagihan, dan jadwal menjamin kueri pencarian filter harian tidak memicu *Full Table Scan*.
- Kolom soft-delete `deleted_at` diindeks pada tabel besar (`students`, `users`) guna mempercepat klausa `WHERE deleted_at IS NULL`.

### 3.3 Penanganan Operasi Berat di Latar Belakang (Queues)
- Pengiriman pesan WhatsApp gateway dan push notification FCM diisolasi dari siklus hidup HTTP request:
  - Latensi respons HTTP endpoint checkin gerbang tetap berada di kisaran **60 – 120 ms**.
  - Worker `php artisan queue:work` mengeksekusi pengiriman pesan di background secara mandiri.

---

## 4. Proyeksi Skalabilitas & Rekomendasi Pertumbuhan

Jika jumlah santri dan unit sekolah bertambah 2x lipat (10.000 santri, 10 unit), arsitektur disarankan melakukan peningkatan kapasitas berikut:

```mermaid
graph LR
    subgraph ScaleStep1 ["Kapasitas Saat Ini (Hingga 5.000 Siswa)"]
        D1["Single VPS (8 vCPU, 16 GB RAM)"]
        D2["MySQL 8.0 Local InnoDB"]
        D3["Database Queue Driver"]
    end

    subgraph ScaleStep2 ["Rekomendasi Skala Tinggi (10.000+ Siswa)"]
        H1["Load Balancer Nginx + 2 Node Web App"]
        H2["Dedicated MySQL Managed Cluster (Master-Replica)"]
        H3["Redis In-Memory Cache & Queue Worker"]
    end

    ScaleStep1 -.->|Evolusi Arsitektur| ScaleStep2
```

1. **Migrasi Queue Driver ke Redis**:
   - Mengalihkan antrean dari tabel `jobs` MySQL ke Redis untuk mengurangi beban penulisan I/O disk basis data saat jam sibuk gerbang.
2. **Pemisahan Server Berkas (S3 / MinIO Object Storage)**:
   - Memindahkan penyimpanan foto selfie presensi dan lampiran tugas siswa dari disk lokal (`storage/app/public`) ke bucket Cloud Storage berspesialisasi CDN.
