<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendance;
use App\Models\User;
use App\Models\StudentAttendance;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Employee\Models\Staff;
use App\Modules\Finance\Models\StudentBill;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private function getActiveUnitId(Request $request): ?int
    {
        $sessionUnit = session('active_unit_id');
        if ($sessionUnit) {
            return (int) $sessionUnit;
        }

        $user = $request->user();
        $hasGlobalRole = $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan']);

        if ($hasGlobalRole) {
            $firstUnit = Unit::first();
            return $firstUnit?->id;
        }

        $firstTeamId = \DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('model_type', get_class($user))
            ->whereNotNull('team_id')
            ->value('team_id');

        return $firstTeamId ? (int) $firstTeamId : null;
    }

    private function getActiveAcademicYearId(): ?int
    {
        $year = AcademicYear::where('is_active', true)->first();
        return $year?->id;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $roles = $user->getRoleNames()->toArray();
        $unitId = $this->getActiveUnitId($request);
        $academicYearId = $this->getActiveAcademicYearId();

        $unit = $unitId ? Unit::find($unitId) : null;
        $academicYear = $academicYearId
            ? AcademicYear::find($academicYearId)
            : null;

        $data = [
            'unit' => $unit ? [
                'id' => $unit->id,
                'name' => $unit->name,
                'code' => $unit->code,
            ] : null,
            'academic_year' => $academicYear ? [
                'id' => $academicYear->id,
                'name' => $academicYear->name,
                'semester' => $academicYear->semester,
            ] : null,
            'roles' => $roles,
        ];

        $isGlobalAdmin = in_array($roles[0] ?? '', [
            'super_admin_yayasan', 'admin_yayasan', 'staff_yayasan',
        ]);

        $isUnitAdmin = in_array($roles[0] ?? '', [
            'admin_unit', 'kepala_sekolah',
        ]);

        $isTeacher = in_array($roles[0] ?? '', ['teacher', 'guru']);
        $isStudent = in_array($roles[0] ?? '', ['siswa', 'student']);
        $isStaff = in_array($roles[0] ?? '', ['finance', 'staff_admin_keuangan', 'humas_unit', 'koordinator_sarpar', 'koordinator_kurikulum']);

        if ($isGlobalAdmin || $isUnitAdmin) {
            $data['stats'] = $this->getAdminStats($unitId, $academicYearId);
            $data['recent_activity'] = $this->getRecentActivity($unitId);
        } elseif ($isTeacher) {
            $data['stats'] = $this->getTeacherStats($user->id, $unitId, $academicYearId);
        } elseif ($isStudent) {
            $data['stats'] = $this->getStudentStats($user->id, $unitId, $academicYearId);
        } else {
            $data['stats'] = $this->getStaffStats($user->id, $unitId);
        }

        return response()->json($data);
    }

    private function getAdminStats(?int $unitId, ?int $academicYearId): array
    {
        $studentQuery = Student::query();
        $teacherQuery = Teacher::query();
        $staffQuery = Staff::query();

        if ($unitId) {
            $studentQuery->where('unit_id', $unitId);
            $teacherQuery->where('unit_id', $unitId);
            $staffQuery->where('unit_id', $unitId);
        }
        if ($academicYearId) {
            $studentQuery->where('academic_year_id', $academicYearId);
        }

        $totalStudents = $studentQuery->count();
        $totalTeachers = $teacherQuery->count();
        $totalStaff = $staffQuery->count();
        $totalClasses = Classroom::where('unit_id', $unitId)->count();

        $billQuery = StudentBill::query();
        $transactionQuery = Transaction::query();

        if ($unitId) {
            $billQuery->whereHas('student', fn ($q) => $q->where('unit_id', $unitId));
            $transactionQuery->whereHas('student', fn ($q) => $q->where('unit_id', $unitId));
        }

        $activeBills = $billQuery->where('status', '!=', 'lunas')->count();
        $totalRevenue = (clone $transactionQuery)->sum('amount');
        $totalBilled = (clone $billQuery)->sum('final_amount');

        $today = now()->toDateString();
        $studentsPresentToday = StudentAttendance::where('date', $today)
            ->where('status', 'hadir')
            ->when($unitId, fn ($q) => $q->whereHas('classroom', fn ($cq) => $cq->where('unit_id', $unitId)))
            ->count();

        $teachersPresentToday = EmployeeAttendance::where('date', $today)
            ->where('check_in_time', '!=', null)
            ->whereIn('user_id', $teacherQuery->pluck('user_id')->toArray())
            ->count();

        return [
            'total_students' => $totalStudents,
            'total_teachers' => $totalTeachers,
            'total_staff' => $totalStaff,
            'total_classes' => $totalClasses,
            'active_bills' => $activeBills,
            'total_revenue' => (float) $totalRevenue,
            'total_billed' => (float) $totalBilled,
            'collection_rate' => $totalBilled > 0
                ? round(($totalRevenue / $totalBilled) * 100, 1)
                : 0,
            'students_present_today' => $studentsPresentToday,
            'teachers_present_today' => $teachersPresentToday,
        ];
    }

    private function getTeacherStats(int $userId, ?int $unitId, ?int $academicYearId): array
    {
        $teacher = Teacher::where('user_id', $userId)
            ->when($unitId, fn ($q) => $q->where('unit_id', $unitId))
            ->first();

        $today = now()->toDateString();

        $totalStudents = 0;
        $totalClasses = 0;

        if ($teacher) {
            $homeroomClass = Classroom::where('homeroom_teacher_id', $teacher->id)->first();
            if ($homeroomClass) {
                $totalStudents = Student::where('classroom_id', $homeroomClass->id)
                    ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                    ->count();
                $totalClasses = 1;
            }
        }

        $attendanceToday = EmployeeAttendance::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        return [
            'total_students' => $totalStudents,
            'total_classes' => $totalClasses,
            'attendance_today' => $attendanceToday ? [
                'status' => $attendanceToday->status,
                'check_in' => $attendanceToday->check_in_time,
                'check_out' => $attendanceToday->check_out_time,
                'late_minutes' => $attendanceToday->late_minutes,
            ] : null,
        ];
    }

    private function getStudentStats(int $userId, ?int $unitId, ?int $academicYearId): array
    {
        $student = Student::where('user_id', $userId)
            ->when($unitId, fn ($q) => $q->where('unit_id', $unitId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->first();

        if (!$student) {
            return ['attendance_today' => null, 'unpaid_bills' => 0];
        }

        $today = now()->toDateString();
        $attendanceToday = StudentAttendance::where('student_id', $student->id)
            ->where('date', $today)
            ->first();

        $unpaidBills = StudentBill::where('student_id', $student->id)
            ->where('status', '!=', 'lunas')
            ->count();

        $totalUnpaid = StudentBill::where('student_id', $student->id)
            ->where('status', '!=', 'lunas')
            ->sum('final_amount');

        return [
            'attendance_today' => $attendanceToday ? [
                'status' => $attendanceToday->status,
                'note' => $attendanceToday->note,
            ] : null,
            'unpaid_bills' => $unpaidBills,
            'total_unpaid' => (float) $totalUnpaid,
        ];
    }

    private function getStaffStats(int $userId, ?int $unitId): array
    {
        $today = now()->toDateString();
        $attendanceToday = EmployeeAttendance::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        return [
            'attendance_today' => $attendanceToday ? [
                'status' => $attendanceToday->status,
                'check_in' => $attendanceToday->check_in_time,
                'check_out' => $attendanceToday->check_out_time,
                'late_minutes' => $attendanceToday->late_minutes,
            ] : null,
        ];
    }

    private function getRecentActivity(?int $unitId): array
    {
        $recentTransactions = Transaction::with('student')
            ->when($unitId, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('unit_id', $unitId)))
            ->latest('transaction_date')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'type' => 'payment',
                'description' => 'Pembayaran dari ' . ($t->student->full_name ?? 'Unknown'),
                'amount' => (float) $t->amount,
                'date' => $t->transaction_date?->format('Y-m-d H:i'),
            ]);

        return $recentTransactions->toArray();
    }
}
