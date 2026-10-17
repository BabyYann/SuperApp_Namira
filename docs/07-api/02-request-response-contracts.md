# SuperApp Namira — Kontrak Permintaan & Respons API (API Contracts)

**Dokumen Spesifikasi Teknis Skema & Payload Data**  
**ID Dokumen:** `DOC-API-02-CONTRACTS`  
**Status Audit:** `IMPLEMENTED`  
**Format Komunikasi:** `JSON (application/json)` & `multipart/form-data`  
**Terkait:** [`01-endpoint-catalog.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/07-api/01-endpoint-catalog.md), [`03-authentication-and-errors.md`](file:///E:/Rian%20hidayad/SuperApp_Namira/docs/07-api/03-authentication-and-errors.md)

---

## 1. Standar Amplop JSON Global (JSON Response Envelope)

Seluruh respons sukses dari REST API SuperApp Namira dibungkus dalam amplop terpadu (*consistent envelope*):

```json
{
  "success": true,
  "message": "Deskripsi hasil operasi dalam Bahasa Indonesia",
  "data": {},
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 68
  }
}
```

- `success` (`boolean`): Penanda status keberhasilan operasi (`true`/`false`).
- `message` (`string`): Pesan ramah pengguna yang siap dirender pada UI / Toast.
- `data` (`object` atau `array`): Muatan payload data utama.
- `meta` (`object`, opsional): Metadata paginasi jika respons berbentuk daftar koleksi data.

---

## 2. Kontrak Endpoint Kritis

### 2.1 Autentikasi Pengguna: `POST /api/login`

**Header Permintaan:**
```http
Content-Type: application/json
Accept: application/json
```

**Payload Permintaan (Request Body):**
```json
{
  "email": "ahmad.guru@namira.sch.id",
  "password": "Password123!"
}
```
*(Dapat pula menggunakan `username` sebagai pengganti `email`)*.

**Contoh Respons Sukses (HTTP 200 OK):**
```json
{
  "success": true,
  "message": "Autentikasi berhasil. Selamat datang kembali!",
  "data": {
    "token": "1|sanctum_token_string_here_abc123xyz...",
    "user": {
      "id": 14,
      "name": "Ustadz Ahmad, S.Pd.",
      "email": "ahmad.guru@namira.sch.id",
      "phone": "081234567890",
      "profile_photo_url": "https://namira.sch.id/storage/photos/guru-ahmad.jpg",
      "active_unit": {
        "id": 1,
        "name": "SMA Namira",
        "slug": "sma-namira",
        "unit_type": "sekolah"
      },
      "roles": [
        "teacher",
        "wali_kelas"
      ]
    }
  }
}
```

---

### 2.2 Presensi Pegawai: `POST /api/attendance/check-in`

Digunakan oleh aplikasi mobile saat guru/staf melakukan presensi masuk di area kampus.

**Header Permintaan:**
```http
Content-Type: multipart/form-data
Accept: application/json
Authorization: Bearer <token_sanctum>
```

**Form Data Permintaan:**
- `latitude` (`numeric`, required): Titik koordinat lintang GPS (contoh: `-7.76543210`).
- `longitude` (`numeric`, required): Titik koordinat bujur GPS (contoh: `113.23456780`).
- `photo` (`file`, required): Berkas gambar swafoto (JPEG, PNG, maksimal 3MB).

**Contoh Respons Sukses (HTTP 200 OK):**
```json
{
  "success": true,
  "message": "Presensi masuk berhasil dicatat tepat waktu.",
  "data": {
    "attendance_id": 412,
    "date": "2026-10-16",
    "check_in": "06:48:15",
    "status": "present",
    "late_minutes": 0,
    "distance_meters": 18.5,
    "location_name": "Kampus Utama Namira"
  }
}
```

---

### 2.3 Jadwal Mengajar Guru: `GET /api/academic/schedules`

**Parameter Kueri URL (Query String):**
- `day_of_week` (`string`, opsional): Filter hari (`senin`, `selasa`, `rabu`, `kamis`, `jumat`, `sabtu`).
- `unit_id` (`integer`, opsional): Konteks unit jika multi-unit.

**Contoh Respons Sukses (HTTP 200 OK):**
```json
{
  "success": true,
  "message": "Jadwal mengajar berhasil dimuat.",
  "data": [
    {
      "id": 58,
      "day_of_week": "kamis",
      "start_time": "07:30",
      "end_time": "09:00",
      "room": "Ruang Lab Multimedia 1",
      "subject": {
        "id": 12,
        "code": "MAT-SMA-11",
        "name": "Matematika Peminatan"
      },
      "classroom": {
        "id": 4,
        "name": "XI-IPA-1",
        "level": "11"
      },
      "is_joint_class": true,
      "joint_classrooms": [
        {"id": 5, "name": "XI-IPA-2"}
      ],
      "journal_status": "unfilled"
    }
  ]
}
```

---

### 2.4 Tagihan Siswa: `GET /api/finance/bills`

**Parameter Kueri:**
- `status` (`string`, opsional): Filter status (`unpaid`, `paid`, `partial`).
- `page` (`integer`, opsional): Nomor halaman.

**Contoh Respons Sukses (HTTP 200 OK):**
```json
{
  "success": true,
  "message": "Daftar tagihan berhasil diambil.",
  "data": [
    {
      "id": 2045,
      "bill_code": "INV-2026-10-0042",
      "student": {
        "id": 85,
        "nis": "202410042",
        "name": "Muhammad Farhan",
        "classroom": "XI-IPA-1"
      },
      "finance_type": {
        "name": "SPP Bulanan Oktober 2026",
        "is_monthly": true
      },
      "amount": 600000.00,
      "discount_amount": 100000.00,
      "paid_amount": 0.00,
      "remaining_amount": 500000.00,
      "status": "unpaid",
      "due_date": "2026-10-10",
      "payment_proof_url": null
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 12,
    "per_page": 15,
    "total": 178
  }
}
```

---

### 2.5 Pengumpulan Tugas LMS: `POST /api/lms/assignments/{id}/submit`

**Header Permintaan:**
```http
Content-Type: multipart/form-data
Authorization: Bearer <token_sanctum>
```

**Form Data:**
- `files[]` (`file[]`, required): Satu atau beberapa berkas dokumen jawaban (PDF, DOCX, ZIP, maks 10MB per berkas).
- `notes` (`string`, optional): Catatan tambahan santri untuk guru pengampu.

**Contoh Respons Sukses (HTTP 201 Created):**
```json
{
  "success": true,
  "message": "Tugas berhasil dikumpulkan tepat waktu.",
  "data": {
    "submission_id": 892,
    "assignment_id": 115,
    "submitted_at": "2026-10-16T14:22:10Z",
    "status": "submitted",
    "score": null,
    "files": [
      {
        "id": 1401,
        "file_name": "Laporan_Praktikum_Farhan.pdf",
        "file_url": "https://namira.sch.id/storage/lms/submissions/892/Laporan_Praktikum_Farhan.pdf",
        "file_size": 2415900
      }
    ]
  }
}
```

---

### 2.6 Penjemputan Santri Mandiri: `POST /api/student/pickup`

**Payload Permintaan:**
```json
{
  "student_id": 85,
  "pickup_person_name": "Rahmat Hidayat (Paman)",
  "relationship": "Keluarga / Paman Kandung",
  "phone": "085299887766",
  "notes": "Dijemput menggunakan motor Honda Vario Nopol N 4521 AA"
}
```

**Contoh Respons Sukses (HTTP 201 Created):**
```json
{
  "success": true,
  "message": "Permintaan penjemputan berhasil dikirim ke pos keamanan gerbang.",
  "data": {
    "pickup_request_id": 310,
    "student_name": "Muhammad Farhan",
    "status": "pending",
    "requested_at": "2026-10-16T15:05:00Z"
  }
}
```
