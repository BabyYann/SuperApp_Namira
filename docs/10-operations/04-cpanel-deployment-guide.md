# SuperApp Namira — Panduan Deployment cPanel & Shared Hosting (cPanel Guide)

**Dokumen Standar Operasional Prosedur (SOP) Deployment Alternatif**  
**ID Dokumen:** `DOC-OPS-04-CPANEL`  
**Status Audit:** `IMPLEMENTED`  
**Target Lingkungan:** cPanel Web Hosting (LiteSpeed / Apache)  
**Terkait:** [`01-prerequisites-and-setup.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/10-operations/01-prerequisites-and-setup.md), [`03-deployment-guide.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/10-operations/03-deployment-guide.md)

---

## 1. Tantangan & Arsitektur Direktori pada cPanel

Pada lingkungan shared hosting cPanel, pengguna tidak memiliki akses *root*, dan web server secara default mengekspos folder `public_html`. Untuk melindungi kode sumber Laravel dan berkas `.env` dari akses langsung publik, diterapkan struktur pemisahan direktori:

```
/home/namira/
├── superapp_core/               <-- KODE SUMBER UTAMA (Di luar akses publik web)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   └── .env                    <-- TERLINDUNGI DARI BROWSER
└── public_html/                <-- WEB ROOT PUBLIK (Hanya berkas dari folder 'public')
    ├── build/                  <-- Aset kompilasi Vite (CSS & JS)
    ├── storage/                <-- Symlink ke /home/namira/superapp_core/storage/app/public
    ├── .htaccess               <-- Aturan Rewrite Apache/LiteSpeed
    ├── favicon.ico
    └── index.php               <-- Entrypoint yang telah disesuaikan jalurnya
```

---

## 2. Langkah-demi-Langkah Deployment cPanel

### Langkah 1: Persiapan Aset Frontend Lokal (Build First)
Karena server cPanel shared hosting sering kali tidak memiliki Node.js v20 atau memori RAM terbatas untuk kompilasi:
1. Jalankan kompilasi aset pada laptop/komputer pengembang:
   ```bash
   npm ci
   npm run build
   ```
2. Pastikan direktori `public/build` terisi berkas `manifest.json`, CSS, dan JS hasil kompilasi.

### Langkah 2: Kompresi & Unggah Berkas ke File Manager cPanel
1. Kompres seluruh proyek ke dalam berkas `superapp.zip` (kecuali folder `node_modules` dan `.git`).
2. Masuk ke **cPanel > File Manager**.
3. Di luar `public_html`, buat direktori baru bernama `superapp_core`.
4. Unggah `superapp.zip` ke dalam `superapp_core` dan lakukan **Extract**.

### Langkah 3: Pindahkan Isi Folder Public ke `public_html`
1. Buka folder `superapp_core/public`.
2. Pindahkan seluruh isi berkas dan subfolder (termasuk `.htaccess`, `build/`, `favicon.ico`, `index.php`) langsung ke dalam folder `public_html`.

### Langkah 4: Sesuaikan Jalur pada `public_html/index.php`
Buka berkas `public_html/index.php` via Code Editor cPanel, lalu ubah baris pemanggilan autoload dan bootstrap:

```php
// SEBELUMNYA:
// require __DIR__.'/../vendor/autoload.php';
// $app = require_once __DIR__.'/../bootstrap/app.php';

// SESUDAH DISESUAIKAN UNTUK CPANEL:
require __DIR__.'/../superapp_core/vendor/autoload.php';
$app = require_once __DIR__.'/../superapp_core/bootstrap/app.php';
```

### Langkah 5: Buat Basis Data & Konfigurasi `.env`
1. Buka **cPanel > MySQL Database Wizard**:
   - Buat database: `namira_superapp`.
   - Buat user database: `namira_dbuser`.
   - Hubungkan user ke database dengan mencentang **ALL PRIVILEGES**.
2. Masuk ke `superapp_core`, edit berkas `.env`:
   ```ini
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://superapp.namira.sch.id

   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_DATABASE=namira_superapp
   DB_USERNAME=namira_dbuser
   DB_PASSWORD=[PasswordMySQLcPanel]
   ```
3. Impor skema basis data awal melalui **phpMyAdmin** atau via Terminal cPanel:
   ```bash
   php artisan migrate --force --seed
   ```

### Langkah 6: Pembuatan Symlink Media Storage
Jika cPanel menyediakan akses Terminal (SSH), jalankan:
```bash
ln -s /home/namira/superapp_core/storage/app/public /home/namira/public_html/storage
```
*Jika Terminal tidak tersedia*, buat skrip PHP sementara di `public_html/symlink_create.php`:
```php
<?php
$target = '/home/namira/superapp_core/storage/app/public';
$shortcut = '/home/namira/public_html/storage';
symlink($target, $shortcut);
echo "Symlink Berhasil Dibuat!";
```
Akses `https://namira.sch.id/symlink_create.php` sekali melalui browser, lalu segera **hapus** file skrip tersebut.

---

## 3. Konfigurasi Cron Job & Penjadwal di cPanel

Buka menu **cPanel > Cron Jobs**, pilih frekuensi *Once Per Minute* (`* * * * *`), dan masukkan perintah:

```bash
/usr/local/bin/php /home/namira/superapp_core/artisan schedule:run >> /dev/null 2>&1
```
*(Ganti `/usr/local/bin/php` sesuai dengan path PHP 8.2 yang terpasang pada cPanel Anda)*.

---

## 4. Keterbatasan & Solusi Fitur Khusus di Shared Hosting

| Fitur SuperApp | Keterbatasan di cPanel Shared | Rekomendasi Solusi Alternatif |
| :--- | :--- | :--- |
| **Laravel Reverb (WebSocket)** | Shared hosting tidak mengizinkan port daemon persisten (8080). | Gunakan layanan terkelola cloud Pusher / Soketi, atau alihkan fitur scanner gerbang ke polling interval pendek (3 detik). |
| **Worker Antrean Persisten** | `supervisor` tidak tersedia untuk menjaga proses `queue:work` hidup. | Terapkan cron job antrean setiap 5 menit: `php artisan queue:work --stop-when-empty`, atau alihkan `QUEUE_CONNECTION=sync` untuk volume trafik rendah. |
