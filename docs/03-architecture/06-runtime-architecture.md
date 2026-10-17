# Runtime Architecture (Arsitektur Eksekusi & Siklus Hidup Permintaan)

Dokumen ini mendokumentasikan arsitektur runtime (*execution lifecycle*), siklus hidup permintaan HTTP, pengelolaan sesi, strategi caching, dan komunikasi WebSocket real-time SuperApp Namira.

---

## 1. Siklus Hidup Permintaan HTTP (Request-Response Lifecycle)

```mermaid
sequenceDiagram
    autonumber
    actor Client as Perangkat Klien (Browser / PWA)
    participant Web as Web Server (Nginx / Apache cPanel)
    participant PHP as PHP-FPM Engine (PHP 8.2+)
    participant Boot as Laravel 12 Bootstrap (bootstrap/app.php)
    participant Pipe as Global Middleware Pipeline
    participant Route as Modular Router (routes/*.php)
    participant Ctrl as Domain Controller
    participant DB as MySQL 8.x Database
    participant Inertia as Inertia Response Engine

    Client->>Web: HTTPS Request (URL, Headers, Cookies, CSRF)
    Web->>PHP: Meneruskan ke public/index.php
    PHP->>Boot: Inisialisasi Service Container & Providers
    Boot->>Pipe: Menjalankan CheckUnitScope, Auth, Inertia
    Pipe->>Route: Pencocokan Rute & Otorisasi Spatie Role
    Route->>Ctrl: Memanggil Method Controller Terkait
    Ctrl->>DB: Eksekusi Query Eloquent (PDO)
    DB-->>Ctrl: Mengembalikan Data Model
    Ctrl->>Inertia: Inertia::render('Page', $props)
    Inertia-->>PHP: Payload JSON X-Inertia / HTML Pertama
    PHP-->>Web: Output Stream Response
    Web-->>Client: HTTPS 200 OK Response Rendering
```

---

## 2. Pengelolaan Sesi & Kuki (Session & Cookie Management)

Sistem menggunakan driver **Database Session** untuk ketahanan tinggi pada lingkungan multi-worker:

1. **Kuki Utama yang Diterbitkan:**
   - `superapp_namira_session`: Token pengenal sesi terenkripsi yang dicocokkan dengan tabel `sessions` di basis data. Memiliki atribut `HttpOnly`, `SameSite=Lax`, dan `Secure` (saat aktif di HTTPS).
   - `XSRF-TOKEN`: Token keamanan anti-CSRF yang dibaca oleh Axios/Inertia pada sisi frontend untuk disematkan dalam header setiap mutasi `X-XSRF-TOKEN`.
   - `active_unit_id`: Nilai unit aktif pilihan pengguna yang digunakan oleh middleware `CheckUnitScope` untuk mempertahankan konteks unit saat berpindah halaman.
   - `remember_web_*`: Token sesi persisten jika pengguna memilih opsi "Ingat Saya" saat login.
2. **Masa Berlaku Sesi:** Standar 120 menit (2 jam) waktu tidak aktif (*inactivity timeout*), dengan pembersihan berkala melalui scheduler `session:gc`.

---

## 3. Strategi Caching Sistem (Caching Strategy)

| Lapisan Cache | Driver / Mekanisme | Kunci / Target Cache | Strategi Invalidasi |
|---|---|---|---|
| **Spatie Permission Cache** | Database / File Cache | `spatie.permission.cache` | Di-reset otomatis saat role/permission diubah via `app(PermissionRegistrar::class)->forgetCachedPermissions()`. |
| **Config & Route Cache** | Production Compiled File | `bootstrap/cache/config.php` & `routes-v7.php` | Diperbarui saat rilis deployment melalui `php artisan optimize` atau `config:cache`. |
| **PWA Workbox Precache** | Browser Cache Storage | 188 file aset statis (`sw.js`) | Diperbarui saat versi hash nama file chunk Vite berubah pada build baru. |
| **Application Query Cache** | Laravel Cache Facade | Master data unit, tahun akademik, sistem libur | Invalidasi berbasis event saat data master diubah oleh admin. |

---

## 4. Siklus Hidup Koneksi WebSocket (Laravel Reverb)

Komunikasi duplex real-time ditangani oleh server **Laravel Reverb**:

```mermaid
flowchart TD
    ClientConnect[Klien Membuka Halaman Authenticated] --> Handshake[WSS Handshake ke Reverb Port 8080/443]
    Handshake --> AuthCheck{Autentikasi Channel}
    AuthCheck -->|Public Channel| SubPublic[Subscribe student-gate-scan, attendance-approvals]
    AuthCheck -->|Private Channel App.Models.User.id| TokenCheck{Cek Sanctum / Session Auth}
    TokenCheck -->|Sah| SubPrivate[Subscribe Private User Channel]
    TokenCheck -->|Batal| Reject[Tolak Koneksi WSS 403]
    SubPublic & SubPrivate --> Heartbeat[Ping / Pong Heartbeat Tiap 30 Detik]
    Heartbeat --> Disconnect{Koneksi Terputus?}
    Disconnect -->|Ya| Reconnect[Exponential Backoff Reconnect 2s, 4s, 8s...]
```

1. **Inisialisasi:** Komponen `AuthenticatedLayout.vue` menginisialisasi `window.Echo` menggunakan konfigurasi Reverb (`pusher-js` compatible client).
2. **Kanal Publik:** Digunakan untuk notifikasi massal seluruh admin/guru yang bertugas (contoh: scanner gerbang kedatangan siswa).
3. **Kanal Privat:** Memerlukan token sesi dan verifikasi pada `routes/channels.php` untuk notifikasi khusus yang bersifat rahasia per user ID.
4. **Heartbeat:** Reverb mengirimkan paket ping setiap 30 detik untuk mendeteksi perangkat offline dan melepaskan socket yang menggantung (*stale sockets*).
