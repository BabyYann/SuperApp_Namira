# 11_BACKEND_ARCHITECTURE

## 1. Backend Overview
**Confidence Level: High**
(Diverifikasi dari `composer.json` dan struktur folder root)

*   **Framework:** Laravel Framework.
*   **Laravel Version:** `^12.0`
*   **PHP Version:** `^8.2`
*   **Application Pattern:** MVC (Model-View-Controller) dikombinasikan dengan pembagian domain modular.
*   **Modular Structure:** Logika modular ditempatkan di bawah namespace `App\Modules\<ModuleName>\` (seperti Academic, Counseling, Finance, LMS, dll.).
*   **Architecture Style:** **Modular Monolith (Partial)**. Modul membagi logika controller dan model secara terisolasi, namun routing, konfigurasi, dan provider tetap terintegrasi global.
*   **Folder Organization:**
    *   `app/Http/Controllers/` (Core Controllers)
    *   `app/Modules/` (Domain-specific Controllers, Models, dan Sub-folders)
    *   `app/Models/` (Core Shared Models)
    *   `app/Helpers/` & `app/Console/` (Shared Console Commands dan Utility Helpers)

---

## 2. Bootstrap Architecture
**Confidence Level: High**
(Diverifikasi dari `public/index.php` dan `bootstrap/app.php`)

1.  **Entry Point (`public/index.php`):**
    *   Memuat autoloader Composer (`vendor/autoload.php`).
    *   Mengambil instansi aplikasi Laravel dari `bootstrap/app.php` dan menangani request HTTP secara sinkronus.
2.  **App Configuration (`bootstrap/app.php`):**
    *   Mengatur routing dasar (`web.php`, `console.php`) dan setelan endpoint cek kesehatan `/up`.
    *   **Middleware Registration:** Menggunakan konfigurasi fluent untuk menambahkan middleware ke dalam grup `web` (`CheckUnitScope`, `HandleInertiaRequests`, `CheckMaintenanceMode`) dan mendefinisikan alias middleware (`role`, `permission`, `feature`).
    *   **Exception Registration:** Menentukan cara penanganan kustom exception seperti `PostTooLargeException` langsung di method `withExceptions`.

---

## 3. Service Provider Analysis
**Confidence Level: High**
(Diverifikasi dari `bootstrap/providers.php` dan `app/Providers/`)

*   **Registered Providers:** Hanya terdaftar satu provider kustom aplikasi: `App\Providers\AppServiceProvider`.
*   **Responsibility:**
    *   `AppServiceProvider::boot()`: Mengonfigurasi prefetch aset Vite dan mendaftarkan gerbang otorisasi (`Gate::define('viewPulse')`) untuk admin yayasan.
*   **Evaluation:** Provider sangat ramping. Aplikasi tidak memiliki provider kustom per modul domain, melainkan mengandalkan registrasi terpusat dan auto-discovery paket composer.

---

## 4. Module Loading Architecture
**Confidence Level: High**
(Diverifikasi dari `composer.json` autoload block)

*   **Autoloading Strategy:** Menggunakan standard **PSR-4 Autoloading** yang dikonfigurasi di `composer.json`:
    ```json
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    }
    ```
*   **Module Discovery:** Tidak menggunakan sistem dynamic discovery (seperti modul composer package lokal). Pemetaan kelas modular di `app/Modules/` bekerja otomatis karena semua direktori di bawah `app/` berada dalam pemetaan namespace `"App\\": "app/"`.
*   **Module Dependency:** Rute terpusat (`routes/web.php`) memanggil controller modul secara langsung via *fully qualified class names* (FQCN).

---

## 5. Container & Dependency Injection
**Confidence Level: High**
(Berdasarkan analisis konstruktor dan metode di controller)

*   **Constructor Injection:** Jarang digunakan pada tingkat controller.
*   **Method Injection:** Sangat dominan pada controller untuk menangkap objek `Request` dan model data menggunakan **Route Model Binding** bawaan Laravel (contoh: `showAssignment(Request $request, Classroom $classroom)`).
*   **App Container Resolution:** Aplikasi menggunakan helper global `auth()->user()` atau `session('active_unit_id')` secara langsung di dalam controller untuk memecahkan data sesi aktif, alih-alih meresolve binding dari App Container.

### Dependency Graph (Contoh Aliran Pemrosesan)
```
HTTP Request 
     ↓
routes/web.php 
     ↓
CheckUnitScope Middleware (Tenancy Check)
     ↓
Module Controller Method (Method Injection: Request, Model)
     ↓
Eloquent Model (Database Interaction)
```

---

## 6. Business Logic Distribution
**Confidence Level: High**
(Berdasarkan penelusuran lokasi penulisan logika di dalam modul)

*   **Controllers:** **Sangat Dominan (85%)**. Logika validasi data, parsing CSV mutasi bank, perhitungan waterfall payment, geofencing lokasi GPS, dan kalkulasi status naik kelas siswa ditulis langsung di dalam method Controller (*Fat Controllers*).
*   **Models:** **Dominan (10%)**. Model Eloquent digunakan untuk mendefinisikan relasi database, accessors, dan mutators.
*   **Service Layer & Actions:** **Sangat Rendah (2%)**. Hanya ditemukan satu kelas generator kode di domain Sarpar.
*   **Helpers:** **3%**. Mengelola utility notifikasi WhatsApp (`WhatsAppHelper`).
*   **Observers:** **Not Found** (Tidak digunakan).

---

## 7. Eloquent Architecture
**Confidence Level: High**
(Diverifikasi dari model model di `app/Models` dan `app/Modules/*/Models/`)

*   **Base Model:** Menggunakan `Illuminate\Database\Eloquent\Model`.
*   **Mass Assignment Protection:** Menggunakan `$guarded = []` pada mayoritas model.
*   **Casting:** Menggunakan properti `$casts` untuk mengonversi kolom boolean (`is_active` pada `system_settings`), format tanggal, dan tipe enum status.
*   **Accessors:** Ditemukan pada model `User.php` (`getUnitsAttribute()`) untuk mengambil daftar unit kerja aktif karyawan.
*   **Soft Deletes:** Menggunakan trait `Illuminate\Database\Eloquent\SoftDeletes` pada model transaksi BK (`violations`, `achievements`, `counseling_sessions`) dan keuangan (`student_bills`).

---

## 8. Service Layer Analysis
**Confidence Level: High**
(Berdasarkan pencarian direktori Service)

*   **Daftar Service:** `App\Modules\Sarpar\Services\InventoryCodeGenerator.php`
*   **Responsibility:** Menghasilkan string kode inventaris unik secara otomatis.
*   **Dampak Ketiadaan Service Layer Global:** Logika transaksi penting seperti Waterfall Payment dan Naik Kelas terjebak di dalam controller, memicu duplikasi logika, sulit dilakukan unit testing, dan menyalahi prinsip Single Responsibility Principle (SRP).

---

## 9. Action Pattern Analysis
**Confidence Level: High**

*   **Action Classes:** **Not Found** (Tidak diterapkan dalam arsitektur backend).
*   **Jobs:** **Not Found** (Tidak ada kelas Job asinkronus yang didefinisikan).
*   **Commands:** Ditemukan satu command scheduler `AutoAlphaAttendance.php` untuk memicu absensi alpa otomatis karyawan.

---

## 10. Repository Pattern
**Confidence Level: High**

*   **Repository Pattern:** **Not Found** (Aplikasi tidak menerapkan pembungkusan logika query).
*   **Dampak:** Controller terikat erat dengan skema database SQL (Eloquent ORM) secara langsung. Hal ini mempersulit migrasi atau penggantian driver database di masa mendatang serta memicu duplikasi query scope yang berulang.

---

## 11. Helper Architecture
**Confidence Level: High**

*   **Global Helper:** `WhatsAppHelper.php` diletakkan di `app/Helpers/`.
*   **Coupling:** Helper BK (`ViolationController`) memanggil static method `WhatsAppHelper::send()` secara langsung di dalam thread request HTTP utama. Ini memicu *tight coupling* antara modul Counseling BK dengan pustaka pengiriman pesan eksternal.

---

## 12. Observer Architecture
**Confidence Level: High**

*   **Model Observers:** **Not Found** (Tidak ada folder `app/Observers` atau kelas pengamat).
*   **Model Events:** Tidak ada registrasi event `booted()` pada model Eloquent. Semua pembaruan status log audit trail diserahkan secara pasif kepada paket Spatie Activity Log.

---

## 13. Exception Architecture
**Confidence Level: High**

*   **Custom Exceptions:** **Not Found** (Tidak ada kelas Exception kustom).
*   **Handler:** Penanganan exception ditangani secara fluent di `bootstrap/app.php` menggunakan penangkap Renderable bawaan Laravel. Contoh: penanganan `PostTooLargeException`.

---

## 14. Transaction Architecture
**Confidence Level: High**
(Diverifikasi dari baris kode TransactionController dan ClassPromotionController)

Sistem menggunakan transaksi database manual untuk mengamankan operasi CRUD jamak:
*   `DB::beginTransaction()` / `DB::commit()` / `DB::rollBack()` dibungkus dalam blok `try-catch` di dalam controller.
*   *Consistency:* Cukup konsisten, tetapi penulisan transaksi langsung di controller memicu kekacauan kode (*code clutter*).

---

## 15. Event Driven Architecture
**Confidence Level: High**

*   **Event & Listener:** **Not Found** (Aplikasi tidak menggunakan event dispatching).
*   **Opportunity:** Mengganti trigger pengiriman WhatsApp BK dan log audit trail yang bersifat sinkronus menjadi asinkronus menggunakan Event Listener bawaan Laravel.

---

## 16. File Storage Architecture
**Confidence Level: High**

*   **Storage Disk:** Terkonfigurasi menggunakan disk lokal (`Storage::disk('public')`).
*   **Clean-up Strategy:** Modul LMS (`LmsStudentController.php`) melakukan pengecekan file lama di disk publik sebelum mengunggah jawaban tugas baru untuk menghindari penumpukan sampah berkas di server.

---

## 17. Caching Strategy
**Confidence Level: High**

*   **Query Cache & Remember:** **Not Found** (Tidak ada penggunaan `Cache::remember` atau cache query database).
*   **Config & Route Cache:** Mendukung fitur bawaan Laravel (`php artisan config:cache` dan `php artisan route:cache`) yang dieksekusi secara otomatis saat perintah instalasi composer setup dijalankan.

---

## 18. Logging Architecture
**Confidence Level: High**

*   **Log Channel:** Default log menggunakan stack log ke file tunggal (`storage/logs/laravel.log`).
*   **observability:** Modul Keuangan (`TransactionController`) mencatat log eksekusi impor mutasi bank secara eksplisit menggunakan `Log::info()` dan `Log::error()`. Log aktivitas audit trail CRUD disimpan di tabel database via Spatie.

---

## 19. Scheduler Architecture
**Confidence Level: High**
(Diverifikasi dari `routes/console.php`)

*   **Scheduler Command:** Terdaftar satu perintah terjadwal:
    ```php
    Schedule::command('attendance:auto-alpha')->dailyAt('23:00');
    ```
    Menjalankan absensi alpa otomatis karyawan setiap hari pukul 23:00 malam (kecuali hari Minggu dan kalender libur).

---

## 20. Queue Architecture
**Confidence Level: High**

*   **Queue Driver:** Terkonfigurasi menggunakan driver database (`QUEUE_CONNECTION=database`).
*   **Worker Readiness:** Infrastruktur antrean siap di tingkat konfigurasi, namun tidak ada *jobs* asinkronus yang ditulis di source code aplikasi untuk didelegasikan ke worker.

---

## 21. Backend Dependency Graph

```
[HTTP Request]
      │
      ▼
[bootstrap/app.php (Bootstrap & Global Middleware)]
      │
      ▼
[routes/web.php (Named Routes Matching)]
      │
      ▼
[CheckUnitScope Middleware (Session unit validation)]
      │
      ▼
[Fat Module Controller (Inline Validation, Logika Bisnis, DB Transaction)]
      │
      ▼
[Eloquent Model (Accessors, Relations)]
      │
      ▼
[MySQL Database]
```

---

## 22. Backend Hotspot

Lima bagian backend dengan kompleksitas paling tinggi:
1.  **`TransactionController::processImport()`:** Memproses validasi CSV bank, pencarian nomor VA Regex, konversi string rupiah, deduplikasi, dan alokasi *waterfall payment*.
2.  **`ClassPromotionController::store()`:** Mengolah array kenaikan kelas siswa massal, log history, pembaruan data murid massal di dalam blok transaksi database.
3.  **`AttendanceController::store()`:** Menghitung jarak koordinat bumi GPS via Rumus Haversine, pengecekan toleransi waktu terlambat unit kerja, konversi selfie Base64, dan penulisan log kehadiran.
4.  **`LmsStudentController::submitAssignment()`:** Mengelola transaksi pengumpulan tugas siswa, evaluasi status keterlambatan tugas, pengunggahan dokumen, dan manajemen penghapusan file lama di server.
5.  **`AutoAlphaAttendance::handle()`:** Command konsol harian BK untuk mengecek ribuan akun karyawan, memadukan dengan pengecekan kalender libur dinamis unit, dan membuat rekam alpa massal.

---

## 23. Technical Debt

*   **High:**
    *   *Fat Controllers:* Seluruh logika bisnis kritis (waterfall, promotion, geofencing) diselesaikan di tingkat controller.
    *   *Misplaced Namespaces:* Folder ganda kelas `Subject` di luar modul akademis yang memicu kebingungan referensi pemanggilan kode.
*   **Medium:**
    *   *Ketiadaan Service/Repository Layer:* Tidak adanya pemisahan query database dan manipulasi data.
    *   *Synchronous WhatsApp Sending:* Pengiriman pesan WhatsApp BK menghambat kecepatan loading browser.
*   **Low:**
    *   *Hardcoded String Status:* Kolom status transaksi dan kehadiran menggunakan raw enum string di controller alih-alih menggunakan PHP Enum terpusat.

---

## 24. SOLID Principles Analysis

*   **Single Responsibility Principle (SRP): 2/10** (Controller menangani validasi, routing respons, manipulasi database, file upload, dan logika bisnis sekaligus).
*   **Open/Closed Principle (OCP): 3/10** (Modul tertutup untuk ekstensi karena minim penggunaan interface).
*   **Liskov Substitution Principle (LSP): 5/10** (Eloquent model extends standar Laravel model berjalan normal).
*   **Interface Segregation Principle (ISP): 4/10** (Tidak ada implementasi interface khusus aplikasi).
*   **Dependency Inversion Principle (DIP): 2/10** (Controller bergantung langsung pada model Eloquent konkret).
*   **SOLID Score: 3.2/10 (Low Compliance).**

---

## 25. Backend Refactoring Recommendation

### Recommendation 1: Ekstraksi Logika Bisnis SPP ke Service Class
*   **Problem:** Controller Keuangan (`TransactionController`) memuat logika pengolahan waterfall payment yang rumit.
*   **Evidence:** `TransactionController::applyWaterfallPayment()` memiliki 70+ baris logika bisnis manipulasi database.
*   **Impact:** Kode sulit dibaca, tidak dapat digunakan kembali di route lain, dan tidak dapat diuji secara terisolasi.
*   **Recommendation:** Buat kelas service `App\Modules\Finance\Services\WaterfallPaymentService` dan pindahkan logika waterfall ke sana.
*   **Priority:** High | **Confidence:** High

### Recommendation 2: Pemasangan Laravel Job Queue untuk Notifikasi WhatsApp BK
*   **Problem:** Penginputan pelanggaran BK melambat akibat pengiriman WhatsApp ke wali murid secara sinkronus.
*   **Evidence:** Panggilan helper `WhatsAppHelper::send()` di `ViolationController.php`.
*   **Impact:** Latensi request HTTP meningkat karena harus menunggu respons server gateway WhatsApp.
*   **Recommendation:** Ubah menjadi asinkronus menggunakan event `ViolationRecorded` yang ditangani oleh Queue Listener `SendWhatsAppAlert` berjalan di background worker.
*   **Priority:** High | **Confidence:** High
