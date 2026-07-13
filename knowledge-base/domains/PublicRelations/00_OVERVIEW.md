# Public Relations Domain Overview

## 1. Domain Overview
*   **Domain Name:** PublicRelations (Humas)
*   **Purpose:** Mengelola informasi eksternal sekolah berupa publikasi berita, artikel, dokumentasi kegiatan/event sekolah, testimoni publik, serta data mitra industri/sponsor sekolah.
*   **Responsibility:**
    *   Mengatur proses draf, sunting, dan publikasi berita/artikel sekolah.
    *   Mengatur publikasi event/acara sekolah yang dapat diakses oleh publik.
    *   Mengelola riwayat jumlah tayangan berita (`views`) untuk mengukur popularitas artikel.
    *   Mengelola direktori profil mitra industri sekolah (`Partner`).
*   **Business Objective:** Membangun citra positif yayasan/sekolah di mata publik, menyediakan portal berita resmi bagi wali murid dan masyarakat, serta memfasilitasi kerja sama dengan pihak industri.

---

## 2. Domain Boundary
*   **Tanggung Jawab Domain:**
    *   Pengelolaan konten portal publik (News, Events, Partners).
    *   Menghitung jumlah pembaca artikel sekolah.
*   **Bukan Tanggung Jawab Domain:**
    *   Mengelola jadwal pelajaran sekolah (tanggung jawab domain *Academic*).
    *   Pencatatan pelanggaran siswa BK (tanggung jawab domain *Counseling*).
    *   Pengelolaan peminjaman ruangan untuk event internal (tanggung jawab domain *Sarpar*).

---

## 3. Owned Components
*   **Controllers (3):**
    *   `EventController.php`, `NewsController.php`, `PartnerController.php`
*   **Models:**
    *   *Status:* **None** (Menggunakan core shared models di folder `app/Models/` yaitu `Event.php`, `News.php`, dan `Partner.php`).
*   **Pages (6):**
    *   Vue files under `resources/js/Pages/PublicRelations/` (Halaman internal admin) dan `resources/js/Pages/Public/` (Halaman portal publik).

---

## 4. Owned Entities
*   `News` (Reside in `app/Models/News.php`)
*   `Event` (Reside in `app/Models/Event.php`)
*   `Partner` (Reside in `app/Models/Partner.php`)

---

## 5. Shared Components
*   `User` (Core Model - merekam pembuat berita/author).
*   `Unit` (Yayasan Model - mengaitkan postingan berita dengan sekolah tertentu).

---

## 6. Incoming Dependencies
*   **Core (Web Routes / Homepage):** Homepage utama (`Welcome.vue`, `NewsIndex.vue`, `EventIndex.vue`) memanggil model `News`, `Event`, dan `Partner` untuk ditampilkan langsung ke masyarakat umum tanpa login.

---

## 7. Outgoing Dependencies
*   **Yayasan:** Membutuhkan data unit sekolah (`Unit`) untuk melabeli asal berita/kegiatan.
*   **Core:** Bergantung penuh pada model `News`, `Event`, `Partner`, dan `User` yang diletakkan di direktori core.

---

## 8. Entry Points
*   **Web Routes (Public):** Rute publik `/`, `/berita`, `/berita/{slug}`, `/events`, `/events/{slug}` di `routes/web.php`.
*   **Web Routes (Admin):** Rute admin `/public-relations/news`, `/public-relations/events`, `/public-relations/partners` di bawah middleware otorisasi staf humas.
*   **Sidebar Navigation:** Menu Humas pada dashboard administrator unit/yayasan.

---

## 9. Public Interface
*   Model `News`, `Event`, dan `Partner` terhubung langsung dengan rute publik yang tidak memerlukan autentikasi login (tamu/guest).

---

## 10. Internal Structure
*   `app/Modules/PublicRelations/Controllers/` berisi controller CRUD berita dan kemitraan.
*   `resources/js/Pages/PublicRelations/` berisi form pembuatan berita dan daftar mitra untuk admin.
*   `resources/js/Pages/Public/` berisi template layout pembaca berita untuk umum.

---

## 11. Domain Findings
1.  **Tidak Memiliki Model Internal:**
    *   *Evidence:* Modul `PublicRelations` tidak mendefinisikan model apa pun di dalam foldernya sendiri. Model `News`, `Event`, dan `Partner` semuanya diletakkan di folder global `app/Models/`. Hal ini memicu dependensi tinggi dari core ke modul humas.
    *   *Confidence:* High.
2.  **Mekanisme Anti-Spam View Counter:**
    *   *Evidence:* Terdapat session-based view counter (`viewed_events` dan `viewed_news`) pada route detail berita untuk mencegah eksploitasi pembaca berita palsu lewat tombol refresh.
    *   *Confidence:* High.

---

## 12. Unknown Areas
*   Apakah ada integrasi otomatis untuk membagikan berita baru ke media sosial sekolah atau grup WhatsApp pengumuman yayasan.

---

## 13. Confidence
*   **High:** Fungsionalitas humas (berita, event, testimoni) terbagi jelas dengan antarmuka portal umum yang dapat diakses publik tanpa login.
