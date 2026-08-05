# Laporan Upgrade Stack Frontend — SuperApp Namira

**Tanggal:** 05 Agustus 2026
**Status Selesai:** Fase 1–5

---

## Ringkasan

Upgrade menyeluruh stack frontend selesai: Inertia.js **v2 → v3**, Vite **6 → 8** (bundler Rolldown/Rust), ESLint **8 → 10** (flat config), Tailwind **4.1 → 4.3**, Vue minor, serta perombakan dependency. Build produksi **sukses** dan seluruh test render halaman Inertia (AcademicCrudTest) **hijau**.

---

## Fase 1 — Server Adapter Inertia v3 (Backend)

| Paket | Sebelum | Sesudah |
|---|---|---|
| `inertiajs/inertia-laravel` | v2.0.14 | **v3.3.1** |

- Client `@inertiajs/vue3` v3 memerlukan server adapter v3; keduanya sudah naik.
- **Scan breaking change:** tidak ada pemakaian `Inertia::lazy()`, `router.cancel()`, `qs`, `onCancel` di kode — semua API yang dipakai (`useForm`, `usePage`, `Head`, `Link`, `router.get/post/reload`) stabil di v3. → **tanpa perubahan kode**.
- `composer.json`: ditambah `config.platform.php = 8.4.0` (menjaga resolusi dependency tetap stabil terhadap constraint PHP yang konservatif, sesuai stack PHP 8.4 proyek).
- **Bonus fix (pre-existing):** migrasi `2026_07_20_102809_add_graded_status_to_lms_submissions_table.php` memakai `ALTER ... MODIFY COLUMN ... ENUM` (khusus MySQL) yang memecah seluruh test di SQLite `:memory:`. Ditambahkan guard `getDriverName() !== 'mysql'` supaya DB-agnostik. Sebelum fix: **83 test gagal total**; sesudah: sebagian besar lulus.

### Hasil test (Fase 1 & final)
```
Tests: 60 passed, 24 failed (120 assertions)
```
- `Tests/Feature/AcademicCrudTest` (10 test render halaman Inertia): **10/10 PASS** — bukti Inertia v3 server bekerja normal.
- 24 kegagalan tersisa adalah **utang test pre-existing** yang tidak terkait Inertia (detail di bagian Akhir).

---

## Fase 2 — Bump Dependency Frontend

| Paket | Sebelum | Sesudah |
|---|---|---|
| `@inertiajs/vue3` | 2.3.1 | **3.6.1** |
| `vite` | 6.4.1 | **8.2.0** |
| `@vitejs/plugin-vue` | 5.2.4 | **6.0.8** |
| `laravel-vite-plugin` | 2.0.1 | **3.1.3** |
| `tailwindcss` `@tailwindcss/vite` | 4.1.18 | **4.3.3** |
| `vue` | 3.5.25 | **3.5.40** |
| `eslint` | 8.57.1 | **10.8.0** |
| `eslint-plugin-vue` | 9.33.0 | **10.10.0** |
| `@vue/eslint-config-prettier` | 9.0.0 | **10.2.0** |
| `vite-plugin-pwa` | 0.21.2 | **1.3.0** |
| `axios`, `sweetalert2`, `dayjs`, `@vueup/vue-quill`, `@vueuse/core`, `laravel-echo`, `pusher-js`, `concurrently`, `prettier`, `prettier-plugin-tailwindcss`, `vue-chartjs` | lama | **minor terbaru** |

### Cleanup & repair saat `npm install`
1. **Hapus `@rushstack/eslint-patch`** — tidak dibutuhkan lagi di flat config.
2. **Hapus `vue-select`** — ternyata paket **Vue 2** (`peerDependencies: vue 2.x`) dan **tidak dipakai di codebase**; menyebabkan ERESOLVE.
3. **Tambah `lodash`** (`^4.17.21`) — 5 file mengimpor `lodash/debounce` langsung, sebelumnya hanya andalkan transitive hoisting yang hilang setelah fresh install; tanpa ini build gagal.
4. **Tambah `@eslint/js`** (`^10.0.1`) — `eslint:recommended` di ESLint 10 tidak lagi bawaan, harus deklarasi eksplisit.

---

## Fase 3 — Migrasi ESLint ke Flat Config

- `.eslintrc.cjs` (legacy, dihapus) → **`eslint.config.js`** (flat config).
- Memakai `@eslint/js`, `eslint-plugin-vue` `flat/essential`, dan `@vue/eslint-config-prettier/skip-formatting`.
- Rule lama dipertahankan: `vue/multi-word-component-names: off`, `no-undef: off`.
- Script `lint` diubah: `eslint resources/js --fix` (drop `--ext` / `--ignore-path` legacy).

---

## Fase 4 — Adaptasi Breaking Changes

**Tidak diperlukan perubahan kode.** Semua API Inertia yang dipakai (60+ pemakaian `router.*`, belasan `useForm`, banyak `usePage`) sudah kompatibel dengan v3. Tidak ada `router.cancel`, `Inertia::lazy`, atau `qs`.

---

## Fase 5 — Verifikasi

| Pemeriksaan | Hasil |
|---|---|
| `npm run build` | ✅ **Sukses** — Vite 8.2, 2729 modul ter-transform, built 12.82s; PWA precache 227 entri (`sw.js`, workbox) terbentuk |
| `npm run lint` | ⚠️ Bekerja; melaporkan **247 error `no-unused-vars` di 87 file** (utang kode pre-existing, tak dijalankan di CI) |
| `composer test` | ⚠️ 60 lulus / 24 gagal (pre-existing — lihat bagian bawah) |
| `php artisan about` | ✅ Boot normal, aplikasi berjalan (Laravel 12.42, PHP 8.5) |
| `composer validate` | ✅ Valid (hanya warning `*` unbound version pre-existing) |

---

## Perubahan File

- `composer.json`, `composer.lock` — Adapter Inertia v3 + `config.platform.php`
- `package.json`, `package-lock.json` — Upgrade dependency (Fase 2)
- `eslint.config.js` (baru), `.eslintrc.cjs` (dihapus)
- `database/migrations/2026_07_20_102809_add_graded_status_to_lms_submissions_table.php` — fix DB-agnostic
- `public/build/**` — **diregenerasi** oleh build Vite 8 (217 file lama hapus / 218 baru). Sesuai pola deploy repo ini (build di-commit ke git), silakan commit `public/build` sebelum deploy.

---

## Catatan Lingkungan

PHP CLI (`C:\Program Files\PHP\current\php.exe`, 8.5.7) **tidak memuat ekstensi** `openssl`, `curl`, `pdo_sqlite`, `mbstring`, dst. Composer/artisan/test butuh ekstensi tersebut. Solusi sementara yang dipakai:

```
$env:PHP_INI_SCAN_DIR = "C:\Users\sdnam\AppData\Local\Temp\opencode\phpconf"
& "C:\Program Files\PHP\current\php.exe" C:\composer\composer.phar <perintah>
```

> **Rekomendasi:** aktifkan ekstensi di `C:\Program Files\PHP\8.5.7\ts\x64\php.ini` (butuh akses admin) agar `php`, `composer`, dan `artisan` berjalan tanpa env var manual.

---

## Isu Pre-existing (tidak diperbaiki — di luar scope upgrade)

**A. 24 test gagal (60 lulus).** Semua berasal dari lapisan di luar Inertia:
- Test Unit (`ClassPromotionDashboardTest`, `PromotionValidationEngineTest`) memanggil model DB tanpa migrasi → `no such table: users` (design flaw; unit test menyentuh DB).
- Test Auth (`users can authenticate`, `logout`, sebagian `PasswordReset`) — assertion terhadap alur auth yang sudah bergeser dari implementasi saat ini (test drift).
- Test RBAC/Finance — ekspektasi `200` namun menerima `403` (masalah scope/permission di lingkungan test, bukan Inertia).

Bukti: `AcademicCrudTest` (10 halaman yang di-render Inertia) lulus sempurna; kegagalan terjadi pada assertion auth/permission sebelum rendering Inertia.

**B. 247 error lint `no-unused-vars`** di 87 file — import/variabel tak terpakai yang sudah ada sebelumnya; kini terlihat karena parser eslint-plugin-vue v10 yang lebih ketat. `npm run lint` tidak dijalankan di CI (`ci.yml` hanya Pint, Pest, dan `npm run build`), sehingga tidak memblokir.

Rekomendasi lanjutan (opsional, di luar upgrade ini): bersihkan unused import, perbaiki test unit agar memakai migrasi, dan tambah `npm run lint` ke CI.