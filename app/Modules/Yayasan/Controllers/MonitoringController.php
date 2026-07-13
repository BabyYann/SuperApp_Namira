<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendance;
use App\Models\User;
use App\Modules\Academic\Models\ClassSchedule;
use App\Modules\Academic\Models\TeachingJournal;
use App\Modules\Yayasan\Models\Unit;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses halaman monitoring.');
        }

        $today = Carbon::today()->toDateString();
        $inputUnitId = $request->input('unit_id');

        // Logic: 
        // 1. If 'all' explicitly selected -> Global Mode (null)
        // 2. If valid ID provided -> Use it
        // 3. If missing (null) -> Default to Session Unit
        
        if (auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            // Global admin: respect the input
            if ($inputUnitId === 'all') {
                $unitId = null;
            } elseif ($inputUnitId) {
                $unitId = $inputUnitId;
            } else {
                $unitId = session('active_unit_id');
            }
        } else {
            // Non-global admin: always force to active unit (ignore any input override)
            $unitId = session('active_unit_id');
        }

        // --- 1. Get Total Employees from Teachers Table (Consistent with AttendanceData) ---
        $allEmployeeIds = collect();
        
        if ($unitId) {
            // Get teachers for this unit
            $teacherUserIds = \App\Modules\Academic\Models\Teacher::where('unit_id', $unitId)
                ->pluck('user_id');
            $allEmployeeIds = $allEmployeeIds->merge($teacherUserIds);
            
            // Also get staff for this unit
            $staffUserIds = \App\Modules\Employee\Models\Staff::where('unit_id', $unitId)
                ->pluck('user_id');
            $allEmployeeIds = $allEmployeeIds->merge($staffUserIds)->unique();
        } else {
            // Global mode: Get all teachers
            $teacherUserIds = \App\Modules\Academic\Models\Teacher::pluck('user_id');
            $allEmployeeIds = $teacherUserIds->unique();
        }
        
        $totalEmployees = $allEmployeeIds->count();

        // --- 2. HR / Employee Attendance Stats ---
        $employeeQuery = EmployeeAttendance::where('date', $today)
            ->whereIn('user_id', $allEmployeeIds)
            ->with('user');
        
        $attendanceStats = [
            'total_present' => (clone $employeeQuery)->whereIn('status', ['present', 'late'])->count(),
            'total_late' => (clone $employeeQuery)->where('status', 'late')->count(),
            'total_permit' => (clone $employeeQuery)->where('status', 'permit')->count(),
            'total_sick' => (clone $employeeQuery)->where('status', 'sick')->count(),
        ];

        // Top 5 Late Today
        $lateEmployees = (clone $employeeQuery)
            ->where('status', 'late')
            ->orderBy('check_in_time', 'desc') 
            ->take(5)
            ->get()
            ->map(function ($log) {
                return [
                    'name' => $log->user->name,
                    'time' => $log->check_in_time,
                    'photo' => $log->check_in_photo,
                ];
            });


        // --- 3. Academic / Journal ---
        $journalQuery = TeachingJournal::whereDate('created_at', $today);
        
        if ($unitId) {
             // TeachingJournal has direct unit_id column
             $journalQuery->where('unit_id', $unitId);
        }
        
        $totalJournalsToday = $journalQuery->count();
        
        // --- 4. User Stats ---
        // Pending Users (No roles yet, usually) - or just null verification
        $pendingUsers = User::whereNull('email_verified_at')->count();

        // Total Students (from students table)
        $totalStudents = $unitId 
            ? \App\Modules\Academic\Models\Student::where('unit_id', $unitId)->count()
            : \App\Modules\Academic\Models\Student::count();

        // Total Teachers
        $totalTeachers = $unitId 
            ? \App\Modules\Academic\Models\Teacher::where('unit_id', $unitId)->count()
            : \App\Modules\Academic\Models\Teacher::count();

        // Total Schedules Context
        // Map English Day to Indonesian
        $daysMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        $todayEnglish = Carbon::now()->format('l');
        $todayIndonesian = $daysMap[$todayEnglish] ?? $todayEnglish;

        $scheduleQuery = ClassSchedule::where('day', $todayIndonesian);
        if ($unitId) {
            $scheduleQuery->where('unit_id', $unitId);
        }
        $totalSchedulesToday = $scheduleQuery->count();

        // Calculate Alpha (Tanpa Keterangan/Belum Absen)
        // Logic: Total Employees - (Hadir + Terlambat + Izin + Sakit)
        $totalRecorded = $attendanceStats['total_present'] + $attendanceStats['total_permit'] + $attendanceStats['total_sick'];
        $totalAlpha = max(0, $totalEmployees - $totalRecorded);


        // --- 4. Student Attendance Stats ---
        $studentAttendanceQuery = \App\Models\StudentAttendance::where('date', Carbon::today()->toDateString());
        
        if ($unitId) {
            $studentAttendanceQuery->whereHas('classroom', function ($q) use ($unitId) {
                $q->where('unit_id', $unitId);
            });
        }
        
        // Get counts by status group
        $studentStatsRaw = (clone $studentAttendanceQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $studentStats = [
            'present' => $studentStatsRaw['H'] ?? 0,
            'sick' => $studentStatsRaw['S'] ?? 0,
            'permit' => $studentStatsRaw['I'] ?? 0,
            'alpha' => $studentStatsRaw['A'] ?? 0,
        ];

        // Calculate Unrecorded Students (Total Students - (Present+Sick+Permit+Alpha))
        // Note: $totalStudents is calculated above
        $totalStudentsRecorded = array_sum($studentStats);
        $studentStats['unrecorded'] = max(0, $totalStudents - $totalStudentsRecorded);


        return Inertia::render('Yayasan/Monitoring/Index', [
            'stats' => [
                'employees' => array_merge($attendanceStats, [
                    'total_employees' => $totalEmployees,
                    'total_alpha' => $totalAlpha,
                ]),
                'students' => $studentStats, // New Stats
                'academic' => [
                    'journals_today' => $totalJournalsToday,
                    'schedules_today' => $totalSchedulesToday,
                ],
                'users' => [
                    'pending' => $pendingUsers,
                    'students' => $totalStudents,
                    'teachers' => $totalTeachers,
                ]
            ],
            'lateEmployees' => $lateEmployees,
            'filters' => [
                'unit_id' => $unitId,
            ],
            'units' => Unit::select('id', 'name')->get(),
        ]);
    }
}
