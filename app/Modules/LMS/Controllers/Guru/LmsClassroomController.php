<?php

namespace App\Modules\LMS\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Modules\LMS\Models\LmsClassroom;
use App\Modules\LMS\Models\LmsMaterial;
use App\Modules\LMS\Models\LmsMaterialFile;
use App\Modules\LMS\Models\LmsAssignment;
use App\Modules\LMS\Models\LmsSubmission;
use App\Modules\LMS\Models\LmsAnnouncement;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class LmsClassroomController extends Controller
{
    private function getTeacherId()
    {
        $user = auth()->user();
        if ($user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            return null; // Admin sees all
        }
        $teacher = Teacher::where('user_id', $user->id)->first();
        return $teacher ? $teacher->id : null;
    }

    private function getActiveAcademicYearId()
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        return $activeYear ? $activeYear->id : 1;
    }

    public function index()
    {
        $teacherId = $this->getTeacherId();
        $academicYearId = $this->getActiveAcademicYearId();

        $query = LmsClassroom::with(['classroom', 'subject', 'teacher'])
            ->where('academic_year_id', $academicYearId);

        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }

        $classrooms = $query->get();

        return Inertia::render('LMS/Guru/Classroom/Index', [
            'classrooms' => $classrooms
        ]);
    }

    public function show($id)
    {
        $classroom = LmsClassroom::with(['classroom.students.user', 'subject', 'teacher'])
            ->findOrFail($id);

        // Security check: teacher can only access their own classroom unless admin
        $teacherId = $this->getTeacherId();
        if ($teacherId && $classroom->teacher_id !== $teacherId) {
            abort(403, 'Akses Ditolak: Anda tidak mengajar di kelas ini.');
        }

        // Fetch announcements, materials, assignments
        $announcements = LmsAnnouncement::with('author')
            ->where('lms_classroom_id', $id)
            ->latest()
            ->get()
            ->map(function ($item) {
                $item->type = 'announcement';
                return $item;
            });

        $materials = LmsMaterial::with('files')
            ->where('lms_classroom_id', $id)
            ->latest()
            ->get()
            ->map(function ($item) {
                $item->type = 'material';
                return $item;
            });

        $assignments = LmsAssignment::where('lms_classroom_id', $id)
            ->latest()
            ->get()
            ->map(function ($item) {
                $item->type = 'assignment';
                return $item;
            });

        // Merge and sort stream chronologically (latest first)
        $stream = collect()
            ->concat($announcements)
            ->concat($materials)
            ->concat($assignments)
            ->sortByDesc('created_at')
            ->values();

        return Inertia::render('LMS/Guru/Classroom/Stream', [
            'lmsClassroom' => $classroom,
            'stream' => $stream,
            'students' => $classroom->classroom->students ?? []
        ]);
    }

    public function storeAnnouncement(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        LmsAnnouncement::create([
            'lms_classroom_id' => $id,
            'content' => $request->content,
            'author_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Pengumuman berhasil diposting.');
    }

    public function storeMaterial(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published',
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,zip,rar|max:10240', // 10MB max
            'file_url' => 'nullable|url',
        ]);

        DB::transaction(function () use ($request, $id) {
            $material = LmsMaterial::create([
                'lms_classroom_id' => $id,
                'title' => $request->title,
                'description' => $request->description,
                'status' => $request->status,
                'published_at' => $request->status === 'published' ? now() : null,
            ]);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('lms/materials', 'public');
                
                $extension = strtolower($file->getClientOriginalExtension());
                $fileType = 'link';
                if (in_array($extension, ['pdf'])) $fileType = 'pdf';
                elseif (in_array($extension, ['doc', 'docx'])) $fileType = 'word';
                elseif (in_array($extension, ['ppt', 'pptx'])) $fileType = 'ppt';
                elseif (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'svg'])) $fileType = 'image';

                LmsMaterialFile::create([
                    'lms_material_id' => $material->id,
                    'file_path' => 'storage/' . $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $fileType,
                ]);
            } elseif ($request->file_url) {
                $fileType = 'link';
                if (str_contains($request->file_url, 'youtube.com') || str_contains($request->file_url, 'youtu.be')) {
                    $fileType = 'youtube';
                }

                LmsMaterialFile::create([
                    'lms_material_id' => $material->id,
                    'file_path' => $request->file_url,
                    'file_name' => $request->file_url,
                    'file_type' => $fileType,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Materi berhasil ditambahkan.');
    }

    public function storeAssignment(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'max_score' => 'required|integer|min:0|max:100',
            'status' => 'required|in:draft,published',
        ]);

        LmsAssignment::create([
            'lms_classroom_id' => $id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'max_score' => $request->max_score,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Tugas berhasil dipublikasikan.');
    }

    public function submissions($classId, $assignmentId)
    {
        $classroom = LmsClassroom::with(['classroom.students', 'subject'])->findOrFail($classId);
        $assignment = LmsAssignment::findOrFail($assignmentId);

        // Fetch submissions
        $submissions = LmsSubmission::with(['student', 'files'])
            ->where('lms_assignment_id', $assignmentId)
            ->get()
            ->keyBy('student_id');

        // Prepare students submission status list
        $studentList = $classroom->classroom->students->map(function ($student) use ($submissions, $assignment) {
            $sub = $submissions->get($student->id);
            if ($sub) {
                return [
                    'student_id' => $student->id,
                    'full_name' => $student->full_name,
                    'nis' => $student->nis,
                    'status' => $sub->status,
                    'submitted_at' => $sub->submitted_at->format('Y-m-d H:i:s'),
                    'grade' => $sub->grade,
                    'feedback' => $sub->feedback,
                    'submission_id' => $sub->id,
                    'files' => $sub->files,
                    'submission_text' => $sub->submission_text
                ];
            } else {
                $isLate = now()->greaterThan($assignment->due_date);
                return [
                    'student_id' => $student->id,
                    'full_name' => $student->full_name,
                    'nis' => $student->nis,
                    'status' => $isLate ? 'missing' : 'not_submitted',
                    'submitted_at' => null,
                    'grade' => null,
                    'feedback' => null,
                    'submission_id' => null,
                    'files' => [],
                    'submission_text' => null
                ];
            }
        });

        return Inertia::render('LMS/Guru/Assignment/SubmissionList', [
            'lmsClassroom' => $classroom,
            'assignment' => $assignment,
            'submissions' => $studentList
        ]);
    }

    public function gradeSubmission(Request $request, $classId, $assignmentId, $submissionId)
    {
        $request->validate([
            'grade' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string',
        ]);

        $submission = LmsSubmission::findOrFail($submissionId);
        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
            'status' => 'returned',
            'graded_by' => auth()->id(),
            'graded_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Nilai berhasil disimpan.');
    }

    public function gradebook($id)
    {
        $classroom = LmsClassroom::with(['classroom.students', 'subject'])->findOrFail($id);
        $assignments = LmsAssignment::where('lms_classroom_id', $id)
            ->where('status', 'published')
            ->get();

        $students = $classroom->classroom->students;

        // Fetch all grades for students in this classroom
        $gradesMatrix = $students->map(function ($student) use ($assignments) {
            $studentGrades = [];
            $totalScore = 0;
            $gradedCount = 0;

            foreach ($assignments as $assign) {
                $sub = LmsSubmission::where('lms_assignment_id', $assign->id)
                    ->where('student_id', $student->id)
                    ->first();

                $score = $sub && $sub->status === 'returned' ? $sub->grade : null;
                $studentGrades[$assign->id] = $score;

                if ($score !== null) {
                    $totalScore += $score;
                    $gradedCount++;
                }
            }

            return [
                'id' => $student->id,
                'name' => $student->full_name,
                'nis' => $student->nis,
                'grades' => $studentGrades,
                'average' => $gradedCount > 0 ? round($totalScore / $gradedCount, 2) : '-'
            ];
        });

        return Inertia::render('LMS/Guru/Gradebook/Index', [
            'lmsClassroom' => $classroom,
            'assignments' => $assignments,
            'gradesMatrix' => $gradesMatrix
        ]);
    }
}
