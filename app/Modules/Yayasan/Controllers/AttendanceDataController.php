<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendance;
use App\Models\User;
use App\Modules\Yayasan\Models\Holiday;
use App\Modules\Yayasan\Models\Unit;
use App\Services\NotificationDispatcher;
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
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan'])) {
            $unitId = session('active_unit_id');
            if (!$unitId) {
                $unitId = \DB::table('model_has_roles')
                    ->where('model_id', auth()->id())
                    ->whereNotNull('team_id')
                    ->value('team_id')
                    ?? \App\Modules\Academic\Models\Teacher::where('user_id', auth()->id())->value('unit_id')
                    ?? \App\Modules\Employee\Models\Staff::where('user_id', auth()->id())->value('unit_id')
                    ?? \App\Modules\Yayasan\Models\Unit::where('principal_id', auth()->id())->value('id');
            }
        }

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $today = Carbon::today();
        $isCurrentMonth = ($month == $today->month && $year == $today->year);
        $isFutureMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth()->isFuture();

        $effectiveEnd = $isCurrentMonth ? $today : ($isFutureMonth ? $start->copy()->subDay() : $end);

        // Fetch official holidays for this month and unit
        $holidays = Holiday::where(function($q) {
                $q->where('event_type', 'libur')->orWhereNull('event_type');
            })
            ->where(function($q) use ($unitId) {
                $q->whereNull('unit_id');
                if ($unitId) {
                    $q->orWhere('unit_id', $unitId);
                }
            })
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        // Calculate elapsed work days so far:
        // Namira operates 6 days a week (Senin - Sabtu). Only Sunday (Ahad) is routine day off.
        // Official holidays from Holiday model reduce expected work days.
        $workDays = 0;
        $workDates = [];
        if ($effectiveEnd >= $start) {
            $currentDate = $start->copy();
            while ($currentDate <= $effectiveEnd) {
                $dateStr = $currentDate->toDateString();
                if (!$currentDate->isSunday() && !in_array($dateStr, $holidays)) {
                    $workDays++;
                    $workDates[] = $dateStr;
                }
                $currentDate->addDay();
            }
        }

        // Set Spatie team ID if unit is specified
        if ($unitId) {
            setPermissionsTeamId($unitId);
        }

        // 1. Resolve employee user IDs for the specified unit or global
        $employeeRoleNames = [
            'teacher', 'staff', 'admin_unit', 'staff_unit',
            'wali_kelas', 'bk', 'guru', 'finance', 'kepala_sekolah',
            'koordinator_kurikulum', 'koordinator_sarpar', 'koordinator_kesiswaan', 'koordinator_keuangan', 'koordinator_tahfidz'
        ];

        $roleUserIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('roles.name', $employeeRoleNames)
            ->when($unitId, function($q) use ($unitId) {
                $q->where('model_has_roles.team_id', $unitId);
            })
            ->pluck('model_id');

        $teacherUserIds = \App\Modules\Academic\Models\Teacher::when($unitId, fn($q) => $q->where('unit_id', $unitId))->pluck('user_id');
        $staffUserIds = \App\Modules\Employee\Models\Staff::when($unitId, fn($q) => $q->where('unit_id', $unitId))->pluck('user_id');

        $allUserIds = $roleUserIds->concat($teacherUserIds)->concat($staffUserIds)->unique()->filter();

        $employeesQuery = User::whereIn('id', $allUserIds);

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

        $recapData = $employees->map(function($user) use ($attendances, $workDays, $workDates) {
            $userAtt = $attendances->get($user->id) ?? collect();
            
            // 1. Hadir WFO: status 'present', approval_status != 'rejected'
            $hadirWfo = $userAtt->where('status', 'present')
                ->where('approval_status', '!=', 'rejected')
                ->count();

            // 2. Dinas Luar: status 'business_trip', approval_status == 'approved'
            $dinasLuar = $userAtt->where('status', 'business_trip')
                ->where('approval_status', 'approved')
                ->count();

            // 3. Terlambat: status 'late', approval_status != 'rejected'
            $terlambat = $userAtt->where('status', 'late')
                ->where('approval_status', '!=', 'rejected')
                ->count();

            // Akumulasi menit keterlambatan
            $lateMinutes = (int) $userAtt->where('status', 'late')
                ->where('approval_status', '!=', 'rejected')
                ->sum('late_minutes');

            // 4. Izin: status 'permit', approval_status == 'approved'
            $izin = $userAtt->where('status', 'permit')
                ->where('approval_status', 'approved')
                ->count();

            // 5. Sakit: status 'sick', approval_status == 'approved'
            $sakit = $userAtt->where('status', 'sick')
                ->where('approval_status', 'approved')
                ->count();

            // 6. Cuti: status 'cuti', approval_status == 'approved'
            $cuti = $userAtt->where('status', 'cuti')
                ->where('approval_status', 'approved')
                ->count();

            // 7. Menunggu Verifikasi (Pending): any submission still pending
            $pending = $userAtt->where('approval_status', 'pending')->count();

            // 8. Alpha calculation on effective work dates:
            // A work day is Alpha if there is no record, or the record was rejected.
            // Pending records do NOT count as Alpha.
            $userAttGrouped = $userAtt->groupBy(fn($att) => substr((string)$att->date, 0, 10));
            $alpha = 0;
            if ($workDays > 0) {
                foreach ($workDates as $wDate) {
                    $dayRecords = $userAttGrouped->get($wDate);
                    if (!$dayRecords || $dayRecords->isEmpty()) {
                        $alpha++;
                    } else {
                        $hasNonRejected = $dayRecords->contains(fn($r) => $r->approval_status !== 'rejected');
                        if (!$hasNonRejected) {
                            $alpha++;
                        }
                    }
                }
            }

            // 9. Fair Attendance Percentage:
            // Target Hari Wajib = max(1, Hari Kerja - (Cuti Sah + Sakit Sah))
            // Total Masuk = Hadir WFO + Dinas Luar + Terlambat (termasuk tugas weekend/libur)
            // Persentase = min(100.0, round((Total Masuk / Target Hari Wajib) * 100, 1))
            if ($workDays > 0) {
                $targetDays = max(1, $workDays - ($cuti + $sakit));
                $totalMasuk = $hadirWfo + $dinasLuar + $terlambat;
                $percentage = min(100.0, round(($totalMasuk / $targetDays) * 100, 1));
            } else {
                $percentage = 0.0;
            }

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
            $unitName = $user->teacher_profile?->unit?->name 
                ?? $user->staff?->unit?->name 
                ?? ($user->units->first()?->name ?? '-');

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
                'hadir' => $hadirWfo,
                'dinas_luar' => $dinasLuar,
                'hadir_total' => $hadirWfo + $dinasLuar,
                'terlambat' => $terlambat,
                'late_minutes' => $lateMinutes,
                'izin' => $izin,
                'sakit' => $sakit,
                'cuti' => $cuti,
                'pending' => $pending,
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
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit', 'kepala_sekolah'])) {
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
        $totalDinasLuar = $recapData->sum('dinas_luar');
        $totalTerlambat = $recapData->sum('terlambat');
        $totalLateMinutes = $recapData->sum('late_minutes');
        $totalIzin = $recapData->sum('izin');
        $totalSakit = $recapData->sum('sakit');
        $totalCuti = $recapData->sum('cuti');
        $totalPending = $recapData->sum('pending');
        $totalAlpha = $recapData->sum('alpha');
        $avgAttendance = $totalEmployees > 0 ? round($recapData->avg('percentage'), 1) : 0;

        $stats = [
            'total_employees' => $totalEmployees,
            'hadir' => $totalHadir,
            'dinas_luar' => $totalDinasLuar,
            'terlambat' => $totalTerlambat,
            'late_minutes' => $totalLateMinutes,
            'izin' => $totalIzin,
            'sakit' => $totalSakit,
            'cuti' => $totalCuti,
            'pending' => $totalPending,
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
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit', 'kepala_sekolah'])) {
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
        $totalDinasLuar = $recapData->sum('dinas_luar');
        $totalTerlambat = $recapData->sum('terlambat');
        $totalLateMinutes = $recapData->sum('late_minutes');
        $totalIzin = $recapData->sum('izin');
        $totalSakit = $recapData->sum('sakit');
        $totalCuti = $recapData->sum('cuti');
        $totalPending = $recapData->sum('pending');
        $totalAlpha = $recapData->sum('alpha');
        $avgAttendance = $totalEmployees > 0 ? round($recapData->avg('percentage'), 1) : 0;

        $response = new StreamedResponse(function () use ($recapData, $workDays, $unitName, $monthName, $totalEmployees, $totalHadir, $totalDinasLuar, $totalTerlambat, $totalLateMinutes, $totalIzin, $totalSakit, $totalCuti, $totalPending, $totalAlpha, $avgAttendance) {
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
            fputcsv($handle, ['Total Hadir WFO', $totalHadir]);
            fputcsv($handle, ['Total Dinas Luar', $totalDinasLuar]);
            fputcsv($handle, ['Total Terlambat', $totalTerlambat . ' kali (' . $totalLateMinutes . ' menit)']);
            fputcsv($handle, ['Total Izin', $totalIzin]);
            fputcsv($handle, ['Total Sakit', $totalSakit]);
            fputcsv($handle, ['Total Cuti', $totalCuti]);
            fputcsv($handle, ['Total Menunggu Verifikasi', $totalPending]);
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
                'Hadir WFO', 
                'Dinas Luar', 
                'Terlambat (Kali)', 
                'Akumulasi Telat (Menit)', 
                'Izin', 
                'Sakit', 
                'Cuti', 
                'Pending Verifikasi', 
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
                    $row['dinas_luar'],
                    $row['terlambat'],
                    $row['late_minutes'] . 'm',
                    $row['izin'],
                    $row['sakit'],
                    $row['cuti'],
                    $row['pending'],
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
                $totalDinasLuar,
                $totalTerlambat,
                $totalLateMinutes . 'm',
                $totalIzin,
                $totalSakit,
                $totalCuti,
                $totalPending,
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
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit', 'kepala_sekolah'])) {
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
        $totalDinasLuar = $recapData->sum('dinas_luar');
        $totalTerlambat = $recapData->sum('terlambat');
        $totalLateMinutes = $recapData->sum('late_minutes');
        $totalIzin = $recapData->sum('izin');
        $totalSakit = $recapData->sum('sakit');
        $totalCuti = $recapData->sum('cuti');
        $totalPending = $recapData->sum('pending');
        $totalAlpha = $recapData->sum('alpha');
        $avgAttendance = $totalEmployees > 0 ? round($recapData->avg('percentage'), 1) : 0;

        $totals = [
            'hadir' => $totalHadir,
            'dinas_luar' => $totalDinasLuar,
            'telat' => $totalTerlambat,
            'late_minutes' => $totalLateMinutes,
            'izin' => $totalIzin,
            'sakit' => $totalSakit,
            'cuti' => $totalCuti,
            'pending' => $totalPending,
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
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit', 'kepala_sekolah'])) {
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

        // Resolve unit ID for holidays
        $unitId = $user->teacher_profile?->unit_id 
            ?? $user->staff?->unit_id 
            ?? $user->units->first()?->id 
            ?? null;

        $holidays = Holiday::where(function($q) {
                $q->where('event_type', 'libur')->orWhereNull('event_type');
            })
            ->where(function($q) use ($unitId) {
                $q->whereNull('unit_id');
                if ($unitId) {
                    $q->orWhere('unit_id', $unitId);
                }
            })
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $today = Carbon::today();
        $isCurrentMonth = ($month == $today->month && $year == $today->year);
        $isFutureMonth = $start->isFuture();
        $effectiveEnd = $isCurrentMonth ? $today : ($isFutureMonth ? $start->copy()->subDay() : $end);

        $workDays = 0;
        $workDates = [];
        if ($effectiveEnd >= $start) {
            $currentDate = $start->copy();
            while ($currentDate <= $effectiveEnd) {
                $dateStr = $currentDate->toDateString();
                if (!$currentDate->isSunday() && !in_array($dateStr, $holidays)) {
                    $workDays++;
                    $workDates[] = $dateStr;
                }
                $currentDate->addDay();
            }
        }

        // Calculate stats
        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->where('approval_status', '!=', 'rejected')->count();
        $late = $attendances->where('status', 'late')->where('approval_status', '!=', 'rejected')->count();
        $sick = $attendances->where('status', 'sick')->where('approval_status', 'approved')->count();
        $permit = $attendances->where('status', 'permit')->where('approval_status', 'approved')->count();
        $cuti = $attendances->where('status', 'cuti')->where('approval_status', 'approved')->count();
        $businessTrip = $attendances->where('status', 'business_trip')->where('approval_status', 'approved')->count();
        $pending = $attendances->where('approval_status', 'pending')->count();
        $totalLateMinutes = (int) $attendances->where('status', 'late')->where('approval_status', '!=', 'rejected')->sum('late_minutes');

        $userAttGrouped = $attendances->groupBy(fn($att) => substr((string)$att->date, 0, 10));
        $alpha = 0;
        if ($workDays > 0) {
            foreach ($workDates as $wDate) {
                $dayRecords = $userAttGrouped->get($wDate);
                if (!$dayRecords || $dayRecords->isEmpty()) {
                    $alpha++;
                } else {
                    $hasNonRejected = $dayRecords->contains(fn($r) => $r->approval_status !== 'rejected');
                    if (!$hasNonRejected) {
                        $alpha++;
                    }
                }
            }
        }

        if ($workDays > 0) {
            $targetDays = max(1, $workDays - ($cuti + $sick));
            $totalMasuk = $present + $late + $businessTrip;
            $attendancePercentage = min(100.0, round(($totalMasuk / $targetDays) * 100, 1));
        } else {
            $attendancePercentage = 0.0;
        }

        // Activity logs / Giat Tugas count & details
        $activityLogs = \App\Modules\Employee\Models\EmployeeActivityLog::where('user_id', $user->id)
            ->whereBetween('activity_date', [$start->toDateString(), $end->toDateString()])
            ->latest('activity_date')
            ->get();

        $stats = [
            'total' => $total,
            'work_days' => $workDays,
            'present' => $present,
            'late' => $late,
            'sick' => $sick,
            'permit' => $permit,
            'cuti' => $cuti,
            'business_trip' => $businessTrip,
            'pending' => $pending,
            'alpha' => $alpha,
            'total_late_minutes' => $totalLateMinutes,
            'attendance_percentage' => $attendancePercentage,
            'total_activity_logs' => $activityLogs->count(),
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
            'activity_logs' => $activityLogs,
            'stats' => $stats,
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function exportIndividualPdf(Request $request, User $user)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit', 'kepala_sekolah'])) {
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

        // Resolve unit ID for holidays
        $unitId = $unit?->id 
            ?? $user->teacher_profile?->unit_id 
            ?? $user->staff?->unit_id 
            ?? null;

        $holidays = Holiday::where(function($q) {
                $q->where('event_type', 'libur')->orWhereNull('event_type');
            })
            ->where(function($q) use ($unitId) {
                $q->whereNull('unit_id');
                if ($unitId) {
                    $q->orWhere('unit_id', $unitId);
                }
            })
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $today = Carbon::today();
        $isCurrentMonth = ($month == $today->month && $year == $today->year);
        $isFutureMonth = $start->isFuture();
        $effectiveEnd = $isCurrentMonth ? $today : ($isFutureMonth ? $start->copy()->subDay() : $end);

        $workDays = 0;
        $workDates = [];
        if ($effectiveEnd >= $start) {
            $currentDate = $start->copy();
            while ($currentDate <= $effectiveEnd) {
                $dateStr = $currentDate->toDateString();
                if (!$currentDate->isSunday() && !in_array($dateStr, $holidays)) {
                    $workDays++;
                    $workDates[] = $dateStr;
                }
                $currentDate->addDay();
            }
        }

        // Stats
        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->where('approval_status', '!=', 'rejected')->count();
        $late = $attendances->where('status', 'late')->where('approval_status', '!=', 'rejected')->count();
        $sick = $attendances->where('status', 'sick')->where('approval_status', 'approved')->count();
        $permit = $attendances->where('status', 'permit')->where('approval_status', 'approved')->count();
        $cuti = $attendances->where('status', 'cuti')->where('approval_status', 'approved')->count();
        $businessTrip = $attendances->where('status', 'business_trip')->where('approval_status', 'approved')->count();
        $pending = $attendances->where('approval_status', 'pending')->count();
        $totalLateMinutes = (int) $attendances->where('status', 'late')->where('approval_status', '!=', 'rejected')->sum('late_minutes');

        $userAttGrouped = $attendances->groupBy(fn($att) => substr((string)$att->date, 0, 10));
        $alpha = 0;
        if ($workDays > 0) {
            foreach ($workDates as $wDate) {
                $dayRecords = $userAttGrouped->get($wDate);
                if (!$dayRecords || $dayRecords->isEmpty()) {
                    $alpha++;
                } else {
                    $hasNonRejected = $dayRecords->contains(fn($r) => $r->approval_status !== 'rejected');
                    if (!$hasNonRejected) {
                        $alpha++;
                    }
                }
            }
        }

        if ($workDays > 0) {
            $targetDays = max(1, $workDays - ($cuti + $sick));
            $totalMasuk = $present + $late + $businessTrip;
            $attendancePercentage = min(100.0, round(($totalMasuk / $targetDays) * 100, 1));
        } else {
            $attendancePercentage = 0.0;
        }

        $stats = [
            'total' => $total,
            'work_days' => $workDays,
            'present' => $present,
            'late' => $late,
            'sick' => $sick,
            'permit' => $permit,
            'cuti' => $cuti,
            'business_trip' => $businessTrip,
            'pending' => $pending,
            'alpha' => $alpha,
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

    public function storeManualAttendance(Request $request)
    {
        if (!auth()->user()->hasRole('super_admin_yayasan')) {
            abort(403, 'Akses Ditolak: Fitur Input/Koreksi Presensi Manual khusus untuk Super Admin Yayasan.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date|before_or_equal:today',
            'status' => 'required|in:present,business_trip,permit,sick,cuti',
            'check_in_time' => 'nullable|string',
            'check_out_time' => 'nullable|string',
            'late_minutes' => 'nullable|integer|min:0',
            'note' => 'required|string|max:500',
        ], [
            'user_id.required' => 'Pilih karyawan/guru terlebih dahulu.',
            'date.required' => 'Tanggal presensi wajib dipilih.',
            'date.before_or_equal' => 'Tanggal presensi tidak boleh di masa depan.',
            'status.required' => 'Status kehadiran wajib dipilih.',
            'note.required' => 'Alasan atau keterangan koreksi wajib diisi.',
        ]);

        $employee = User::findOrFail($request->user_id);
        $dateFormatted = Carbon::parse($request->date)->toDateString();

        // Standardize time formatting
        $checkIn = $request->check_in_time ? (strlen($request->check_in_time) === 5 ? $request->check_in_time . ':00' : $request->check_in_time) : null;
        $checkOut = $request->check_out_time ? (strlen($request->check_out_time) === 5 ? $request->check_out_time . ':00' : $request->check_out_time) : null;

        // If status is present without time, provide reasonable default school check-in time (e.g. 07:00:00 & 15:30:00)
        if ($request->status === 'present') {
            if (!$checkIn) $checkIn = '07:00:00';
            if (!$checkOut) $checkOut = '15:30:00';
        } elseif ($request->status === 'business_trip') {
            if (!$checkIn) $checkIn = '07:30:00';
            if (!$checkOut) $checkOut = '15:30:00';
        }

        $adminName = auth()->user()->name;
        $noteText = "[Koreksi Super Admin - {$adminName}]: " . trim($request->note);

        $attendance = EmployeeAttendance::updateOrCreate(
            [
                'user_id' => $employee->id,
                'date' => $dateFormatted,
            ],
            [
                'status' => $request->status,
                'check_in_time' => $checkIn,
                'check_out_time' => $checkOut,
                'late_minutes' => (int) ($request->late_minutes ?? 0),
                'approval_status' => 'approved',
                'approved_by' => auth()->id(),
                'note' => $noteText,
            ]
        );

        // Send Notification to Employee
        try {
            $statusLabel = [
                'present' => 'Hadir WFO',
                'business_trip' => 'Dinas Luar',
                'permit' => 'Izin',
                'sick' => 'Sakit',
                'cuti' => 'Cuti',
            ][$request->status] ?? $request->status;

            NotificationDispatcher::sendToUser(
                $employee,
                '📝 Presensi Dikoreksi oleh Super Admin',
                "Presensi Anda untuk tanggal " . Carbon::parse($dateFormatted)->translatedFormat('d F Y') . " telah disesuaikan menjadi '{$statusLabel}'. Catatan: {$request->note}",
                'employee',
                ['attendance_id' => $attendance->id, 'date' => $dateFormatted]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed sending manual attendance notification: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', "Presensi {$employee->name} tanggal " . Carbon::parse($dateFormatted)->translatedFormat('d M Y') . " berhasil disimpan dan disetujui.");
    }
}
