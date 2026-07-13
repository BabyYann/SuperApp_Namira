<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StudentAttendance;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class StudentAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Logic to determine which classrooms the user can access
        // 1. Admin: All Classrooms
        // 2. Wali Kelas: Only their assigned classroom
        // 3. Guru Mata Pelajaran (Future): Based on Schedule
        
        $classrooms = Classroom::query();

        // Filter by Active Unit (Session Scoping)
        if (session()->has('active_unit_id')) {
            $classrooms->where('unit_id', session('active_unit_id'));
        }
        
        // Role-Based Filtering
        // If user is a Teacher AND NOT an Admin/SuperAdmin
        if ($user->hasRole('teacher') || $user->hasRole('wali_kelas')) {
             if (!$user->hasRole('super_admin_yayasan') && !$user->hasRole('admin_yayasan') && !$user->hasRole('admin_unit')) {
                // Determine Teacher Profile
                $teacherProfile = $user->teacher_profile;

                if ($teacherProfile) {
                    $classrooms->where('homeroom_teacher_id', $teacherProfile->id);
                } else {
                    // User has teacher role but no profile linked?
                    $classrooms->whereRaw('1 = 0');
                }
             }
        }
        
        // If regular user (not teacher/admin)
        if (!$user->hasRole('teacher') && !$user->hasRole('wali_kelas') && !$user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
             $classrooms->whereRaw('1 = 0');
        }

        $classrooms = $classrooms->with('unit')->get();

        return Inertia::render('Academic/StudentAttendance/Index', [
            'classrooms' => $classrooms,
            'user_is_homeroom' => $classrooms->isNotEmpty(), // Helper flag
        ]);
    }

    public function show(Request $request, Classroom $classroom)
    {
        $user = Auth::user();
        
        // 1. Unit Isolation Validation
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan']) && $classroom->unit_id != session('active_unit_id')) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengakses kelas dari unit lain.');
        }

        // 2. Role Authorization Validation (Only Admin or Homeroom Teacher of this class)
        if ($user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            // Allowed
        } elseif ($user->hasRole('teacher') || $user->hasRole('wali_kelas')) {
            $teacher = $user->teacher_profile;
            if (!$teacher || $classroom->homeroom_teacher_id !== $teacher->id) {
                abort(403, 'Akses Ditolak: Hanya Wali Kelas yang berhak melihat absensi kelas ini.');
            }
        } else {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk melihat data absensi.');
        }

        // 3. Handle Date Navigation
        $date = $request->input('date', Carbon::today()->toDateString());
        
        $students = Student::where('classroom_id', $classroom->id)
            ->orderBy('full_name')
            ->get();

        // 2. Fetch existing attendance for the selected date
        $attendances = StudentAttendance::where('classroom_id', $classroom->id)
            ->where('date', $date)
            ->get()
            ->keyBy('student_id');

        // 3. Fetch Monthly History (Insight)
        // Count Alpha/Izin/Sakit for current month to flag frequent absentees
        $startOfMonth = Carbon::parse($date)->startOfMonth();
        $endOfMonth = Carbon::parse($date)->endOfMonth();

        $history = StudentAttendance::selectRaw('student_id, status, count(*) as count')
            ->where('classroom_id', $classroom->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['A', 'S', 'I']) // Focus on non-present statuses
            ->groupBy('student_id', 'status')
            ->get()
            ->groupBy('student_id')
            ->map(function ($items) {
                return $items->pluck('count', 'status');
            });

        return Inertia::render('Academic/StudentAttendance/Show', [
            'classroom' => $classroom,
            'students' => $students,
            'attendances' => $attendances,
            'date' => $date,
            'history' => $history, // Pass history data
        ]);
    }

    public function store(Request $request, Classroom $classroom)
    {
        \Log::info('Attendance Store Request:', $request->all());
        
        $request->validate([
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:H,S,I,A',
            'attendances.*.note' => 'nullable|string',
        ]);

        // Security: Only Homeroom Teacher or Admin
        // Security: Only Homeroom Teacher or Admin
        $user = Auth::user();
        
        // Bypass for Admin
        if ($user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit'])) {
            // Admin allowed
        } elseif ($user->hasRole('teacher') || $user->hasRole('wali_kelas')) {
            $teacher = $user->teacher_profile;
            
            // Check if this teacher is the homeroom teacher for this class
            if (!$teacher || $classroom->homeroom_teacher_id !== $teacher->id) {
                // Determine if they are "Piket" or have special permission?
                // For now, strict: Only Homeroom.
                abort(403, 'Akses Ditolak: Hanya Wali Kelas yang berhak mengisi absensi kelas ini.');
            }
        } else {
             abort(403, 'Unauthorized action.');
        }

        // Use date from form, fallback to today
        $date = $request->input('date', Carbon::today()->toDateString());
        $userId = Auth::id();

        \Log::info("Processing attendance for Classroom {$classroom->id} on {$date} by User {$userId}");

        foreach ($request->attendances as $data) {
            $attendance = StudentAttendance::updateOrCreate(
                [
                    'student_id' => $data['student_id'],
                    'date' => $date,
                ],
                [
                    'classroom_id' => $classroom->id,
                    'status' => $data['status'],
                    'note' => $data['note'] ?? null,
                    'recorded_by' => $userId,
                ]
            );
            \Log::info("Saved attendance for Student {$data['student_id']}: {$data['status']}", $attendance->toArray());

            // Automated WhatsApp Notification via WAHA to parents for Sakit (S), Izin (I), and Alpha (A)
            try {
                $student = Student::with('unit')->find($data['student_id']);
                if ($student && !empty($student->parent_phone) && in_array($data['status'], ['S', 'I', 'A'])) {
                    $statusName = [
                        'S' => 'Sakit',
                        'I' => 'Izin',
                        'A' => 'Alpha (Tanpa Keterangan)',
                    ][$data['status']] ?? $data['status'];

                    $dateFormatted = Carbon::parse($date)->translatedFormat('d F Y');
                    $unitName = $student->unit->name ?? 'Namira School Foundation';
                    $message = "Yth. Orang Tua/Wali dari *{$student->full_name}* (Kelas: {$classroom->name}).\n\nKami menginformasikan bahwa putra/putri Anda tercatat *{$statusName}* pada tanggal *{$dateFormatted}*.\n\n" . 
                               (!empty($data['note']) ? "Keterangan: {$data['note']}\n\n" : "") .
                               "Terima kasih.\n-- *{$unitName}*";

                    \App\Helpers\WhatsAppHelper::send($student->parent_phone, $message);
                }
            } catch (\Exception $e) {
                \Log::error("Failed to send automated WA attendance notification: " . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Absensi siswa berhasil disimpan.');
    }

    public function recap(Request $request)
    {
        $unitId = session('active_unit_id');
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $classroomId = $request->input('classroom_id');

        $classrooms = Classroom::where('unit_id', $unitId)
            ->with('homeroomTeacher')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        // Get schedule days for the classroom (to check if weekend has class)
        $scheduleDays = [];
        if ($classroomId) {
            $scheduleDays = \App\Modules\Academic\Models\ClassSchedule::where('classroom_id', $classroomId)
                ->pluck('day')
                ->unique()
                ->toArray();
        }

        // Day name mapping for schedule check
        $dayNameMap = [
            'Minggu' => 0, 'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 
            'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6
        ];
        $scheduleDayNumbers = array_map(fn($day) => $dayNameMap[$day] ?? -1, $scheduleDays);

        // Generate dates array for the month
        $dates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::createFromDate($year, $month, $d);
            $dayOfWeek = $date->dayOfWeek; // 0=Sunday, 6=Saturday
            
            // Check if this day has schedule - if yes, not considered holiday
            $hasSchedule = in_array($dayOfWeek, $scheduleDayNumbers);
            $isHoliday = $date->isWeekend() && !$hasSchedule;
            
            $dates[] = [
                'day' => $d,
                'date' => $date->format('Y-m-d'),
                'dayName' => $date->locale('id')->shortDayName,
                'isWeekend' => $date->isWeekend(),
                'isHoliday' => $isHoliday, // True only if weekend AND no schedule
            ];
        }

        $recapData = [];
        $selectedClassroom = null;

        if ($classroomId) {
            $selectedClassroom = Classroom::find($classroomId);
            $students = Student::where('classroom_id', $classroomId)
                ->orderBy('full_name')
                ->get();

            // Fetch all attendances for this classroom in the given date range at once to avoid N+1 queries
            $allAttendances = StudentAttendance::where('classroom_id', $classroomId)
                ->whereBetween('date', [$startDate, $endDate])
                ->get()
                ->groupBy('student_id');

            foreach ($students as $student) {
                // Get attendances for this specific student from the pre-fetched collection
                $studentAttendances = $allAttendances->get($student->id, collect())
                    ->keyBy(fn($a) => $a->date->format('Y-m-d'));

                $summary = [
                    'H' => 0, 'S' => 0, 'I' => 0, 'A' => 0
                ];

                // Build daily status array
                $daily = [];
                foreach ($dates as $d) {
                    $att = $studentAttendances->get($d['date']);
                    $status = $att ? $att->status : null;
                    $daily[$d['day']] = $status;
                    
                    if ($status && isset($summary[$status])) {
                        $summary[$status]++;
                    }
                }

                $recapData[] = [
                    'student' => $student,
                    'summary' => $summary,
                    'daily' => $daily,
                    'percentage' => $daysInMonth > 0 ? round(($summary['H'] / max(1, array_sum($summary))) * 100, 1) : 0,
                ];
            }
        }

        return Inertia::render('Academic/StudentAttendance/Recap', [
            'classrooms' => $classrooms,
            'recapData' => $recapData,
            'dates' => $dates,
            'selectedClassroom' => $selectedClassroom,
            'filters' => [
                'month' => $month,
                'year' => $year,
                'classroom_id' => $classroomId,
            ],
            'monthName' => Carbon::createFromDate($year, $month, 1)->locale('id')->monthName,
            'daysInMonth' => $daysInMonth,
        ]);
    }

    public function export(Request $request)
    {
        $unitId = session('active_unit_id');
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $classroomId = $request->input('classroom_id');

        if (!$classroomId) {
            return redirect()->back()->withErrors(['classroom_id' => 'Pilih kelas terlebih dahulu.']);
        }

        $unit = \App\Modules\Yayasan\Models\Unit::find($unitId);
        $classroom = Classroom::find($classroomId);
        $students = Student::where('classroom_id', $classroomId)->orderBy('full_name')->get();
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;
        $monthName = Carbon::createFromDate($year, $month, 1)->locale('id')->monthName;

        // Generate dates array
        $dates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::createFromDate($year, $month, $d);
            $dates[$d] = [
                'day' => $d,
                'date' => $date->format('Y-m-d'),
                'dayName' => $date->locale('id')->shortDayName,
                'isWeekend' => $date->isWeekend(),
            ];
        }

        // Logo URL
        $logoUrl = $unit && $unit->logo ? url('storage/' . $unit->logo) : '';

        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Rekap Kehadiran ' . $classroom->name . '</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 9px; margin: 15px; }
                .header { display: flex; align-items: center; border-bottom: 2px solid #0d9488; padding-bottom: 10px; margin-bottom: 15px; }
                .logo { width: 60px; height: 60px; margin-right: 15px; object-fit: contain; }
                .header-text h1 { margin: 0; color: #0d9488; font-size: 16px; }
                .header-text p { margin: 2px 0; color: #666; font-size: 10px; }
                .info { background: #f0fdf4; padding: 8px 12px; border-radius: 8px; margin-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; font-size: 8px; }
                th { background: #0d9488; color: white; padding: 4px 2px; text-align: center; border: 1px solid #0d9488; font-size: 7px; }
                th.name { text-align: left; min-width: 120px; }
                th.day { min-width: 18px; }
                th.weekend { background: #dc2626; }
                td { padding: 3px 2px; border: 1px solid #ddd; text-align: center; }
                td.name { text-align: left; font-weight: 500; }
                tr:nth-child(even) { background: #f9f9f9; }
                .status { font-weight: bold; font-size: 7px; }
                .H { color: #16a34a; }
                .S { color: #3b82f6; }
                .I { color: #f59e0b; }
                .A { color: #dc2626; }
                .summary { font-weight: bold; }
                .good { color: #16a34a; }
                .warning { color: #f59e0b; }
                .danger { color: #dc2626; }
                .footer { text-align: center; margin-top: 15px; font-size: 8px; color: #999; }
                .legend { margin-top: 10px; font-size: 9px; }
                .legend span { margin-right: 12px; }
                @media print { body { margin: 5mm; } }
            </style>
        </head>
        <body>
            <div class="header">
                ' . ($logoUrl ? '<img src="' . $logoUrl . '" class="logo" alt="Logo">' : '') . '
                <div class="header-text">
                    <h1 style="font-size: 14px; color: #0d9488;">YAYASAN NAMIRA SCHOOL</h1>
                    <h2 style="margin: 3px 0; font-size: 12px; font-weight: bold;">' . ($unit ? $unit->name : 'Sekolah') . '</h2>
                    <p>' . ($unit ? $unit->address : '') . '</p>
                </div>
            </div>

            <div class="info">
                <strong>REKAP KEHADIRAN SISWA</strong><br>
                Kelas: <strong>' . $classroom->name . '</strong> | 
                Periode: <strong>' . $monthName . ' ' . $year . '</strong> |
                Jumlah Siswa: <strong>' . $students->count() . '</strong>
            </div>

            <table>
                <tr>
                    <th class="name">Nama Siswa</th>';
        
        // Date headers
        foreach ($dates as $d) {
            $weekendClass = $d['isWeekend'] ? 'weekend' : '';
            $html .= '<th class="day ' . $weekendClass . '">' . $d['day'] . '</th>';
        }
        
        $html .= '<th>H</th><th>S</th><th>I</th><th>A</th><th>%</th>
                </tr>';
        
        // Fetch all attendances at once to prevent N+1
        $allAttendances = StudentAttendance::where('classroom_id', $classroomId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('student_id');

        foreach ($students as $student) {
            $studentAttendances = $allAttendances->get($student->id, collect())
                ->keyBy(fn($a) => $a->date->format('Y-m-d'));

            $summary = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];
            
            $html .= '<tr><td class="name">' . htmlspecialchars($student->full_name) . '</td>';
            
            // Daily cells
            foreach ($dates as $d) {
                $att = $studentAttendances->get($d['date']);
                $status = $att ? $att->status : '-';
                if ($status !== '-' && isset($summary[$status])) {
                    $summary[$status]++;
                }
                $bgClass = $d['isWeekend'] ? 'style="background:#fef2f2;"' : '';
                $html .= '<td ' . $bgClass . '><span class="status ' . $status . '">' . $status . '</span></td>';
            }

            $total = array_sum($summary);
            $percentage = $total > 0 ? round(($summary['H'] / $total) * 100, 1) : 0;
            $percentClass = $percentage >= 90 ? 'good' : ($percentage >= 75 ? 'warning' : 'danger');

            $html .= '<td class="summary">' . $summary['H'] . '</td>
                <td class="summary">' . $summary['S'] . '</td>
                <td class="summary">' . $summary['I'] . '</td>
                <td class="summary">' . $summary['A'] . '</td>
                <td class="' . $percentClass . '">' . $percentage . '%</td>
            </tr>';
        }
        
        $html .= '</table>
            <div class="legend">
                <strong>Keterangan:</strong>
                <span class="H">H = Hadir</span>
                <span class="S">S = Sakit</span>
                <span class="I">I = Izin</span>
                <span class="A">A = Alpha</span>
                <span>- = Belum diisi</span>
            </div>
            <p class="footer">Dicetak dari SuperApp Namira pada ' . date('d/m/Y H:i') . '</p>
        </body>
        </html>';

        $filename = 'rekap_kehadiran_' . $classroom->name . '_' . $monthName . '_' . $year . '.html';
        
        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}

