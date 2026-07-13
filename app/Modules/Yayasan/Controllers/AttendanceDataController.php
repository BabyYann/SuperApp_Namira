<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendance;
use App\Models\User;
use App\Modules\Yayasan\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceDataController extends Controller
{
    private function getRecapData(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        
        // Active Unit resolution: default to request unit_id (if provided), fallback to active_unit_id session, otherwise null (global)
        if ($request->has('unit_id')) {
            $unitId = $request->input('unit_id');
        } else {
            $unitId = session('active_unit_id');
        }

        if ($unitId === 'all') {
            $unitId = null;
        }

        // Force unit restriction for non-global admins
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $unitId = session('active_unit_id');
        }

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // Calculate work days in month (excluding weekends)
        $workDays = 0;
        $currentDate = $start->copy();
        while ($currentDate <= $end) {
            if (!$currentDate->isWeekend()) {
                $workDays++;
            }
            $currentDate->addDay();
        }

        // 1. Get base query for Employees
        $employeesQuery = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['teacher', 'staff', 'admin_unit', 'staff_unit']);
        });

        // Filter by unit
        if ($unitId) {
            $employeesQuery->where(function($q) use ($unitId) {
                $q->whereHas('teacher_profile', function($sub) use ($unitId) {
                    $sub->where('unit_id', $unitId);
                })->orWhereHas('staff', function($sub) use ($unitId) {
                    $sub->where('unit_id', $unitId);
                });
            });
        }

        // Filter by search (name or NIP)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $employeesQuery->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhereHas('teacher_profile', function($sub) use ($search) {
                      $sub->where('nip', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('staff', function($sub) use ($search) {
                      $sub->where('nip', 'like', '%' . $search . '%');
                  });
            });
        }

        // Filter by subject_id
        if ($request->filled('subject_id')) {
            $subjectId = $request->input('subject_id');
            $employeesQuery->whereHas('teacher_profile.schedules', function($sub) use ($subjectId) {
                $sub->where('subject_id', $subjectId);
            });
        }

        $employees = $employeesQuery->with(['roles', 'teacher_profile.schedules.subject', 'staff', 'teacher_profile.unit', 'staff.unit'])->get();

        // 2. Fetch attendances for date range
        $attendances = EmployeeAttendance::whereIn('user_id', $employees->pluck('id'))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('user_id');

        $recapData = $employees->map(function($user) use ($attendances, $workDays) {
            $userAtt = $attendances->get($user->id) ?? collect();
            
            $hadir = $userAtt->where('status', 'present')->count();
            $terlambat = $userAtt->where('status', 'late')->count();
            $izin = $userAtt->where('status', 'permit')->count();
            $sakit = $userAtt->where('status', 'sick')->count();
            $cuti = $userAtt->where('status', 'cuti')->count();
            $dinasLuar = $userAtt->where('status', 'business_trip')->count();
            
            $totalRecorded = $hadir + $terlambat + $izin + $sakit + $cuti + $dinasLuar;
            $alpha = max(0, $workDays - $totalRecorded);
            
            $presentDays = $hadir + $terlambat + $dinasLuar;
            $percentage = $workDays > 0 ? round(($presentDays / $workDays) * 100, 1) : 0;

            // NIP/NUPTK
            $nip = $user->teacher_profile?->nip ?? $user->staff?->nip ?? '-';
            
            // Jabatan (Role / Position)
            $roleName = $user->roles->first()?->name ?? 'Staff';
            $jabatan = $user->staff?->position ?? ($roleName === 'teacher' ? 'Guru' : ucfirst(str_replace('_', ' ', $roleName)));
            
            // Mata Pelajaran
            $subjectsList = '-';
            if ($user->teacher_profile && $user->teacher_profile->schedules) {
                $subjNames = $user->teacher_profile->schedules->map(fn($s) => $s->subject?->name)->filter()->unique();
                if ($subjNames->count() > 0) {
                    $subjectsList = $subjNames->take(3)->implode(', ') . ($subjNames->count() > 3 ? '...' : '');
                }
            }

            // Unit Name
            $unitName = $user->teacher_profile?->unit?->name ?? $user->staff?->unit?->name ?? '-';

            return [
                'id' => $user->id,
                'name' => $user->name,
                'user_name' => $user->name,
                'photo' => $user->profile_photo_url,
                'nip' => $nip,
                'jabatan' => $jabatan,
                'role' => $roleName,
                'subjects' => $subjectsList,
                'unit_name' => $unitName,
                'hadir' => $hadir + $dinasLuar,
                'terlambat' => $terlambat,
                'izin' => $izin,
                'sakit' => $sakit,
                'cuti' => $cuti,
                'alpha' => $alpha,
                'percentage' => $percentage,
            ];
        });

        // Filter by Status Kehadiran percentage
        if ($request->filled('attendance_status')) {
            $attStatus = $request->input('attendance_status');
            if ($attStatus === 'excellent') {
                $recapData = $recapData->where('percentage', '>=', 90);
            } elseif ($attStatus === 'good') {
                $recapData = $recapData->where('percentage', '>=', 75)->where('percentage', '<', 90);
            } elseif ($attStatus === 'poor') {
                $recapData = $recapData->where('percentage', '<', 75);
            }
        }

        // Sort recapData
        $recapData = $recapData->sortByDesc('percentage')->values();

        return [
            'recapData' => $recapData,
            'workDays' => $workDays,
            'start' => $start,
            'end' => $end,
            'unitId' => $unitId,
        ];
    }

    public function index(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses data absensi karyawan.');
        }

        $units = Unit::all();
        $subjects = \App\Modules\Academic\Models\Subject::all();
        
        $recapInfo = $this->getRecapData($request);
        $recapData = $recapInfo['recapData'];
        $workDays = $recapInfo['workDays'];
        $unitId = $recapInfo['unitId'];
        
        // Month & Year default value
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        // Calculate summary cards stats
        $totalEmployees = $recapData->count();
        $totalHadir = $recapData->sum('hadir');
        $totalTerlambat = $recapData->sum('terlambat');
        $totalIzin = $recapData->sum('izin');
        $totalSakit = $recapData->sum('sakit');
        $totalCuti = $recapData->sum('cuti');
        $totalAlpha = $recapData->sum('alpha');
        $avgAttendance = $totalEmployees > 0 ? round($recapData->avg('percentage'), 1) : 0;

        $stats = [
            'total_employees' => $totalEmployees,
            'hadir' => $totalHadir,
            'terlambat' => $totalTerlambat,
            'izin' => $totalIzin,
            'sakit' => $totalSakit,
            'cuti' => $totalCuti,
            'alpha' => $totalAlpha,
            'avg_attendance' => $avgAttendance,
        ];

        return Inertia::render('Yayasan/Attendance/Data', [
            'units' => $units,
            'subjects' => $subjects,
            'recapData' => $recapData,
            'stats' => $stats,
            'filters' => [
                'month' => (int)$month,
                'year' => (int)$year,
                'unit_id' => is_numeric($unitId) ? (int)$unitId : 'all',
                'search' => $request->input('search', ''),
                'subject_id' => $request->input('subject_id', ''),
                'attendance_status' => $request->input('attendance_status', ''),
            ],
            'workDays' => $workDays,
        ]);
    }

    public function export(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengekspor data absensi.');
        }

        $recapInfo = $this->getRecapData($request);
        $recapData = $recapInfo['recapData'];
        $workDays = $recapInfo['workDays'];
        $start = $recapInfo['start'];
        $unitId = $recapInfo['unitId'];

        $unit = $unitId ? Unit::find($unitId) : null;
        $unitName = $unit ? $unit->name : 'Semua Unit (Global)';
        $monthName = $start->translatedFormat('F Y');

        // Summary Calculations
        $totalEmployees = $recapData->count();
        $totalHadir = $recapData->sum('hadir');
        $totalTerlambat = $recapData->sum('terlambat');
        $totalIzin = $recapData->sum('izin');
        $totalSakit = $recapData->sum('sakit');
        $totalCuti = $recapData->sum('cuti');
        $totalAlpha = $recapData->sum('alpha');
        $avgAttendance = $totalEmployees > 0 ? round($recapData->avg('percentage'), 1) : 0;

        $response = new StreamedResponse(function () use ($recapData, $workDays, $unitName, $monthName, $totalEmployees, $totalHadir, $totalTerlambat, $totalIzin, $totalSakit, $totalCuti, $totalAlpha, $avgAttendance) {
            $handle = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Metadata Headers
            fputcsv($handle, ['LAPORAN REKAP KEHADIRAN KARYAWAN & GURU']);
            fputcsv($handle, ['Sistem SuperApp Yayasan Namira']);
            fputcsv($handle, ['Periode', $monthName]);
            fputcsv($handle, ['Unit Kerja', $unitName]);
            fputcsv($handle, ['Hari Kerja Efektif', $workDays . ' Hari']);
            fputcsv($handle, []);

            // Statistics Info
            fputcsv($handle, ['RINGKASAN STATISTIK']);
            fputcsv($handle, ['Total Karyawan/Guru', $totalEmployees . ' Orang']);
            fputcsv($handle, ['Rata-rata Kehadiran', $avgAttendance . '%']);
            fputcsv($handle, ['Total Hadir', $totalHadir]);
            fputcsv($handle, ['Total Terlambat', $totalTerlambat]);
            fputcsv($handle, ['Total Izin', $totalIzin]);
            fputcsv($handle, ['Total Sakit', $totalSakit]);
            fputcsv($handle, ['Total Cuti', $totalCuti]);
            fputcsv($handle, ['Total Alpha', $totalAlpha]);
            fputcsv($handle, []);

            // Headers
            fputcsv($handle, [
                'No', 
                'NIP / NUPTK', 
                'Nama Karyawan', 
                'Jabatan', 
                'Mata Pelajaran Diajar', 
                'Unit Kerja', 
                'Total Hari Kerja', 
                'Hadir', 
                'Terlambat', 
                'Izin', 
                'Sakit', 
                'Cuti', 
                'Alpha', 
                'Persentase Kehadiran'
            ]);

            $no = 1;
            foreach ($recapData as $row) {
                fputcsv($handle, [
                    $no++,
                    $row['nip'],
                    $row['name'],
                    $row['jabatan'],
                    $row['subjects'],
                    $row['unit_name'],
                    $workDays,
                    $row['hadir'],
                    $row['terlambat'],
                    $row['izin'],
                    $row['sakit'],
                    $row['cuti'],
                    $row['alpha'],
                    $row['percentage'] . '%'
                ]);
            }
            
            // Grand Total Row
            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTAL',
                '',
                $totalEmployees . ' Karyawan',
                '',
                '',
                '',
                '',
                $totalHadir,
                $totalTerlambat,
                $totalIzin,
                $totalSakit,
                $totalCuti,
                $totalAlpha,
                $avgAttendance . '%'
            ]);

            fclose($handle);
        });

        $filename = "Rekap_Kehadiran_" . str_replace(' ', '_', $unitName) . "_" . $start->format('M_Y') . ".csv";

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    public function exportPdf(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengekspor PDF absensi.');
        }

        $recapInfo = $this->getRecapData($request);
        $recapData = $recapInfo['recapData'];
        $workDays = $recapInfo['workDays'];
        $start = $recapInfo['start'];
        $unitId = $recapInfo['unitId'];

        $unit = $unitId ? Unit::with('principal.teacher_profile')->find($unitId) : null;
        
        $totalEmployees = $recapData->count();
        $totalHadir = $recapData->sum('hadir');
        $totalTerlambat = $recapData->sum('terlambat');
        $totalIzin = $recapData->sum('izin');
        $totalSakit = $recapData->sum('sakit');
        $totalCuti = $recapData->sum('cuti');
        $totalAlpha = $recapData->sum('alpha');
        $avgAttendance = $totalEmployees > 0 ? round($recapData->avg('percentage'), 1) : 0;

        $totals = [
            'hadir' => $totalHadir,
            'telat' => $totalTerlambat,
            'izin' => $totalIzin,
            'sakit' => $totalSakit,
            'cuti' => $totalCuti,
            'alpha' => $totalAlpha,
        ];

        $monthName = $start->translatedFormat('F Y');
        $printDate = Carbon::now()->translatedFormat('d F Y, H:i');
        $printedBy = auth()->user()->name;

        // Build active filters array
        $activeFilters = [];
        if ($unit) {
            $activeFilters['Unit'] = $unit->name;
        } else {
            $activeFilters['Unit'] = 'Semua Unit (Global)';
        }
        if ($request->filled('search')) {
            $activeFilters['Pencarian'] = '"' . $request->input('search') . '"';
        }
        if ($request->filled('subject_id')) {
            $subj = \App\Modules\Academic\Models\Subject::find($request->input('subject_id'));
            if ($subj) $activeFilters['Mata Pelajaran'] = $subj->name;
        }
        if ($request->filled('attendance_status')) {
            $statusMapping = ['excellent' => 'Sangat Baik (≥90%)', 'good' => 'Baik (75% - 89%)', 'poor' => 'Kurang (<75%)'];
            $activeFilters['Tingkat Kehadiran'] = $statusMapping[$request->input('attendance_status')] ?? $request->input('attendance_status');
        }

        $stats = [
            'total_employees' => $totalEmployees,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.attendance-report', [
            'unit' => $unit,
            'recapData' => $recapData,
            'totals' => $totals,
            'workDays' => $workDays,
            'avgAttendance' => $avgAttendance,
            'monthName' => $monthName,
            'printDate' => $printDate,
            'printedBy' => $printedBy,
            'activeFilters' => $activeFilters,
            'stats' => $stats,
        ]);

        $pdf->setPaper('A4', 'landscape');

        $filename = "Rekap_Kehadiran_" . ($unit ? str_replace(' ', '_', $unit->name) : 'Global') . "_" . $start->format('M_Y') . ".pdf";
        
        return $pdf->download($filename);
    }

    public function employeeHistory(Request $request, User $user)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            return response()->json(['error' => 'Akses Ditolak'], 403);
        }

        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // Get attendances
        $attendances = EmployeeAttendance::with(['location'])
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date', 'asc')
            ->get();

        // Calculate stats
        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->count();
        $late = $attendances->where('status', 'late')->count();
        $sick = $attendances->where('status', 'sick')->count();
        $permit = $attendances->where('status', 'permit')->count();
        $businessTrip = $attendances->where('status', 'business_trip')->count();
        $totalLateMinutes = $attendances->sum('late_minutes');

        $activeDays = $present + $late + $sick + $permit + $businessTrip;
        $attendancePercentage = $activeDays > 0 ? round((($present + $late) / $activeDays) * 100) : 100;

        $stats = [
            'total' => $total,
            'present' => $present,
            'late' => $late,
            'sick' => $sick,
            'permit' => $permit,
            'business_trip' => $businessTrip,
            'total_late_minutes' => $totalLateMinutes,
            'attendance_percentage' => $attendancePercentage,
        ];

        return response()->json([
            'employee' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name ?? '-',
                'photo' => $user->profile_photo_url,
            ],
            'attendances' => $attendances,
            'stats' => $stats,
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function exportIndividualPdf(Request $request, User $user)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak.');
        }

        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // Get unit
        $units = $user->getUnitsAttribute();
        $unit = $units && $units->count() > 0 ? $units->first() : null;

        // Get attendances
        $attendances = EmployeeAttendance::where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date', 'asc')
            ->get();

        // Stats
        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->count();
        $late = $attendances->where('status', 'late')->count();
        $sick = $attendances->where('status', 'sick')->count();
        $permit = $attendances->where('status', 'permit')->count();
        $businessTrip = $attendances->where('status', 'business_trip')->count();
        $totalLateMinutes = $attendances->sum('late_minutes');

        $activeDays = $present + $late + $sick + $permit + $businessTrip;
        $attendancePercentage = $activeDays > 0 ? round((($present + $late) / $activeDays) * 100) : 100;

        $stats = [
            'total' => $total,
            'present' => $present,
            'late' => $late,
            'sick' => $sick,
            'permit' => $permit,
            'business_trip' => $businessTrip,
            'total_late_minutes' => $totalLateMinutes,
            'attendance_percentage' => $attendancePercentage,
        ];

        $monthName = $start->translatedFormat('F Y');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.individual-attendance-report', [
            'unit' => $unit,
            'employee' => $user,
            'attendances' => $attendances,
            'stats' => $stats,
            'monthName' => $monthName,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = "Rapor_Absensi_" . str_replace(' ', '_', $user->name) . "_" . $start->format('M_Y') . ".pdf";
        
        return $pdf->download($filename);
    }
}
