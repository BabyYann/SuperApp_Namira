# SuperApp Namira — Diagram Urutan Alur Kritis (Sequence Diagrams)

**Dokumen Spesifikasi Interaksi Sistem & Komponen**  
**ID Dokumen:** `DOC-FLOW-03-SEQUENCE`  
**Status Audit:** `IMPLEMENTED`  
**Terkait:** [`01-business-process-catalog.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/05-business-flows/01-business-process-catalog.md), [`02-user-journeys.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/05-business-flows/02-user-journeys.md), [`05-backend-architecture.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/03-architecture/05-backend-architecture.md)

---

## 1. Sequence 1: Presensi Pegawai Berbasis Geofence & Kalkulasi Keterlambatan

Menjelaskan validasi koordinat GPS pegawai terhadap radius titik unit serta penentuan otomatis menit keterlambatan dinas.

```mermaid
sequenceDiagram
    autonumber
    actor Pegawai as Pegawai (Guru / Staf)
    participant App as Mobile Web / PWA (Vue)
    participant GeolocationAPI as Browser Geolocation API
    participant AttCtrl as AttendanceController
    participant UnitModel as Unit Model & Config
    participant DB as MySQL InnoDB

    Pegawai->>App: Klik "Presensi Masuk"
    App->>GeolocationAPI: getCurrentPosition()
    GeolocationAPI-->>App: Return (latitude, longitude, accuracy)
    App->>Pegawai: Buka Kamera untuk Swafoto
    Pegawai->>App: Ambil Foto Selfie
    App->>AttCtrl: POST /employee-attendance (lat, lng, photo_base64)
    
    AttCtrl->>UnitModel: Ambil Koordinat Pusat & Radius Unit
    UnitModel-->>AttCtrl: (unit_lat, unit_lng, radius_meters)
    
    AttCtrl->>AttCtrl: Hitung Jarak Menggunakan Formula Haversine
    alt Jarak > Radius Geofence
        AttCtrl-->>App: HTTP 422: "Anda berada di luar radius lokasi presensi sekolah!"
        App-->>Pegawai: Tampilkan Galat & Jarak Aktual
    else Jarak <= Radius Geofence
        AttCtrl->>UnitModel: Ambil work_start_time (misal 07:00:00)
        AttCtrl->>AttCtrl: Bandingkan Waktu Sekarang dengan work_start_time
        alt Waktu <= work_start_time
            Note over AttCtrl: status = 'present', late_minutes = 0
        else Waktu > work_start_time
            Note over AttCtrl: status = 'late', late_minutes = diff_in_minutes
        end
        AttCtrl->>DB: Simpan Berkas Foto ke Storage & INSERT employee_attendances
        DB-->>AttCtrl: OK (Record ID)
        AttCtrl-->>App: HTTP 200: "Presensi Berhasil Dicatat"
        App-->>Pegawai: Tampilkan Kartu Sukses & Jam Masuk
    end
```

---

## 2. Sequence 2: Gate QR Scanner, Reverb WebSocket & WhatsApp Notification Queue

Menjelaskan pemindaian kartu barcode santri di pos satpam, transmisi real-time ke monitor gerbang, dan otomatisasi pengiriman pesan WhatsApp kepada orang tua.

```mermaid
sequenceDiagram
    autonumber
    actor Satpam as Petugas Keamanan Gerbang
    participant ScannerApp as Gate Scanner UI (Quagga / ZXing)
    participant GateCtrl as StudentCheckinController
    participant Reverb as Laravel Reverb (WebSocket)
    participant Monitor as Monitor Display Gerbang
    participant WAQueue as WhatsAppQueue Job
    participant WAGateway as WhatsApp API Gateway
    actor Ortu as Orang Tua Santri (WhatsApp)

    Satpam->>ScannerApp: Scan Barcode Kartu Santri
    ScannerApp->>GateCtrl: POST /gate/checkin {student_barcode, scanner_id}
    
    GateCtrl->>GateCtrl: Cari Data Santri & Cek Status Hari Ini
    GateCtrl->>GateCtrl: INSERT student_checkins (checked_in_at, scanned_by)
    
    GateCtrl->>Reverb: Broadcast Event: StudentCheckinCreated(student_data)
    Reverb-->>Monitor: Emit "gate-checkin-channel"
    Monitor->>Monitor: Update Tampilan: Foto Santri, Nama, Kelas, Status HIJAU
    
    GateCtrl->>WAQueue: Dispatch SendGateArrivalNotificationJob(student_id)
    GateCtrl-->>ScannerApp: HTTP 200: "Check-in Berhasil"
    
    Note over WAQueue: Diproses Asinkron oleh Worker Antrean
    WAQueue->>WAQueue: Format Pesan Notifikasi Kedatangan
    WAQueue->>WAGateway: POST /api/send-message {phone, message}
    WAGateway-->>Ortu: Pesan Diterima di WhatsApp HP Ortu
    WAGateway-->>WAQueue: Status: DELIVERED
    WAQueue->>WAQueue: UPDATE whatsapp_messages_queue status='sent'
```

---

## 3. Sequence 3: Siklus Tagihan Siswa, Unggah Bukti Bayar & Rekonsiliasi Kas

Menjelaskan alur penagihan, konfirmasi pembayaran dari wali murid, hingga pencatatan transaksi buku kas oleh staf keuangan.

```mermaid
sequenceDiagram
    autonumber
    actor Wali as Wali Murid
    participant ParentApp as SuperApp (Portal Ortu)
    actor TUFin as Bendahara / Staf Keuangan
    participant FinCtrl as StudentBillController
    participant DB as MySQL InnoDB

    TUFin->>FinCtrl: Eksekusi "Generate Tagihan SPP Bulanan"
    FinCtrl->>DB: INSERT student_bills (status='unpaid', amount, due_date)
    DB-->>FinCtrl: Tagihan Terbit
    
    Wali->>ParentApp: Buka Menu "Tagihan Saya"
    ParentApp-->>Wali: Tampilkan Daftar Tagihan (SPP Rp 500.000)
    Wali->>ParentApp: Unggah Bukti Transfer Bank (payment_proof)
    ParentApp->>FinCtrl: POST /bills/{id}/upload-proof
    FinCtrl->>DB: UPDATE student_bills SET payment_proof = path
    FinCtrl-->>ParentApp: "Bukti Pembayaran Terkirim Menunggu Verifikasi"
    
    TUFin->>FinCtrl: Buka Menu "Verifikasi Pembayaran"
    FinCtrl-->>TUFin: Tampilkan Bukti Transfer & Info Santri
    TUFin->>TUFin: Verifikasi dengan Mutasi Rekening Koran Bank
    TUFin->>FinCtrl: Klik "Approve & Selesaikan Pembayaran"
    
    Note over FinCtrl: DB::transaction Dimulai
    FinCtrl->>DB: INSERT bill_payments (amount_paid, payment_date)
    FinCtrl->>DB: INSERT transactions (type='income', amount, account_id)
    FinCtrl->>DB: UPDATE student_bills SET status='paid', paid_amount=amount, remaining_amount=0
    Note over FinCtrl: DB::transaction Commit
    
    FinCtrl-->>TUFin: "Pembayaran Berhasil Diverifikasi & Masuk Buku Kas"
    ParentApp-->>Wali: Status Tagihan Berubah Menjadi "LUNAS", Kwitansi Tersedia
```

---

## 4. Sequence 4: Pengisian Jurnal Mengajar & Propagasi Presensi Sesi

Menjelaskan bagaimana seorang guru mendokumentasikan proses KBM, memilih capaian pembelajaran kurikulum, dan menyebarkan status kehadiran santri secara atomik.

```mermaid
sequenceDiagram
    autonumber
    actor Guru as Guru Pengampu Mapel
    participant MobileApp as SuperApp Mobile (Vue)
    participant JrnCtrl as TeachingJournalController
    participant DB as MySQL InnoDB

    Guru->>MobileApp: Pilih Jadwal Mengajar & Klik "Buat Jurnal"
    MobileApp->>Guru: Tampilkan Daftar Siswa Kelas & Opsi TP Kurikulum
    Guru->>MobileApp: Tulis Rangkuman Materi, Checklist Siswa Hadir/Izin, Upload Foto
    Guru->>MobileApp: Klik "Simpan Jurnal KBM"
    
    MobileApp->>JrnCtrl: POST /academic/teaching-journals (payload multipart)
    
    Note over JrnCtrl: Validasi Hak Akses & Integritas Jam Mengajar
    Note over JrnCtrl: DB::transaction Dimulai
    JrnCtrl->>DB: Simpan Foto Dokumentasi ke Storage
    JrnCtrl->>DB: INSERT teaching_journals (schedule_id, teacher_id, date, summary, photo)
    
    loop Setiap Indikator TP Terpilih
        JrnCtrl->>DB: INSERT journal_learning_objectives (journal_id, objective_id)
    end
    
    loop Setiap Siswa di Rombel
        JrnCtrl->>DB: INSERT journal_attendance (journal_id, student_id, status, notes)
    end
    Note over JrnCtrl: DB::transaction Commit
    
    JrnCtrl-->>MobileApp: HTTP 201: "Jurnal Mengajar Berhasil Disimpan"
    MobileApp-->>Guru: Tampilkan Kartu Jurnal Selesai & Ringkasan Kehadiran
```

---

## 5. Sequence 5: Kenaikan Kelas Massal & Mekanisme Rollback

Menjelaskan mutasi rombel siswa saat tahun ajaran baru dan proses pembatalan aman (*rollback*) jika ditemukan kesalahan administrasi.

```mermaid
sequenceDiagram
    autonumber
    actor AdminTU as Staf Tata Usaha
    participant PromoCtrl as ClassPromotionController
    participant PromoService as PromotionService
    participant DB as MySQL InnoDB

    AdminTU->>PromoCtrl: POST /academic/promotions (source_class_id, target_class_id, student_ids)
    PromoCtrl->>PromoService: executePromotion(data)
    
    Note over PromoService: DB::transaction Dimulai
    PromoService->>DB: INSERT class_promotions (batch_code, source_id, target_id, snapshot_data)
    loop Setiap ID Siswa
        PromoService->>DB: UPDATE students SET classroom_id = target_class_id, academic_year_id = new_year_id
    end
    Note over PromoService: DB::transaction Commit
    
    PromoService-->>AdminTU: "Kenaikan Kelas Berhasil Diproses (Batch #PROM-2026-001)"
    
    opt Jika Terjadi Kesalahan Administrasi (Rollback)
        AdminTU->>PromoCtrl: POST /academic/promotions/{id}/rollback
        PromoCtrl->>PromoService: rollbackPromotion(id)
        Note over PromoService: DB::transaction Dimulai
        PromoService->>DB: Baca snapshot_data dari class_promotions
        loop Setiap Siswa dalam Snapshot
            PromoService->>DB: Kembalikan classroom_id ke source_class_id semula
        end
        PromoService->>DB: UPDATE class_promotions SET status = 'rolled_back'
        Note over PromoService: DB::transaction Commit
        PromoService-->>AdminTU: "Rollback Selesai: Data Santri Dikembalikan ke Kelas Semula"
    end
```

---

## 6. Ringkasan Evaluasi Kinerja Alur Interaksi

1. **Integritas Transaksional**: Seluruh operasi yang melibatkan penulisan lebih dari 1 tabel (Jurnal KBM, Pelunasan Tagihan, Promosi Kelas) dilindungi oleh blok `DB::transaction()`.
2. **Asinkronitas Antrean**: Operasi pengiriman pesan WhatsApp dan notifikasi FCM diisolasi ke dalam sistem antrean (*queue worker*) untuk menjaga responsivitas HTTP API di bawah 200 ms.
3. **Komunikasi Real-time**: Penggunaan Laravel Reverb pada modul gerbang sekolah memberikan waktu latensi pembaruan monitor di bawah 100 ms tanpa perlu polling berulang.
