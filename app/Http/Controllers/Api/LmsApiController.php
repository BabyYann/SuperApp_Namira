<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasUnitScope;
use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\Teacher;
use App\Modules\LMS\Models\LmsAnnouncement;
use App\Modules\LMS\Models\LmsAssignment;
use App\Modules\LMS\Models\LmsClassroom;
use App\Modules\LMS\Models\LmsMaterial;
use App\Modules\LMS\Models\LmsSubmission;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LmsApiController extends Controller
{
    use HasUnitScope;

    private function teacherId(Request $request): ?int
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();
        return $teacher?->id;
    }

    private function studentId(Request $request): ?int
    {
        $student = Student::where('user_id', $request->user()->id)
            ->when($this->resolveUnitId($request), fn ($q, $u) => $q->where('unit_id', $u))
            ->first();
        return $student?->id;
    }

    // GET /lms/classrooms
    public function classrooms(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);
        $user = $request->user();
        $roles = $user->getRoleNames()->toArray();

        $query = LmsClassroom::with(['classroom', 'subject', 'teacher'])
            ->whereHas('classroom', fn ($q) => $q->where('unit_id', $unitId));

        if (in_array('teacher', $roles) || in_array('guru', $roles)) {
            $query->where('teacher_id', $this->teacherId($request));
        } elseif (in_array('siswa', $roles) || in_array('student', $roles)) {
            $student = Student::where('user_id', $user->id)
                ->where('unit_id', $unitId)
                ->first();
            $query->whereHas('classroom', fn ($q) => $q->where('id', $student?->classroom_id));
        }

        $items = $query->get()->map(fn ($c) => [
            'id' => $c->id,
            'subject' => $c->subject?->name,
            'classroom' => $c->classroom?->name,
            'teacher' => $c->teacher?->full_name,
            'status' => $c->status,
            'materials_count' => $c->materials()->count(),
            'assignments_count' => $c->assignments()->count(),
        ]);

        return response()->json(['data' => $items]);
    }

    // GET /lms/classrooms/:id
    public function classroomShow(Request $request, $id): JsonResponse
    {
        $classroom = LmsClassroom::with([
            'classroom', 'subject', 'teacher',
            'materials.files', 'assignments.submissions',
        ])->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $classroom->id,
                'subject' => $classroom->subject?->name,
                'classroom' => $classroom->classroom?->name,
                'teacher' => $classroom->teacher?->full_name,
                'status' => $classroom->status,
                'materials' => $classroom->materials->map(fn ($m) => [
                    'id' => $m->id,
                    'title' => $m->title,
                    'description' => $m->description,
                    'published_at' => $m->published_at?->format('Y-m-d H:i'),
                    'files' => $m->files->map(fn ($f) => [
                        'id' => $f->id,
                        'file_name' => $f->file_name,
                        'file_path' => $f->file_path,
                    ]),
                ]),
                'assignments' => $classroom->assignments->map(fn ($a) => [
                    'id' => $a->id,
                    'title' => $a->title,
                    'description' => $a->description,
                    'due_date' => $a->due_date?->format('Y-m-d H:i'),
                    'max_score' => $a->max_score,
                    'status' => $a->status,
                    'submissions_count' => $a->submissions->count(),
                ]),
            ],
        ]);
    }

    // GET /lms/materials/:id
    public function materialShow($id): JsonResponse
    {
        $material = LmsMaterial::with(['files', 'classroom.classroom', 'classroom.subject'])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $material->id,
                'title' => $material->title,
                'description' => $material->description,
                'published_at' => $material->published_at?->format('Y-m-d H:i'),
                'classroom' => $material->classroom?->classroom?->name,
                'subject' => $material->classroom?->subject?->name,
                'files' => $material->files->map(fn ($f) => [
                    'id' => $f->id,
                    'file_name' => $f->file_name,
                    'file_path' => $f->file_path,
                ]),
            ],
        ]);
    }

    // POST /lms/materials
    public function materialStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lms_classroom_id' => 'required|exists:lms_classrooms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $material = LmsMaterial::create([
            'lms_classroom_id' => $request->lms_classroom_id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => 'published',
            'published_at' => now(),
        ]);

        return response()->json(['data' => $material, 'message' => 'Materi berhasil dibuat'], 201);
    }

    // GET /lms/assignments/:id
    public function assignmentShow($id): JsonResponse
    {
        $assignment = LmsAssignment::with(['classroom.classroom', 'classroom.subject', 'submissions.student', 'submissions.files'])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'due_date' => $assignment->due_date?->format('Y-m-d H:i'),
                'max_score' => $assignment->max_score,
                'status' => $assignment->status,
                'classroom' => $assignment->classroom?->classroom?->name,
                'subject' => $assignment->classroom?->subject?->name,
                'submissions' => $assignment->submissions->map(fn ($s) => [
                    'id' => $s->id,
                    'student' => $s->student?->full_name,
                    'status' => $s->status,
                    'grade' => $s->grade,
                    'submitted_at' => $s->submitted_at?->format('Y-m-d H:i'),
                ]),
            ],
        ]);
    }

    // POST /lms/assignments
    public function assignmentStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lms_classroom_id' => 'required|exists:lms_classrooms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'max_score' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $assignment = LmsAssignment::create([
            'lms_classroom_id' => $request->lms_classroom_id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'max_score' => $request->max_score,
            'status' => 'published',
        ]);

        return response()->json(['data' => $assignment, 'message' => 'Tugas berhasil dibuat'], 201);
    }

    // POST /lms/assignments/:id/submit
    public function assignmentSubmit(Request $request, $id): JsonResponse
    {
        $assignment = LmsAssignment::findOrFail($id);
        $studentId = $this->studentId($request);
        if (! $studentId) {
            return response()->json(['message' => 'Profil siswa tidak ditemukan'], 403);
        }

        $validator = Validator::make($request->all(), [
            'submission_text' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $submission = LmsSubmission::updateOrCreate(
            ['lms_assignment_id' => $assignment->id, 'student_id' => $studentId],
            [
                'submission_text' => $request->submission_text,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]
        );

        return response()->json(['data' => $submission, 'message' => 'Tugas berhasil dikumpulkan'], 201);
    }

    // GET /lms/submissions/:id
    public function submissionShow($id): JsonResponse
    {
        $submission = LmsSubmission::with(['student', 'assignment', 'files', 'grader'])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $submission->id,
                'student' => $submission->student?->full_name,
                'submission_text' => $submission->submission_text,
                'status' => $submission->status,
                'grade' => $submission->grade,
                'feedback' => $submission->feedback,
                'submitted_at' => $submission->submitted_at?->format('Y-m-d H:i'),
                'graded_at' => $submission->graded_at?->format('Y-m-d H:i'),
                'graded_by' => $submission->grader?->name,
                'files' => $submission->files->map(fn ($f) => [
                    'id' => $f->id,
                    'file_name' => $f->file_name,
                    'file_path' => $f->file_path,
                ]),
            ],
        ]);
    }

    // POST /lms/submissions/:id/grade
    public function submissionGrade(Request $request, $id): JsonResponse
    {
        $submission = LmsSubmission::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'grade' => 'required|numeric',
            'feedback' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
            'status' => 'graded',
            'graded_by' => $request->user()->id,
            'graded_at' => now(),
        ]);

        return response()->json(['data' => $submission, 'message' => 'Nilai berhasil disimpan']);
    }

    // GET /lms/gradebook/:classroom
    public function gradebook(Request $request, $classroom): JsonResponse
    {
        $lmsClassroom = LmsClassroom::with(['assignments.submissions.student'])->findOrFail($classroom);

        $rows = [];
        foreach ($lmsClassroom->assignments as $assignment) {
            foreach ($assignment->submissions as $submission) {
                $rows[] = [
                    'student' => $submission->student?->full_name,
                    'assignment' => $assignment->title,
                    'grade' => $submission->grade,
                    'status' => $submission->status,
                ];
            }
        }

        return response()->json([
            'data' => [
                'classroom' => $lmsClassroom->classroom?->name,
                'subject' => $lmsClassroom->subject?->name,
                'grades' => $rows,
            ],
        ]);
    }

    // GET /lms/announcements
    public function announcements(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);
        $items = LmsAnnouncement::with(['author', 'classroom.classroom'])
            ->whereHas('classroom.classroom', fn ($q) => $q->where('unit_id', $unitId))
            ->latest()
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'content' => $a->content,
                'author' => $a->author?->name,
                'classroom' => $a->classroom?->classroom?->name,
                'created_at' => $a->created_at?->format('Y-m-d H:i'),
            ]);

        return response()->json(['data' => $items]);
    }

    // POST /lms/announcements
    public function announcementStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lms_classroom_id' => 'required|exists:lms_classrooms,id',
            'content' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $announcement = LmsAnnouncement::create([
            'lms_classroom_id' => $request->lms_classroom_id,
            'content' => $request->content,
            'author_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $announcement, 'message' => 'Pengumuman berhasil dibuat'], 201);
    }

    // GET /lms/my-tasks
    public function myTasks(Request $request): JsonResponse
    {
        $studentId = $this->studentId($request);
        if (! $studentId) {
            return response()->json(['data' => []]);
        }

        $items = LmsSubmission::with(['assignment.classroom.classroom', 'assignment.classroom.subject'])
            ->where('student_id', $studentId)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'assignment' => $s->assignment?->title,
                'classroom' => $s->assignment?->classroom?->classroom?->name,
                'subject' => $s->assignment?->classroom?->subject?->name,
                'due_date' => $s->assignment?->due_date?->format('Y-m-d H:i'),
                'status' => $s->status,
                'grade' => $s->grade,
            ]);

        return response()->json(['data' => $items]);
    }
}
