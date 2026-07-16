# Audit Review — SuperApp Namira v2

> Hasil review mandiri terhadap keseluruhan arsitektur, kode, dan fitur.  
> Tanggal: 12 Mei 2026
> **Terakhir diupdate:** 13 Juli 2026

---

## TEMUAN KRITIS (Bug / Keamanan)

### 1. ~~Feature Toggle Tidak Di-Enforce — Hanya Kosmetik!~~ ✅ SUDAH DIPERBAIKI
**Status:** ✅ **FIXED** (Juli 2026)

Sidebar sudah meng-enforce feature flags via `isFeatureEnabled()` (lihat `Sidebar.vue:267-423`). Middleware `CheckFeatureEnabled` sudah dibuat dan diaktifkan di route groups.

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

## TEMUAN PENTING (Miss / Inkonsistensi)

### 4. ~~Flash Message (`success`) Tidak Ditampilkan di UI~~ ✅ SUDAH DIPERBAIKI
**Status:** ✅ **FIXED** (Juli 2026)

Flash message sudah di-share di `HandleInertiaRequests.php` (line 67-69):
```php
'flash' => [
    'success' => fn () => $request->session()->get('success'),
    'error'   => fn () => $request->session()->get('error'),
],
```

---

### 5. Activity Log / Audit Trail — PARTIAL
**Status:** 🟡 **PARTIALLY FIXED**

`spatie/laravel-activitylog` sudah terinstall di `composer.json`. Namun **belum ada model** yang menggunakan `LogsActivity` trait. Package tersedia, tinggal dipakai.

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

### 8. ~~Enforce Feature Flags di Sidebar + Middleware~~ ✅ SUDAH DIPERBAIKI
**Status:** ✅ **FIXED** (Juli 2026)

Sidebar sudah enforce feature flags. Middleware `CheckFeatureEnabled` sudah aktif di route groups. Lihat Finding #1.

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

## RINGKASAN PRIORITAS

| # | Temuan | Tipe | Status | Estimasi |
|---|---|---|---|---|
| 1 | Feature toggle tidak enforce | Bug | ✅ **FIXED** | — |
| 2 | updateGlobal tanpa validasi | Security | ⚠️ Belum | 30 menit |
| 3 | Maintenance Inertia response | Bug | ⚠️ Belum | 1 jam |
| 4 | Flash message tidak di-share | Miss | ✅ **FIXED** | — |
| 5 | Activity log belum dipakai | Miss | 🟡 Partial | 1-2 jam |
| 6 | Maintenance page emoji | Minor | ⚠️ Belum | 10 menit |
| 7 | Logo lama tidak dihapus | Minor | ⚠️ Belum | 15 menit |
| 8 | Feature flag enforcement | Fitur | ✅ **FIXED** | — |
| 9 | Monitoring (Pulse/Telescope) | Fitur | ⚠️ Belum | 1 jam |
| 10 | Backup database | Fitur | ⚠️ Belum | 1 jam |
| 11 | Notifikasi WA/Email | Fitur | ⚠️ Belum | Besar |
| 12 | Rate limiting | Fitur | ⚠️ Belum | 15 menit |

**Updated:** 4/12 fixed, 1 partial, 7 remaining
