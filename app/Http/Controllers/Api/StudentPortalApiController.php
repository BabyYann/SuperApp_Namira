<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasUnitScope;
use App\Models\StudentPickupRequest;
use App\Models\StudentTask;
use App\Modules\Academic\Models\Student;
use App\Modules\Finance\Models\StudentBill;
use App\Modules\LMS\Models\LmsSubmission;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentPortalApiController extends Controller
{
    use HasUnitScope;

    private function currentStudent(Request $request): ?Student
    {
        $unitId = $this->resolveUnitId($request);
        $academicYearId = AcademicYear::where('is_active', true)->value('id');

        return Student::where('user_id', $request->user()->id)
            ->when($unitId, fn ($q) => $q->where('unit_id', $unitId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->first();
    }

    // GET /student/dashboard
    public function dashboard(Request $request): JsonResponse
    {
        $student = $this->currentStudent($request);
        if (! $student) {
            return response()->json(['message' => 'Profil siswa tidak ditemukan'], 403);
        }

        $unpaidBills = StudentBill::where('student_id', $student->id)
            ->where('status', '!=', 'lunas')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'amount' => (float) $b->final_amount,
                'due_date' => $b->due_date?->format('Y-m-d'),
                'status' => $b->status,
            ]);

        $tasks = LmsSubmission::with(['assignment'])
            ->where('student_id', $student->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->assignment?->title,
                'status' => $s->status,
                'due_date' => $s->assignment?->due_date?->format('Y-m-d'),
            ]);

        $schedule = $student->classroom?->schedules ?? [];

        return response()->json([
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'nis' => $student->nis,
                    'classroom' => $student->classroom?->name,
                ],
                'unpaid_bills' => $unpaidBills,
                'tasks' => $tasks,
                'schedule_count' => count($schedule),
            ],
        ]);
    }

    // GET /student/tasks
    public function tasks(Request $request): JsonResponse
    {
        $student = $this->currentStudent($request);
        if (! $student) {
            return response()->json(['data' => []]);
        }

        $items = LmsSubmission::with(['assignment.classroom.classroom'])
            ->where('student_id', $student->id)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'assignment_id' => $s->assignment?->id,
                'title' => $s->assignment?->title,
                'classroom' => $s->assignment?->classroom?->classroom?->name,
                'due_date' => $s->assignment?->due_date?->format('Y-m-d H:i'),
                'status' => $s->status,
                'grade' => $s->grade,
                'submitted_at' => $s->submitted_at?->format('Y-m-d H:i'),
            ]);

        return response()->json(['data' => $items]);
    }

    // POST /student/tasks/:id/complete
    public function completeTask(Request $request, $id): JsonResponse
    {
        $task = StudentTask::findOrFail($id);
        $student = $this->currentStudent($request);
        if (! $student || $task->student_id !== $student->id) {
            return response()->json(['message' => 'Tugas tidak ditemukan'], 403);
        }

        $task->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        return response()->json(['data' => $task, 'message' => 'Tugas selesai']);
    }

    // GET /student/pickup
    public function pickupIndex(Request $request): JsonResponse
    {
        $student = $this->currentStudent($request);
        if (! $student) {
            return response()->json(['data' => []]);
        }

        $items = StudentPickupRequest::where('student_id', $student->id)
            ->latest('requested_at')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'status' => $p->status,
                'latitude' => $p->latitude,
                'longitude' => $p->longitude,
                'requested_at' => $p->requested_at?->format('Y-m-d H:i'),
                'completed_at' => $p->completed_at?->format('Y-m-d H:i'),
            ]);

        return response()->json(['data' => $items]);
    }

    // POST /student/pickup
    public function pickupStore(Request $request): JsonResponse
    {
        $student = $this->currentStudent($request);
        if (! $student) {
            return response()->json(['message' => 'Profil siswa tidak ditemukan'], 403);
        }

        $validator = Validator::make($request->all(), [
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $pickup = StudentPickupRequest::create([
            'student_id' => $student->id,
            'requested_by' => $request->user()->id,
            'status' => 'pending',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'requested_at' => now(),
        ]);

        return response()->json(['data' => $pickup, 'message' => 'Permintaan jemput dikirim'], 201);
    }

    // GET /student/grades
    public function grades(Request $request): JsonResponse
    {
        $student = $this->currentStudent($request);
        if (! $student) {
            return response()->json(['data' => []]);
        }

        $items = LmsSubmission::with(['assignment.classroom.subject'])
            ->where('student_id', $student->id)
            ->where('status', 'graded')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'assignment' => $s->assignment?->title,
                'subject' => $s->assignment?->classroom?->subject?->name,
                'grade' => $s->grade,
                'feedback' => $s->feedback,
                'graded_at' => $s->graded_at?->format('Y-m-d'),
            ]);

        return response()->json(['data' => $items]);
    }

    // GET /student/schedule
    public function schedule(Request $request): JsonResponse
    {
        $student = $this->currentStudent($request);
        if (! $student || ! $student->classroom_id) {
            return response()->json(['data' => []]);
        }

        $items = $student->classroom->schedules()
            ->with(['subject', 'teacher'])
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'subject' => $s->subject?->name,
                'teacher' => $s->teacher?->full_name,
                'day' => $s->day,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'room' => $s->room,
            ]);

        return response()->json(['data' => $items]);
    }

    // GET /student/profile
    public function profile(Request $request): JsonResponse
    {
        $student = $this->currentStudent($request);
        if (! $student) {
            return response()->json(['message' => 'Profil siswa tidak ditemukan'], 403);
        }

        return response()->json([
            'data' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
                'gender' => $student->gender,
                'classroom' => $student->classroom?->name,
                'parent_name' => $student->parent_name,
                'parent_phone' => $student->parent_phone,
                'address' => $student->address,
                'photo' => $student->photo,
            ],
        ]);
    }
}
