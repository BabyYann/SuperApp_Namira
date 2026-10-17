# Standar Dokumentasi Sistem SuperApp Namira

Dokumen ini mendefinisikan aturan format, konvensi teknis, standar penamaan, dan kebijakan integritas data yang wajib dipatuhi dalam seluruh dokumentasi teknis SuperApp Namira.

---

## 1. Konvensi Bahasa & Tipografi
1. **Bahasa Baku:** Seluruh dokumen ditulis dalam Bahasa Indonesia formal dan baku yang sesuai untuk dokumentasi korporat/enterprise, dengan mempertahankan istilah teknis universal dalam bahasa aslinya (misal: *Single Page Application*, *Foreign Key*, *Controller*, *Middleware*, *State Management*).
2. **Format Berkas:** Menggunakan Markdown standar (GitHub Flavored Markdown - GFM).
3. **Format Tanggal & Waktu:** Menggunakan format tanggal standar Indonesia (contoh: `16 Oktober 2026`) dengan zona waktu `WIB` (UTC+7).
4. **Format Kode & Path:**
   - Nama berkas dan path direktori wajib menggunakan format inline code atau markdown link: contoh `resources/js/Pages/Academic/Schedules/Index.vue`.
   - Simbol kode seperti method, fungsi, atau kelas ditulis dengan inline code: contoh `StudentController::index()`.
   - Nama tabel dan kolom database ditulis dengan huruf kecil tebal/inline code: contoh tabel `class_schedules`, kolom `academic_year_id`.

---

## 2. Standar Notasi Status Fitur
Setiap fitur, modul, atau rute harus diberi label status yang konsisten berdasarkan bukti nyata kode sumber:

| Status Label | Kriteria & Arti Teknis | Contoh Kasus |
|---|---|---|
| **`IMPLEMENTED`** | Kode lengkap, rute aktif, antarmuka Vue terpasang, dan skema database mendukung secara penuh. | Fitur Presensi Guru Geofence GPS (`AttendanceController.php`). |
| **`PARTIAL`** | Kode controller ada namun antarmuka belum lengkap, atau aksi CRUD belum terhubung tuntas. | Fitur Cetak Kartu Ujian (rute ada, template PDF masih minimal). |
| **`PLANNED`** | Disebutkan dalam dokumen kebutuhan bisnis namun belum ada berkas kode terkait. | Integrasi Payment Gateway Otomatis (Midtrans/Xendit). |
| **`UNVERIFIED`** | Implementasi kode ada, tetapi membutuhkan pengujian perangkat keras fisik atau API berbayar aktif untuk pembuktian. | Pemindaian fisik Gate RFID Scanner atau gateway WhatsApp vendor. |
| **`GAP`** | Terdapat perbedaan antara aturan bisnis di `knowledge-base/` dengan implementasi rute/controller di kode sumber. | Hak akses approval presensi antar role kepala sekolah vs yayasan. |
| **`NOT_APPLICABLE`** | Komponen bawaan boilerplate yang tidak diaktifkan pada sistem produksi. | Fitur registrasi mandiri publik bawaan Breeze untuk role siswa. |

---

## 3. Format Penulisan Register Bukti (Evidence Tagging)
Setiap klaim arsitektural dan spesifikasi teknis penting wajib mencantumkan ID Bukti (**Evidence ID**) yang merujuk pada berkas nyata di `docs/00-governance/evidence-register.md`.

Format Evidence ID:
- `EV-CORE-XXX`: Arsitektur dasar, framework, dependensi, dan multi-tenancy.
- `EV-ACAD-XXX`: Modul Akademik, kurikulum, guru, siswa, kelas, jadwal.
- `EV-EMP-XXX`: Modul Kepegawaian, staf, absensi karyawan, log giat.
- `EV-FIN-XXX`: Modul Keuangan, pos bayar, tagihan SPP, transaksi kas.
- `EV-COUN-XXX`: Modul BK/Konseling, pelanggaran tata tertib, prestasi siswa.
- `EV-SARP-XXX`: Modul Sarana Prasarana, aset inventaris, ruang, perawatan.
- `EV-LMS-XXX`: Modul LMS, kelas digital, materi, penugasan, nilai.
- `EV-SPEC-XXX`: Modul spesifik (Daycare balita, SPMB online, Ekstrakurikuler).
- `EV-NOTIF-XXX`: Real-time WebSocket Reverb, Push Notification FCM, WhatsApp.

---

## 4. Standar Diagram Mermaid
Diagram harus menggunakan sintaks Mermaid resmi yang didukung luas:
- **Arsitektur & Konteks:** Menggunakan `flowchart TD` atau `flowchart LR`.
- **Alur Bisnis & Proses:** Menggunakan `flowchart TD` dengan percabangan keputusan jelas.
- **Interaksi Komponen:** Menggunakan `sequenceDiagram` untuk siklus autentikasi, pembayaran, dan presensi.
- **Skema Data:** Menggunakan `erDiagram` dengan relasi cardinalitas yang terverifikasi (`||--o{`, `||--||`).

Aturan kebersihan diagram:
- Hindari tag HTML di dalam label node.
- Berikan kutip ganda pada teks node yang memuat spasi atau karakter kurung: `node["Nama Node (Keterangan)"]`.

---

## 5. Kebijakan Privasi & Keamanan Informasi
1. **Dilarang Mengekspos Rahasia:** Kunci API (`APP_KEY`, `VITE_FIREBASE_*`, token bot WA, kredensial database) TIDAK BOLEH ditulis secara literal dalam dokumen. Selalu gunakan placeholder semantik seperti `<REDACTED_DATABASE_PASSWORD>` atau `<CONFIGURED_IN_ENV>`.
2. **Anonimisasi PII (Personally Identifiable Information):** Data siswa, nomor induk kependudukan (NIK), alamat tempat tinggal, dan nomor rekening riil tidak boleh diekspos sebagai contoh literal tanpa disamarkan (contoh penyamaran: `351301XXXXXXXXXX`, `08123456XXXX`).
