<?php

namespace App\Modules\LMS\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Modules\LMS\Models\LmsClassroom;
use App\Modules\LMS\Models\LmsMaterial;
use App\Modules\LMS\Models\LmsAssignment;
use App\Modules\LMS\Models\LmsSubmission;
use App\Modules\LMS\Models\LmsSubmissionFile;
use App\Modules\LMS\Models\LmsAnnouncement;
use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class LmsStudentController extends Controller
{
    private function getStudent()
    {
        return Student::where('user_id', auth()->id())->first();
    }

    private function getActiveAcademicYearId()
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        return $activeYear ? $activeYear->id : 1;
    }

    public function index()
    {
        $student = $this->getStudent();
        if (!$student) {
            abort(403, 'Akses Ditolak: Anda tidak terdaftar sebagai siswa.');
        }

        $academicYearId = $this->getActiveAcademicYearId();

        $classrooms = LmsClassroom::with(['classroom', 'subject', 'teacher'])
            ->where('classroom_id', $student->classroom_id)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->get();

        return Inertia::render('LMS/Siswa/Classroom/Index', [
            'classrooms' => $classrooms
        ]);
    }

    public function show($id)
    {
        $student = $this->getStudent();
        $classroom = LmsClassroom::with(['classroom', 'subject', 'teacher'])
            ->findOrFail($id);

        if ($student->classroom_id !== $classroom->classroom_id) {
            abort(403, 'Akses Ditolak: Anda tidak terdaftar di kelas ini.');
        }

        // Fetch announcements, published materials, published assignments
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
            ->where('status', 'published')
            ->latest()
            ->get()
            ->map(function ($item) {
                $item->type = 'material';
                return $item;
            });

        $assignments = LmsAssignment::where('lms_classroom_id', $id)
            ->where('status', 'published')
            ->latest()
            ->get()
            ->map(function ($item) use ($student) {
                $item->type = 'assignment';
                // Check if student already submitted
                $submission = LmsSubmission::where('lms_assignment_id', $item->id)
                    ->where('student_id', $student->id)
                    ->first();
                $item->submission_status = $submission ? $submission->status : 'not_submitted';
                $item->grade = $submission ? $submission->grade : null;
                return $item;
            });

        // Merge and sort stream chronologically
        $stream = collect()
            ->concat($announcements)
            ->concat($materials)
            ->concat($assignments)
            ->sortByDesc('created_at')
            ->values();

        return Inertia::render('LMS/Siswa/Classroom/Stream', [
            'lmsClassroom' => $classroom,
            'stream' => $stream
        ]);
    }

    public function showAssignment($classId, $assignmentId)
    {
        $student = $this->getStudent();
        $classroom = LmsClassroom::with(['classroom', 'subject', 'teacher'])->findOrFail($classId);
        $assignment = LmsAssignment::findOrFail($assignmentId);

        if ($student->classroom_id !== $classroom->classroom_id) {
            abort(403, 'Akses Ditolak.');
        }

        $submission = LmsSubmission::with('files')
            ->where('lms_assignment_id', $assignmentId)
            ->where('student_id', $student->id)
            ->first();

        return Inertia::render('LMS/Siswa/Assignment/Show', [
            'lmsClassroom' => $classroom,
            'assignment' => $assignment,
            'submission' => $submission
        ]);
    }

    public function submitAssignment(Request $request, $classId, $assignmentId)
    {
        $student = $this->getStudent();
        $assignment = LmsAssignment::findOrFail($assignmentId);

        $request->validate([
            'submission_text' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,zip,rar|max:10240', // 10MB limit
        ]);

        DB::transaction(function () use ($request, $student, $assignment) {
            $isLate = now()->greaterThan($assignment->due_date);
            $status = $isLate ? 'late' : 'submitted';

            // Find existing submission or create new
            $submission = LmsSubmission::updateOrCreate(
                [
                    'lms_assignment_id' => $assignment->id,
                    'student_id' => $student->id
                ],
                [
                    'submission_text' => $request->submission_text,
                    'status' => $status,
                    'submitted_at' => now(),
                ]
            );

            if ($request->hasFile('file')) {
                // Delete old submission files if replacing
                foreach ($submission->files as $oldFile) {
                    $oldPath = str_replace('storage/', '', $oldFile->file_path);
                    Storage::disk('public')->delete($oldPath);
                    $oldFile->delete();
                }

                $file = $request->file('file');
                $path = $file->store('lms/submissions', 'public');

                LmsSubmissionFile::create([
                    'lms_submission_id' => $submission->id,
                    'file_path' => 'storage/' . $path,
                    'file_name' => $file->getClientOriginalName(),
                ]);
            }
        });

        return redirect()->route('lms.student.classrooms.show', $classId)
            ->with('success', 'Tugas berhasil dikumpulkan.');
    }

    public function grades()
    {
        $student = $this->getStudent();
        if (!$student) {
            abort(403);
        }

        $academicYearId = $this->getActiveAcademicYearId();

        // Get student's virtual classes
        $classrooms = LmsClassroom::with(['subject', 'teacher'])
            ->where('classroom_id', $student->classroom_id)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->get();

        $gradesOverview = $classrooms->map(function ($class) use ($student) {
            $assignments = LmsAssignment::where('lms_classroom_id', $class->id)
                ->where('status', 'published')
                ->get();

            $totalScore = 0;
            $gradedCount = 0;

            $items = $assignments->map(function ($assign) use ($student, &$totalScore, &$gradedCount) {
                $sub = LmsSubmission::where('lms_assignment_id', $assign->id)
                    ->where('student_id', $student->id)
                    ->first();

                $score = $sub && $sub->status === 'returned' ? $sub->grade : null;
                if ($score !== null) {
                    $totalScore += $score;
                    $gradedCount++;
                }

                return [
                    'assignment_title' => $assign->title,
                    'due_date' => $assign->due_date->format('Y-m-d H:i'),
                    'score' => $score,
                    'feedback' => $sub ? $sub->feedback : null,
                    'status' => $sub ? $sub->status : 'not_submitted'
                ];
            });

            return [
                'classroom_id' => $class->id,
                'subject_name' => $class->subject->name,
                'teacher_name' => $class->teacher->full_name,
                'average' => $gradedCount > 0 ? round($totalScore / $gradedCount, 2) : '-',
                'assignments' => $items
            ];
        });

        return Inertia::render('LMS/Siswa/Grades/Index', [
            'gradesOverview' => $gradesOverview
        ]);
    }
}
