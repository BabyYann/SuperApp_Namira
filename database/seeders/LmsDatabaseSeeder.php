<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\LMS\Models\LmsClassroom;
use App\Modules\LMS\Models\LmsMaterial;
use App\Modules\LMS\Models\LmsMaterialFile;
use App\Modules\LMS\Models\LmsAssignment;
use App\Modules\LMS\Models\LmsSubmission;
use App\Modules\LMS\Models\LmsSubmissionFile;
use App\Modules\LMS\Models\LmsAnnouncement;
use App\Modules\Academic\Models\Student;
use App\Models\User;

class LmsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Get Budi Guru User and Teacher
        $budiUser = User::where('email', 'budiguru@namira.school')->first();
        $budiUserId = $budiUser ? $budiUser->id : 3;

        // 1. Create Virtual Classrooms
        // Virtual Class 1: PAI (ID 2) in classroom 7 taught by Budi Guru SD (ID 2)
        $virtualClass1 = LmsClassroom::updateOrCreate(
            ['classroom_id' => 7, 'subject_id' => 2, 'teacher_id' => 2, 'academic_year_id' => 1],
            ['status' => 'active']
        );

        // Virtual Class 2: Matematika (ID 5) in classroom 7 taught by Budi Guru SD (ID 2)
        $virtualClass2 = LmsClassroom::updateOrCreate(
            ['classroom_id' => 7, 'subject_id' => 5, 'teacher_id' => 2, 'academic_year_id' => 1],
            ['status' => 'active']
        );

        // 2. Create Announcements
        LmsAnnouncement::create([
            'lms_classroom_id' => $virtualClass2->id,
            'content' => 'Selamat datang di kelas Matematika! Mohon bersiap dengan buku paket halaman 20 untuk pembelajaran besok pagi.',
            'author_id' => $budiUserId,
        ]);

        LmsAnnouncement::create([
            'lms_classroom_id' => $virtualClass2->id,
            'content' => 'Reminder: Besok kita akan mengadakan kuis kecil tentang pecahan sederhana. Pelajari catatan minggu lalu ya!',
            'author_id' => $budiUserId,
        ]);

        // 3. Create Materials
        $material1 = LmsMaterial::create([
            'lms_classroom_id' => $virtualClass2->id,
            'title' => 'Pengenalan Pecahan Sederhana',
            'description' => 'Materi ini membahas konsep dasar pecahan, pembilang, penyebut, dan menyederhanakan pecahan.',
            'status' => 'published',
            'published_at' => now()->subDays(5),
        ]);

        $material2 = LmsMaterial::create([
            'lms_classroom_id' => $virtualClass2->id,
            'title' => 'Pecahan Senilai & Desimal',
            'description' => 'Lanjutan materi pecahan senilai dan cara mengubah pecahan ke desimal.',
            'status' => 'draft',
        ]);

        // 4. Material Files
        LmsMaterialFile::create([
            'lms_material_id' => $material1->id,
            'file_path' => 'storage/materials/pecahan_sederhana.pdf',
            'file_name' => 'Modul Pecahan Sederhana.pdf',
            'file_type' => 'pdf',
        ]);

        LmsMaterialFile::create([
            'lms_material_id' => $material1->id,
            'file_path' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'file_name' => 'Video Pembelajaran Pecahan',
            'file_type' => 'youtube',
        ]);

        // 5. Assignments
        $assignment1 = LmsAssignment::create([
            'lms_classroom_id' => $virtualClass2->id,
            'title' => 'Latihan Soal Pecahan Sederhana',
            'description' => 'Kerjakan soal-soal latihan pada modul halaman 25. Tulis jawaban di kertas, foto, dan kumpulkan di sini.',
            'due_date' => now()->addDays(2),
            'max_score' => 100,
            'status' => 'published',
        ]);

        $assignment2 = LmsAssignment::create([
            'lms_classroom_id' => $virtualClass2->id,
            'title' => 'Tugas Kelompok Matematika Desimal',
            'description' => 'Membuat kelompok 3 orang untuk menyelesaikan studi kasus nilai desimal.',
            'due_date' => now()->addDays(7),
            'max_score' => 100,
            'status' => 'draft',
        ]);

        // 6. Submissions
        // Ani Siswa SD (ID 1) is in classroom 7, so she is enrolled in this virtual class
        $student1 = Student::find(1);
        if ($student1) {
            $submission = LmsSubmission::create([
                'lms_assignment_id' => $assignment1->id,
                'student_id' => $student1->id,
                'submission_text' => 'Saya sudah menyelesaikan semua soal latihan halaman 25 Pak. Berikut lampiran jawabannya.',
                'status' => 'submitted',
                'submitted_at' => now()->subDay(),
            ]);

            LmsSubmissionFile::create([
                'lms_submission_id' => $submission->id,
                'file_path' => 'storage/submissions/ani_jawaban_mtk.pdf',
                'file_name' => 'Ani_Jawaban_MTK_Hal25.pdf',
            ]);
        }

        // Rian Hidayad (ID 2) is also in classroom 7
        $student2 = Student::find(2);
        if ($student2) {
            $submission2 = LmsSubmission::create([
                'lms_assignment_id' => $assignment1->id,
                'student_id' => $student2->id,
                'submission_text' => 'Tugas matematika pecahan senilai selesai Pak.',
                'status' => 'submitted',
                'submitted_at' => now()->subHours(5),
            ]);

            LmsSubmissionFile::create([
                'lms_submission_id' => $submission2->id,
                'file_path' => 'storage/submissions/rian_jawaban_mtk.png',
                'file_name' => 'Rian_Jawaban_MTK.png',
            ]);
        }
    }
}
