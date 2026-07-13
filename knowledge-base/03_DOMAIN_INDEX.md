# 03_DOMAIN_INDEX

| Domain | Responsibility | Owner Entity | Confidence |
| :--- | :--- | :--- | :--- |
| **Academic** | Mengatur pembagian kelas, jadwal pelajaran, mengelola guru dan siswa, serta pencatatan kehadiran kelas dan kelulusan/kenaikan kelas. | Student, Teacher, Subject, Classroom, ClassSchedule, TeachingJournal, LearningObjective, ClassPromotion | High |
| **Counseling** | Mengelola sesi konseling kesiswaan, pencatatan prestasi/penghargaan, dan pelanggaran tata tertib beserta sanksi poin. | CounselingSession, ViolationCategory, Violation, Achievement | High |
| **Employee** | Mengelola profil kepegawaian (staf non-guru) dan absensi kedatangan harian karyawan. | Staff | High |
| **Finance** | Mengelola setelan rekening keuangan, jenis tagihan SPP, pemotongan diskon siswa, laporan kas, dan impor transaksi mutasi bank. | FinanceAccount, FinanceType, StudentBill, StudentDiscount, Transaction, BillPayment | High |
| **LMS** | Platform pembelajaran online untuk pengumuman, materi/file KBM, pengumpulan tugas, dan rekap penilaian tugas oleh guru. | LmsClassroom, LmsAnnouncement, LmsMaterial, LmsAssignment, LmsSubmission | High |
| **PublicRelations** | Mengelola konten berita umum sekolah, agenda kegiatan humas, testimoni, dan direktori kerja sama kemitraan/sponsor. | News, Event, Partner | High |
| **Sarpar** | Mengatur data aset inventaris sekolah, penempatan ruang, log perawatan barang rusak, peminjaman barang, dan log penggunaan ruang. | Category, Room, Inventory, MaintenanceLog, Loan, UsageLog | High |
| **Student** | Menyediakan antarmuka dashboard terpadu bagi siswa untuk memantau nilai akademik, absensi, tagihan keuangan, BK, dan tugas mandiri. | StudentTask | High |
| **Yayasan** | Pengendali administrasi global yayasan, mengelola data unit sekolah, tahun ajaran aktif, direktori user global, kalender libur, monitoring audit trail, dan setelan radius GPS lokasi absensi. | Unit, AcademicYear, SystemSetting, Holiday | High |
