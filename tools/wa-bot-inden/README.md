# Bot WhatsApp Anti-Ban — Undangan Grup Inden SD Namira Kraksaan

Bot mandiri untuk mengirim undangan **Grup WhatsApp Resmi Orang Tua Calon Murid** + pengingat formulir biodata online (`https://namiraschool.com/spmb/daftar/sd`) kepada **72 Data Inden SD Namira Kraksaan** secara personal (japri) dan aman dari pemblokiran WhatsApp.

## Fitur Keamanan Anti-Ban (5 Lapis)
1. **Deduplikasi Otomatis Nomor Kembar**: Dari 72 baris data Excel, nomor `082302559423` tercatat 2 kali (baris #31 & #58) sehingga otomatis digabung menjadi **71 nomor WhatsApp unik** agar tidak pernah terkirim ganda.
2. **Cek Nomor Aktif WhatsApp (`onWhatsApp`)**: Nomor yang sudah tidak aktif di WhatsApp otomatis dilewati tanpa memicu error spam.
3. **Simulasi Mengetik Manusia (`Typing Indicator`)**: Bot menampilkan status *"sedang mengetik..."* selama 3,5 – 7 detik layaknya admin manusia sebelum pesan dikirim.
4. **Variasi Teks Dinamis (Anti-Hash Identik)**: Sapaan, doa pembuka, kalimat pengantar (otomatis membedakan alumni `TK Namira` vs TK luar), penutup, dan kode referensi unik diacak secara sopan sehingga tidak ada 2 pesan yang memiliki sidik jari teks identik.
5. **Jeda Acak 45–85 Detik + Istirahat Batch Otomatis**: Setiap pesan dijeda acak 45–85 detik, ditambah istirahat 3–5 menit setiap 15 pesan terkirim. Progress tersimpan seketika di `sent_log.json` (aman dijeda dengan `Ctrl+C` dan dilanjutkan kapan saja tanpa dobel kirim).

---

## Cara Pakai Cepat (Windows)

### Langkah 1 — Tes 1 Pesan ke Nomor Sendiri
Klik 2x file **`1_TES_KIRIM_KE_SAYA.bat`** (atau jalankan `npm run test-pesan` di terminal folder ini):
1. Jika belum pernah mengisi Link Grup WA, bot akan meminta Anda menempelkan link `https://chat.whatsapp.com/...` dan menyimpannya otomatis ke `config.json`.
2. Masukkan nomor WA Anda sendiri untuk menerima 1 pesan contoh.
3. Scan QR Code yang muncul di layar melalui aplikasi WhatsApp HP (**Perangkat Tertaut -> Tautkan Perangkat**).
4. Cek hasil pesan yang masuk di HP Anda.

### Langkah 2 — Jalankan Pengiriman Anti-Ban 72 Data Inden
Klik 2x file **`2_MULAI_KIRIM_72_INDEN.bat`** (atau jalankan `npm start`):
- Bot otomatis mengirim satu per satu dengan jeda aman (~65–75 menit untuk seluruh 71 nomor unik).
- Biarkan jendela terminal tetap terbuka hingga muncul tulisan **`PENGIRIMAN SELESAI!`**.
- Jika ingin berhenti sementara, tekan `Ctrl+C`. Saat dijalankan kembali nanti, bot otomatis melanjutkan dari nomor yang belum terkirim.

---

## Perintah Tambahan (Opsional)
- `npm run status` (atau klik `3_CEK_STATUS_TERKIRIM.bat`) — Lihat jumlah nomor yang sudah terkirim & sisa antrean.
- `npm run dry-run` — Lihat contoh hasil variasi teks pesan tanpa terhubung ke WhatsApp.
- `npm run logout` — Hapus sesi login WhatsApp jika ingin mengganti nomor pengirim.
- `npm run reset-log` — Reset catatan `sent_log.json` (otomatis mem-backup log lama).
