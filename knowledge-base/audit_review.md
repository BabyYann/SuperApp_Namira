# 🔍 Audit Review — SuperApp Namira v2

> Hasil review mandiri terhadap keseluruhan arsitektur, kode, dan fitur.  
> Tanggal: 12 Mei 2026

---

## 🔴 TEMUAN KRITIS (Bug / Keamanan)

### 1. Feature Toggle Tidak Di-Enforce — Hanya Kosmetik!
**Status:** ⛔ **BERBAHAYA**

Saat ini, toggle fitur di Pusat Kontrol (misal: matikan Modul Sarpar) **hanya menyimpan nilai ke database**. Namun:
- **Sidebar tidak menyembunyikan menu** modul yang dimatikan — user tetap melihat & bisa klik.
- **Backend tidak memblokir request** ke route modul yang dimatikan — user bisa akses langsung via URL.

**Yang seharusnya terjadi:**
1. Sidebar harus baca `$page.props.app_settings.feature_sarpar` dan menyembunyikan grup menu jika `'0'`.
2. Middleware backend harus cek feature flag sebelum mengizinkan akses ke route modul tersebut.

**File yang perlu diubah:**
- `Sidebar.vue` → tambah `v-if` berdasarkan `app_settings.feature_*` di setiap grup menu
- Buat middleware baru `CheckFeatureEnabled` atau tambahkan logika di route group

---

### 2. `updateGlobal()` Tidak Ada Validasi Input
**Status:** ⚠️ **Rentan**

```php
// SettingController.php, line 59-63
$settingsData = $request->except(['app_logo', 'app_favicon']);
foreach ($settingsData as $key => $value) {
    SystemSetting::setSetting($key, $value);  // SEMUA key diterima!
}
```

Method ini menerima **semua key yang dikirim dari frontend** tanpa whitelist. Artinya:
- User bisa mengirim key sembarang (misal `maintenance_mode=1`) lewat tab Identitas.
- Tidak ada validasi format file (bisa upload `.php` sebagai "logo").

**Solusi:** Tambahkan whitelist key yang diizinkan dan validasi file upload.

---

### 3. Maintenance Mode — Respons Inertia Tidak Benar
**Status:** ⚠️ **Pecah di frontend**

```php
// CheckMaintenanceMode.php, line 33-37
if ($request->header('X-Inertia')) {
    return response()->json([...], 503);
}
```

Inertia.js tidak menangani respons JSON 503 dengan baik — ini akan menyebabkan error di console browser, bukan menampilkan halaman maintenance. Seharusnya mengembalikan respons Inertia yang valid atau melakukan redirect.

**Solusi:** Gunakan `Inertia::render('Maintenance', [...])` atau redirect ke halaman maintenance khusus.

---

## 🟡 TEMUAN PENTING (Miss / Inkonsistensi)

### 4. Flash Message (`success`) Tidak Ditampilkan di UI
**Status:** ⚠️ **Silent fail**

Banyak controller mengirim `->with('success', '...')`, tapi di `HandleInertiaRequests.php` tidak ada sharing `flash` message ke props. Artinya pesan sukses dari backend **tidak pernah sampai ke frontend** (kecuali yang sudah di-handle manual lewat `onSuccess` callback di Vue).

Ini bukan masalah fatal karena SweetAlert sudah menangani di sisi frontend, tapi menunjukkan inkonsistensi arsitektur.

**Solusi:** Tambahkan flash message ke Inertia shared props:
```php
'flash' => [
    'success' => fn () => $request->session()->get('success'),
    'error'   => fn () => $request->session()->get('error'),
],
```

---

### 5. Tidak Ada Activity Log / Audit Trail
**Status:** ⚠️ **Kritis untuk yayasan**

Saat ini **tidak ada catatan** siapa yang:
- Mengubah role pengguna
- Mematikan/menghidupkan modul
- Mengaktifkan maintenance mode
- Mengubah tagihan keuangan

Untuk aplikasi manajemen yayasan, ini sangat penting untuk akuntabilitas.

**Solusi:** Pasang `spatie/laravel-activitylog` — sudah sangat mature dan gratis.

---

### 6. Maintenance Page Masih Pakai Emoji
**Status:** 🟡 **Minor**

Di `CheckMaintenanceMode.php` line 74:
```html
<div class="icon">🔧</div>
```

Kita baru saja mengganti semua emoji di Settings UI, tapi halaman maintenance fallback masih menggunakan emoji. Harus diganti dengan SVG atau Heroicon inline.

---

### 7. Logo Lama Tidak Dihapus Saat Diganti
**Status:** 🟡 **Boros storage**

Di `SettingController::updateGlobal()`, saat user upload logo baru:
```php
$path = $request->file('app_logo')->store('settings', 'public');
SystemSetting::setSetting('app_logo', $path, 'image');
```

File logo lama **tidak dihapus** dari storage. Seiring waktu, folder `storage/settings/` akan penuh dengan file yang tidak terpakai.

**Solusi:** Hapus file lama sebelum menyimpan yang baru:
```php
$oldLogo = SystemSetting::getSetting('app_logo');
if ($oldLogo) Storage::disk('public')->delete($oldLogo);
```

---

## 🔵 FITUR YANG SEBAIKNYA DITAMBAHKAN

### 8. Enforce Feature Flags di Sidebar + Middleware
**Prioritas:** 🔴 **Tinggi** — karena tanpa ini, toggle di Pusat Kontrol cuma tombol pajangan.

**A. Di Sidebar (Frontend):**
```js
// Sebelum menampilkan grup menu, cek feature flag:
const settings = usePage().props.app_settings;
// Di computed filteredMenuGroups, tambahkan:
if (group.key === 'finance' && settings?.feature_finance === '0') return false;
if (group.key === 'sarpar' && settings?.feature_sarpar === '0') return false;
// ... dst
```

**B. Di Backend (Middleware):**
Buat `CheckFeatureEnabled` middleware:
```php
Route::prefix('finance')->middleware(['feature:feature_finance'])->group(...)
Route::prefix('sarpar')->middleware(['feature:feature_sarpar'])->group(...)
```

---

### 9. Laravel Pulse atau Telescope (Monitoring)
**Prioritas:** 🟡 **Sedang**

Aplikasi ini sudah cukup kompleks (7 modul, banyak role). Tanpa monitoring tool, sulit mendeteksi:
- Query lambat
- Error rate
- Memory usage

**Rekomendasi:** `Laravel Pulse` (ringan, built-in dashboard) cocok untuk production. `Telescope` untuk development.

---

### 10. Backup Database Otomatis
**Prioritas:** 🟡 **Sedang**

Belum ada mekanisme backup. Untuk data yayasan (keuangan, siswa, presensi), kehilangan data bisa fatal.

**Rekomendasi:** `spatie/laravel-backup` — bisa dijadwalkan via cron, simpan ke local atau cloud.

---

### 11. Notifikasi (WhatsApp / Email)
**Prioritas:** 🟢 **Rendah (masa depan)**

Belum ada sistem notifikasi ke orang tua siswa. Fitur yang bermanfaat:
- Notifikasi tagihan jatuh tempo
- Notifikasi pelanggaran anak
- Notifikasi presensi (anak tidak hadir)

**Rekomendasi:** Gunakan Fonnte / WABLAS API untuk WhatsApp. Laravel Notification channel sudah mendukung custom driver.

---

### 12. Rate Limiting pada Toggle Endpoints
**Prioritas:** 🟡 **Sedang**

Endpoint `toggle-role` dan `toggle-feature` tidak memiliki rate limiting. Jika ada bug di frontend (infinite loop click), server bisa kewalahan.

**Solusi:** Tambahkan `throttle:10,1` di route group settings.

---

## 📊 RINGKASAN PRIORITAS

| # | Temuan | Tipe | Prioritas | Estimasi |
|---|---|---|---|---|
| 1 | Feature toggle tidak enforce | 🔴 Bug | **KRITIKAL** | 2-3 jam |
| 2 | updateGlobal tanpa validasi | 🔴 Security | **KRITIKAL** | 30 menit |
| 3 | Maintenance Inertia response | 🔴 Bug | **TINGGI** | 1 jam |
| 4 | Flash message tidak di-share | 🟡 Miss | Sedang | 15 menit |
| 5 | Tidak ada activity log | 🟡 Miss | Sedang | 1-2 jam |
| 6 | Maintenance page emoji | 🟡 Minor | Rendah | 10 menit |
| 7 | Logo lama tidak dihapus | 🟡 Minor | Rendah | 15 menit |
| 8 | Feature flag enforcement | 🔵 Fitur | **KRITIKAL** | 2-3 jam |
| 9 | Monitoring (Pulse/Telescope) | 🔵 Fitur | Sedang | 1 jam |
| 10 | Backup database | 🔵 Fitur | Sedang | 1 jam |
| 11 | Notifikasi WA/Email | 🔵 Fitur | Rendah | Besar |
| 12 | Rate limiting | 🔵 Fitur | Sedang | 15 menit |

---

> **Rekomendasi urutan pengerjaan:**  
> 1 → 8 → 2 → 3 → 7 → 6 → 4 → 5 → 12 → 9 → 10 → 11
