<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\ClassSchedule;
use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\TeachingJournal;
use App\Modules\Academic\Models\Classroom;
use App\Models\EmployeeAttendance;
use App\Models\StudentAttendance;
use App\Models\User;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Http\Controllers\Api\Traits\HasUnitScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicApiController extends Controller
{
    use HasUnitScope;

    private function getActiveYearId(): ?int
    {
        $year = AcademicYear::where('is_active', true)->first();
        return $year?->id;
    }

    public function schedules(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);
        $user = $request->user();
        $activeYear = AcademicYear::where('is_active', true)->first();

        $isTeacher = $user->hasAnyRole(['teacher', 'guru']);
        $selectedClassroomId = $request->input('classroom_id');

        $classrooms = Classroom::where('unit_id', $unitId)
            ->orderBy('level')
            ->orderBy('name')
            ->get(['id', 'name', 'level']);

        $result = [
            'classrooms' => $classrooms,
            'academic_year' => $activeYear ? ['id' => $activeYear->id, 'name' => $activeYear->name] : null,
        ];

        if ($selectedClassroomId) {
            $schedules = ClassSchedule::where('unit_id', $unitId)
                ->where('classroom_id', $selectedClassroomId)
                ->with(['subject:id,name,code', 'teacher:id,full_name'])
                ->get()
                ->groupBy('day')
                ->map(fn ($items) => $items->map(fn ($s) => [
                    'id' => $s->id,
                    'subject' => $s->subject?->name,
                    'teacher' => $s->teacher?->full_name,
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                ]));

            $result['schedule'] = $schedules;
        }

        if ($isTeacher) {
            $teacherProfile = $user->teacher_profile ?? null;
            if ($teacherProfile) {
                $personalSchedules = ClassSchedule::where('teacher_id', $teacherProfile->id)
                    ->with(['subject:id,name', 'classroom:id,name'])
                    ->get()
                    ->groupBy('day')
                    ->map(fn ($items) => $items->map(fn ($s) => [
                        'id' => $s->id,
                        'subject' => $s->subject?->name,
                        'classroom' => $s->classroom?->name,
                        'start_time' => $s->start_time,
                        'end_time' => $s->end_time,
                    ]));

                $result['personal_schedule'] = $personalSchedules;
            }
        }

        return response()->json($result);
    }

    public function students(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);
        $academicYearId = $request->input('academic_year_id') ?? $this->getActiveYearId();

        $query = Student::with(['classroom:id,name,level', 'academicYear:id,name'])
            ->where('unit_id', $unitId);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender);
        }

        if ($classroomId = $request->input('classroom_id')) {
            $query->where('classroom_id', $classroomId);
        }

        $students = $query->orderBy('full_name')
            ->paginate($request->input('per_page', 30));

        $classrooms = Classroom::where('unit_id', $unitId)
            ->orderBy('level')->orderBy('name')
            ->get(['id', 'name', 'level']);

        return response()->json([
            'data' => $students->getCollection(),
            'current_page' => $students->currentPage(),
            'last_page' => $students->lastPage(),
            'total' => $students->total(),
            'per_page' => $students->perPage(),
            'classrooms' => $classrooms,
        ]);
    }

    public function journals(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = $request->input('date', now()->toDateString());

        $teacherProfile = $user->teacher_profile ?? null;
        if (!$teacherProfile) {
            return response()->json(['schedules' => [], 'date' => $date]);
        }

        $dayName = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
            5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
        ][now()->parse($date)->dayOfWeek];

        $schedules = ClassSchedule::where('teacher_id', $teacherProfile->id)
            ->where('day', $dayName)
            ->with(['classroom:id,name', 'subject:id,name'])
            ->withCount(['journals as journal_count' => function ($q) use ($date) {
                $q->where('date', $date);
            }])
            ->get()
            ->map(function ($s) use ($date) {
                $journal = $s->journals->firstWhere('date', $date);
                return [
                    'id' => $s->id,
                    'classroom' => $s->classroom?->name,
                    'subject' => $s->subject?->name,
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                    'is_filled' => $journal !== null,
                    'journal_id' => $journal?->id,
                ];
            });

        return response()->json([
            'schedules' => $schedules,
            'date' => $date,
        ]);
    }

    public function studentAttendance(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);
        $date = $request->input('date', now()->toDateString());
        $classroomId = $request->input('classroom_id');

        if (!$classroomId) {
            $classrooms = Classroom::where('unit_id', $unitId)
                ->get(['id', 'name', 'level']);

            return response()->json([
                'classrooms' => $classrooms,
                'date' => $date,
            ]);
        }

        $students = Student::where('classroom_id', $classroomId)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'nis', 'gender']);

        $attendances = StudentAttendance::where('classroom_id', $classroomId)
            ->where('date', $date)
            ->get()
            ->keyBy('student_id')
            ->map(fn ($a) => [
                'status' => $a->status,
                'note' => $a->note,
            ]);

        return response()->json([
            'students' => $students,
            'attendances' => $attendances,
            'date' => $date,
            'classroom_id' => $classroomId,
        ]);
    }

    public function studentAttendanceRecap(Request $request): JsonResponse
    {
        $unitId = $this->resolveUnitId($request);
        $month = $request->input('month', now()->format('Y-m'));
        $classroomId = $request->input('classroom_id');

        $classrooms = Classroom::where('unit_id', $unitId)
            ->get(['id', 'name', 'level']);

        if (!$classroomId) {
            return response()->json([
                'classrooms' => $classrooms,
                'month' => $month,
            ]);
        }

        $year = (int) substr($month, 0, 4);
        $mon = (int) substr($month, 5, 2);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mon, $year);

        $students = Student::where('classroom_id', $classroomId)
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $attendances = StudentAttendance::where('classroom_id', $classroomId)
            ->whereYear('date', $year)
            ->whereMonth('date', $mon)
            ->get();

        $recapData = $students->map(function ($student) use ($attendances, $daysInMonth) {
            $studentAtts = $attendances->where('student_id', $student->id);
            $summary = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];
            $daily = [];

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dateStr = sprintf('%02d', $d);
                $att = $studentAtts->firstWhere('date', $dateStr);
                $status = $att?->status ?? '-';
                $daily[$d] = $status;
                if (isset($summary[$status])) $summary[$status]++;
            }

            $totalDays = array_sum($summary);
            $percentage = $totalDays > 0 ? round(($summary['H'] / $totalDays) * 100, 1) : 0;

            return [
                'student' => ['id' => $student->id, 'name' => $student->full_name],
                'summary' => $summary,
                'daily' => $daily,
                'percentage' => $percentage,
            ];
        });

        $monthNames = [
            '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        return response()->json([
            'recap_data' => $recapData,
            'classrooms' => $classrooms,
            'month' => $month,
            'month_name' => $monthNames[$mon],
            'days_in_month' => $daysInMonth,
            'classroom_id' => $classroomId,
        ]);
    }
}
