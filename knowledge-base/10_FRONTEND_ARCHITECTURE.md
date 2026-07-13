# 10_FRONTEND_ARCHITECTURE

## 1. Frontend Overview
**Confidence Level: High**
(Diverifikasi dari `package.json`, `vite.config.js`, dan `resources/js/app.js`)

*   **Framework Frontend:** Vue.js 3.
*   **Vue Version:** `^3.4.0`
*   **Inertia Version:** `@inertiajs/vue3` (v2.0.0)
*   **Vite Configuration:** Menggunakan Vite 6.0.0 dengan plugin `@tailwindcss/vite` untuk kompilasi CSS, `laravel-vite-plugin` untuk integrasi Laravel, dan `vite-plugin-pwa` untuk dukungan Progressive Web App (PWA).
*   **Script Type:** JavaScript (Vanilla JS), berbasis file konfigurasi `jsconfig.json` di root.
*   **Component API:** Composition API (SFC dengan sintaks `<script setup>`).
*   **SPA atau MPA:** Single Page Application (SPA) dijembatani oleh routing Inertia.js.
*   **SSR Support:** Not Found (tidak aktif pada setup bootstrapping Laravel).
*   **Folder Structure:** Standard Laravel-Inertia layout dengan membagi folder berdasarkan `Pages`, `Layouts`, dan `Components`.

---

## 2. Folder Structure
**Confidence Level: High**
(Diverifikasi dari penelusuran direktori `resources/js/`)

*   **`resources/js/app.js`:** Entry point utama frontend. Menginisiasi Inertia App, me-resolve halaman secara dinamis menggunakan `import.meta.glob`, meregistrasikan plugin `ZiggyVue`, serta menempelkan aplikasi ke elemen DOM `#app`.
*   **`resources/js/bootstrap.js`:** Inisialisasi awal Axios HTTP client dengan menyuntikkan header `X-Requested-With: XMLHttpRequest` untuk penanganan AJAX Laravel.
*   **`resources/js/Pages/`:** Berisi seluruh komponen halaman utama SPA. File diorganisasikan berdasarkan modul bisnis (misal: `Academic/`, `Finance/`, `LMS/`, `Student/`).
*   **`resources/js/Layouts/`:** Menyimpan template induk antarmuka. 
    *   `AuthenticatedLayout.vue`: Layout dasbor utama guru/staf dengan sidebar dan topbar.
    *   `StudentLayout.vue` / `MobileAppShell.vue`: Layout mobile-first khusus untuk portal siswa.
    *   `GuestLayout.vue`: Layout minimalis untuk halaman login/regis.
*   **`resources/js/Components/`:** Komponen UI global yang dapat digunakan kembali (seperti tombol, modal, input, dropdown, pagination, dan loader khusus `NamiraLoader.vue`).
*   **`resources/js/Composables/` & `resources/js/Stores/`:** **Not Found** (Logika state dan fungsionalitas diatur lokal di masing-masing page component).

---

## 3. Page Architecture

Pemetaan halaman utama SPA berdasarkan modul bisnis:

### Module: Academic
*   **Halaman Utama:** Kelola Kelas, Siswa, Guru, Jadwal Pelajaran, Jurnal Mengajar, Absensi Kelas, dan Naik Kelas.
*   **Route / Component:** `/yayasan/students` → `Academic/Students/Index.vue`, `/yayasan/promotion` → `Academic/Promotion/Index.vue`
*   **Layout:** `AuthenticatedLayout.vue`
*   **Permission:** `role:super_admin_yayasan|admin_yayasan|admin_unit|staff_yayasan|staff_unit|teacher`
*   **Lazy Loading:** Ya (di-resolve otomatis via `import.meta.glob` di app.js).
*   **Confidence:** High.

### Module: Finance
*   **Halaman Utama:** Dasbor Pendapatan, Kategori Tagihan, Pembuatan Tagihan SPP, Impor Rekonsiliasi VA, dan Laporan Tunggakan.
*   **Route / Component:** `/yayasan/finance/transactions` → `Finance/Transactions/Index.vue`, `/yayasan/finance/transactions/import` → `Finance/Transactions/Import.vue`
*   **Layout:** `AuthenticatedLayout.vue`
*   **Permission:** `role:super_admin_yayasan|admin_yayasan|admin_unit|staff_admin_keuangan|finance`
*   **Lazy Loading:** Ya.
*   **Confidence:** High.

### Module: LMS
*   **Halaman Utama:** Linimasa stream kelas virtual guru/siswa, unggah tugas, pengumpulan tugas, dan rekap gradebook.
*   **Route / Component:** `/lms/teacher/classrooms/{id}` → `LMS/Guru/Classroom/Stream.vue`
*   **Layout:** `AuthenticatedLayout.vue` (Guru) / `StudentLayout.vue` (Siswa)
*   **Permission:** `role:teacher` / `role:siswa`
*   **Lazy Loading:** Ya.
*   **Confidence:** High.

### Module: Student Portal
*   **Halaman Utama:** Dasbor ringkasan seluler, jadwal, kesiswaan BK, tagihan SPP, menu cepat, dan To-Do list tugas pribadi.
*   **Route / Component:** `/student/dashboard` → `Student/Dashboard.vue`
*   **Layout:** `StudentLayout.vue` / `MobileAppShell.vue`
*   **Permission:** `role:siswa` (melalui middleware `EnsureStudentAccess`)
*   **Lazy Loading:** Ya.
*   **Confidence:** High.

---

## 4. Layout Architecture
**Confidence Level: High**
(Diverifikasi dari file layout di `resources/js/Layouts/`)

### Layout: `AuthenticatedLayout.vue`
*   **Responsibility:** Menyediakan bingkai dasbor admin/staf/guru pada resolusi desktop.
*   **Reusable Area:** Menyediakan navigasi sidebar sebelah kiri, bar pencarian topbar di atas, nama unit sekolah aktif, serta area konten utama.
*   **Slot:** `<slot />` untuk merender halaman halaman anak.
*   **Props:** `:auth` user data.
*   **Shared State:** Mengonsumsi properti `$page.props.auth` untuk menampilkan profil user, dan `$page.props.active_unit` untuk menampilkan unit sekolah yang sedang dikelola.

### Layout: `StudentLayout.vue` & `MobileAppShell.vue`
*   **Responsibility:** Menyediakan antarmuka seluler (*mobile app shell*) dengan navigasi bottom bar untuk akses cepat portofolio siswa.
*   **Reusable Area:** Header judul halaman atas, bottom navigation menu (Home, Akademik, Keuangan, BK, Menu), dan tombol pintas log-out.

---

## 5. Component Architecture
**Confidence Level: High**
(Diverifikasi dari berkas komponen di `resources/js/Components/`)

### Global UI Components
*   **`NamiraLoader.vue`:** Animasi transisi spinner pemuatan halaman khusus Namira.
*   **`Pagination.vue`:** Komponen navigasi pembagi halaman tabel data. Dependensi: `@inertiajs/vue3` Link.
*   **`Modal.vue`:** Komponen pop-up konfirmasi atau pengisian form melayang.
*   **`PrimaryButton.vue` / `SecondaryButton.vue` / `DangerButton.vue`:** Standardisasi tombol antarmuka.

### Feature / Rich-UI Components
*   **Leaflet (`leaflet`):** Diimpor secara lokal pada halaman absensi karyawan untuk merender peta interaktif geofencing GPS.
*   **ChartJS (`chart.js`, `vue-chartjs`):** Digunakan pada halaman dasbor keuangan (`Finance/Dashboard.vue`) dan sarpar untuk visualisasi grafik.
*   **Quill Editor (`@vueup/vue-quill`):** Digunakan pada halaman admin humas (`PublicRelations/News/Form.vue`) untuk penyusunan konten berita berformat HTML (*Rich Text*).

---

## 6. State Management
**Confidence Level: High**
(Diverifikasi dari konfigurasi aplikasi frontend)

*   **Pinia / Vuex:** **Not Found** (Tidak terdaftar pada package.json).
*   **Inertia Shared Props:** Bertindak sebagai penyimpan state global (*global state store*). Data dibagikan oleh middleware backend `HandleInertiaRequests.php` dan diakses langsung di Vue via `$page.props` atau `usePage().props`:
    *   `auth.user`: Informasi profil dan peran (*roles/permissions*) pengguna yang login.
    *   `active_unit`: Menyimpan info ID unit sekolah aktif saat ini.
    *   `active_academic_year`: Periode tahun ajaran berjalan.
    *   `flash`: Data notifikasi sukses/gagal transaksi dari backend.
*   **Local State:** Menggunakan Reactivity API bawaan Vue 3 (`ref()`, `reactive()`, `computed()`, `watch()`) untuk mengelola status input form, modal toggle, pencarian, dan pemuatan lokal di dalam masing-masing Single File Component.

---

## 7. Navigation Architecture
**Confidence Level: High**
(Diverifikasi dari komponen Sidebar dan StudentLayout bottom bar)

*   **Admin/Teacher Navigation (`Sidebar.vue`):** Menu navigasi vertikal di dasbor guru/staf. Item menu ditampilkan secara dinamis menggunakan kondisional `v-if` yang mengecek peran pengguna (`$page.props.auth.user.roles`), membatasi agar guru hanya melihat menu kurikulum dan LMS, sementara kasir hanya melihat menu finance.
*   **Student Navigation (`StudentLayout.vue`):** Bottom navigation bar yang dirancang menyerupai aplikasi mobile (Home, Academics, Finance, BK, Menu).

---

## 8. Routing Architecture
**Confidence Level: High**
(Diverifikasi dari `app.js` dan penggunaan routing Vue)

*   **Ziggy Integrator:** Rute Laravel disuntikkan ke frontend menggunakan pustaka `Ziggy`. Memungkinkan pemanggilan rute named routes Laravel langsung di Vue dengan sintaks:
    ```js
    route('yayasan.students.edit', student.id)
    ```
*   **Inertia Link:** Seluruh tautan menggunakan `<Link :href="route('...')">` untuk memicu navigasi AJAX asinkronus Inertia tanpa me-reload penuh browser (menjaga status SPA).
*   **Navigation Guard:** Otorisasi navigasi dikendalikan penuh oleh backend middleware. Jika user mencoba menembak URL terlarang via tautan browser, backend mengembalikan respons 403 / redirect, yang ditangkap oleh Inertia untuk memperbarui tampilan.

---

## 9. Form Architecture
**Confidence Level: High**
(Diverifikasi dari komponen form di halaman Pages)

*   **Inertia Form Helper:** Pemrosesan formulir menggunakan `useForm` dari `@inertiajs/vue3`:
    ```js
    const form = useForm({
        name: '',
        file: null
    });
    ```
*   **Fungsionalitas Form:**
    *   *Validation:* Error validasi dikembalikan oleh Laravel, disimpan otomatis di `form.errors`, dan ditampilkan di bawah kolom menggunakan komponen `<InputError :message="form.errors.field" />`.
    *   *Processing State:* Menggunakan status `form.processing` untuk mendisaktifkan tombol submit secara otomatis agar mencegah pengiriman data ganda (*double submit*).
    *   *Multipart Upload:* Pengunggahan file menggunakan objek FormData otomatis bawaan Inertia Form saat mendeteksi input file (misal di halaman pengumpulan tugas LMS).

---

## 10. UI Pattern
**Confidence Level: High**

*   **CRUD Table Pattern:** Pola tabel data standar (pencarian di atas, tabel data di tengah dengan tombol edit/hapus, bar pagination di bawah). Terbaca di kelola siswa, guru, kelas, dan inventaris.
*   **Mobile-First Dashboard:** Pola dasbor portal siswa dengan navigasi melengkung bottom bar dan grid menu pintas berbentuk kartu bundar (*Card Grid*).
*   **Timeline Stream:** Linimasa postingan KBM pada LMS yang menggabungkan pengumuman, materi, dan tugas terurut tanggal terbaru secara kronologis.

---

## 11. Design System
**Confidence Level: High**
(Diverifikasi dari `resources/css/app.css` dan `tailwind.config.js`)

*   **CSS Utility:** Tailwind CSS v4 terintegrasi langsung dengan compiler Vite.
*   **Typography:** Default sans-serif menggunakan font `Figtree` (didefinisikan di config tailwind).
*   **Color Palette:** Menggunakan warna-warna modern (seperti `emerald` / `rose` / `slate`) dan warna kustom yayasan `namira-teal`.
*   **Responsive Breakpoints:** Menerapkan modifikator responsive Tailwind (`sm:`, `md:`, `lg:`, `xl:`) untuk memastikan dasbor admin rapi di resolusi tablet/desktop, dan dasbor siswa ramah perangkat seluler.

---

## 12. Performance Analysis
**Confidence Level: High**

*   **Dynamic Component Import:** Resolusi halaman menggunakan `import.meta.glob('./Pages/**/*.vue')` pada `app.js` memungkinkan Vite melakukan *Code Splitting*. Halaman-halaman Vue akan dipecah menjadi chunk-chunk terpisah dan hanya dimuat ketika rute halaman tersebut dipanggil oleh user (menghemat transfer bandwidth inisial).
*   **Asset Cache (PWA):** Plugin `vite-plugin-pwa` mengonfigurasi Service Worker untuk menyimpan cache aset static (CSS, JS, gambar) secara lokal di browser guna mendukung akses instan pada muatan berikutnya.

---

## 13. Inertia Audit
**Confidence Level: High**

*   **Preserve State & Scroll:** Penggunaan `:preserve-scroll` dan `:preserve-state` pada interaksi mini seperti filter pencarian tabel atau pergantian tab forum LMS agar posisi scroll browser tidak meloncat ke atas saat data diperbarui.
*   **Asset Versioning:** Laravel mengirimkan header `X-Inertia-Version`. Jika ada pembaruan build frontend baru di server, Inertia otomatis memicu reload halaman penuh pada interaksi berikutnya untuk memperbarui cache browser pengguna.

---

## 14. Frontend Security Analysis
**Confidence Level: High**

*   **XSS Vulnerability (v-html):**
    *   *Fakta:* Ditemukan penggunaan direktif `v-html="news.content"` pada detail pembaca berita (`NewsDetail.vue`) dan `v-html="event.description"` pada detail acara (`EventDetail.vue`).
    *   *Analisis Risiko:* Jika konten berita atau acara yang disimpan di database mengandung tag skrip jahat (`<script>`), browser pembaca berita akan mengeksekusi skrip tersebut secara langsung. Ini adalah celah keamanan XSS jika data input editor Quill di backend tidak dibersihkan (*sanitized*) menggunakan library seperti HTMLPurifier sebelum disimpan.
*   **UI-Level Authorization:** Menyembunyikan tombol aksi edit/hapus menggunakan kondisional peran `$page.props.auth.user.roles`. Hal ini aman karena rute aksi backend-nya tetap diproteksi ulang oleh middleware Laravel Spatie.

---

## 15. UX Analysis
**Confidence Level: High**

*   **Visual Feedback:**
    *   *Toast Notification:* SweetAlert2 diimpor untuk memicu pop-up konfirmasi visual yang interaktif saat admin menghapus data atau memproses pembayaran.
    *   *Loader:* Pemuatan halaman Inertia dikawal oleh progress bar standar (`#4B5563`) di atas browser ditambah animasi `NamiraLoader.vue` pada proses transisi dasbor.

---

## 16. Dependency Analysis
**Confidence Level: High**

*   **Tight Coupling (Ziggy):** Frontend sangat tergantung pada konfigurasi rute backend. Jika nama route di Laravel dirubah, seluruh file Vue yang merujuk named route tersebut via helper `route('...')` akan memicu error rendering runtime di browser.
*   **External Library Coupling:** Dasbor keuangan bergantung erat pada `Chart.js` dan absensi geofencing bergantung pada `Leaflet.js`.

---

## 17. Frontend Hotspot

Halaman frontend dengan kompleksitas tertinggi:
1.  **`Finance/Transactions/Import.vue`:** Mengelola form upload mutasi, parameter visual status kolom, parsing log sukses/gagal, dan pop-up loading interaktif yang intensif.
2.  **`Academic/Promotion/Index.vue`:** Menangani wizard penyaringan siswa, pencocokan rombel asal dan tujuan, penanganan status naik/tinggal kelas per siswa dalam bentuk tabel dinamis, serta pengiriman array data besar ke backend.

---

## 18. Frontend Technical Debt

1.  **Inline Logic yang Padat di Komponen Halaman:**
    *   *Evidence:* Kueri pencarian, filter status, pengolahan format tanggal lokal (`dayjs`), dan penanganan modal ditulis menumpuk di file page Vue (misal di halaman siswa BK dan tagihan keuangan). Halaman Vue bertindak sebagai *God Components*.
2.  **Ketiadaan State Store Terstruktur:**
    *   *Evidence:* Modul LMS yang memiliki interaksi data kompleks (forum, file materi, detail pengumpulan tugas) tidak menggunakan composable state sharing, melainkan mengalirkan props secara bertingkat (*props drilling*) dari layout/stream ke sub-komponen.

---

## 19. Frontend Dependency Matrix (Core Modules)

| Component Page | Layout Induk | Domain Module | Laravel Route pengait | External Library Dependency |
| :--- | :--- | :--- | :--- | :--- |
| `Academic/Promotion/Index.vue` | `AuthenticatedLayout` | Academic | `yayasan.promotion.index` | SweetAlert2 |
| `Finance/Dashboard.vue` | `AuthenticatedLayout` | Finance | `yayasan.finance.dashboard` | Chart.js, vue-chartjs |
| `Employee/Attendance/Index.vue`| `StudentLayout` / Mobile | Employee | `employee.attendance.index` | Leaflet.js |
| `PublicRelations/News/Form.vue`| `AuthenticatedLayout` | PublicRelations| `public-relations.news.store` | @vueup/vue-quill |

---

## 20. Frontend Refactoring Recommendation

### Recommendation 1: Pemisahan Logika ke Composable Pattern
*   **Problem:** Halaman Vue mengelola logika formatting tanggal, filter pencarian, dan kalkulasi lokal secara langsung (God Components).
*   **Evidence:** File page di bawah `Academic/Students/Index.vue` dan `LmsClassroomController` memiliki ratusan baris skrip berisi manipulasi data lokal.
*   **Impact:** Kode sulit dipelihara (*low maintainability*) dan tidak dapat diuji secara terpisah (*low testability*).
*   **Recommendation:** Buat folder `resources/js/Composables/` dan ekstrak logika pemfilteran/formatting menjadi fungsi pembantu terpisah, contoh: `useStudentFilter.js` atau `useDateTimeFormatter.js`.
*   **Priority:** High | **Confidence:** High

### Recommendation 2: Implementasi Sanitasi Client-Side pada `v-html`
*   **Problem:** Penggunaan direktif `v-html` untuk merender konten berita dan deskripsi event berpotensi celah keamanan XSS.
*   **Evidence:** `v-html="news.content"` di `NewsDetail.vue` dan `v-html="event.description"` di `EventDetail.vue`.
*   **Impact:** Akun pembaca berita dapat diretas jika editor humas menyisipkan tag skrip berbahaya (XSS).
*   **Recommendation:** Pasang library client-side sanitization seperti `dompurify` dan bungkus rendering v-html dengannya, contoh: `v-html="DOMPurify.sanitize(news.content)"`.
*   **Priority:** High | **Confidence:** High
