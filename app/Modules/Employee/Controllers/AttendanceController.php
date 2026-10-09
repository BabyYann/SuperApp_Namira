<?php

namespace App\Modules\Employee\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLocation;
use App\Models\EmployeeAttendance;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        // Filter Month/Year (Default to Current)
        $month = $request->input('month', $today->month);
        $year = $request->input('year', $today->year);
        
        // Get today's attendance (for input form)
        $todayAttendance = EmployeeAttendance::where('user_id', $user->id)
            ->where('date', $today->toDateString())
            ->first();

        // Get Calendar Data (Current Month)
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $calendarRecords = EmployeeAttendance::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy('date'); // Key by YYYY-MM-DD string

        // Get history records for the selected month
        $history = EmployeeAttendance::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->latest('date')
            ->get();

        // Monthly summary statistics
        $monthStats = [
            'present' => $calendarRecords->where('status', 'present')->count(),
            'late' => $calendarRecords->where('status', 'late')->count(),
            'permit' => $calendarRecords->where('status', 'permit')->count(),
            'sick' => $calendarRecords->where('status', 'sick')->count(),
            'cuti' => $calendarRecords->where('status', 'cuti')->count(),
            'business_trip' => $calendarRecords->where('status', 'business_trip')->count(),
        ];

        // Get allowed locations
        $locations = AttendanceLocation::all(); 

        // Get live unit attendance data for radar
        $liveAttendance = $this->getLiveAttendanceData($request, $user);

        return Inertia::render('Employee/Attendance/Index', [
            'todayAttendance' => $todayAttendance,
            'history' => $history,
            'calendarData' => $calendarRecords,
            'monthStats' => $monthStats,
            'locations' => $locations,
            'currentMonth' => (int)$month,
            'currentYear' => (int)$year,
            'liveAttendance' => $liveAttendance,
            'initialTab' => $request->input('tab', 'personal'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:present,business_trip,sick,permit',
            'latitude' => 'nullable|required_if:type,present,business_trip|numeric',
            'longitude' => 'nullable|required_if:type,present,business_trip|numeric',
            'photo' => 'nullable|required_if:type,present,business_trip|string', // Base64
            'note' => 'nullable|required_if:type,business_trip,sick,permit|string',
            'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:10240', // For sick/permit (up to 10MB)
        ]);

        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // Check if already checked in
        $existing = EmployeeAttendance::where('user_id', $user->id)->where('date', $today)->first();
        if ($existing) {
            // Allow overwriting/updating if existing record was just auto-alpha/absent and user is now submitting a legitimate permit/sick/trip
            if (in_array($existing->status, ['alpha', 'absent']) && in_array($request->type, ['sick', 'permit', 'business_trip'])) {
                // Proceed to update existing alpha record
            } else {
                return redirect()->back()->with('error', 'Anda sudah melakukan input absensi hari ini.');
            }
        }

        $status = $request->type;
        $approvalStatus = 'pending'; // Default for non-present
        $locationId = null;
        $checkInTime = Carbon::now()->toTimeString();
        $photoPath = $existing?->check_in_photo;
        $permitPath = $existing?->permit_file;
        $lateMinutes = 0;

        // 1. Handle "Hadir" (WFO)
        if ($request->type === 'present') {
            // Validate Location
            $location = $this->validateLocation($request->latitude, $request->longitude);
            if (!$location) {
                return redirect()->back()->with('error', 'Anda berada di luar radius lokasi absensi.');
            }
            $locationId = $location->id;
            $approvalStatus = 'not_required'; // WFO is auto-approved if location valid

            // Late Check
            $units = $user->getUnitsAttribute(); // Ensure using the accessor
            if ($units && $units->count() > 0) {
                $unit = $units->first(); // Prioritize first unit (usually main unit)
                if ($unit->work_start_time) {
                    $scheduleStart = Carbon::parse($today . ' ' . $unit->work_start_time);
                    $tolerance = $unit->late_tolerance_minutes ?? 0;
                    $lateThreshold = $scheduleStart->copy()->addMinutes($tolerance);

                    // Check using current time
                    $now = Carbon::now();
                    
                    if ($now->gt($lateThreshold)) {
                        $status = 'late';
                        $lateMinutes = $scheduleStart->diffInMinutes($now); 
                    }
                }
            }
        }

        // 2. Handle Photo (Selfie for Present/BizTrip)
        if ($request->photo) {
            $image = $request->photo;
            if (strpos($image, 'base64') !== false) {
                $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);
                $image = str_replace(' ', '+', $image);
                $imageName = 'attendance_' . $user->id . '_' . time() . '.jpg';
                
                // Ensure directory
                if (!file_exists(storage_path('app/public/attendance_photos'))) {
                    mkdir(storage_path('app/public/attendance_photos'), 0777, true);
                }
                \Storage::disk('public')->put('attendance_photos/' . $imageName, base64_decode($image));
                $photoPath = 'attendance_photos/' . $imageName;
            }
        }

        // 3. Handle Document (For Sick/Permit)
        if ($request->hasFile('document')) {
            $permitPath = $request->file('document')->store('permits', 'public');
        }

        $dataPayload = [
            'user_id' => $user->id,
            'date' => $today,
            'check_in_time' => ($request->type === 'present' || $request->type === 'business_trip') ? $checkInTime : null, 
            'check_in_latitude' => $request->latitude,
            'check_in_longitude' => $request->longitude,
            'check_in_photo' => $photoPath,
            'permit_file' => $permitPath,
            'status' => $status,
            'note' => $request->note,
            'attendance_location_id' => $locationId,
            'approval_status' => $approvalStatus,
            'late_minutes' => $lateMinutes,
        ];

        if ($existing) {
            $existing->update($dataPayload);
            $attendanceRecord = $existing;
        } else {
            $attendanceRecord = EmployeeAttendance::create($dataPayload);
        }

        // Dispatch In-App Notification to Principals & Admins if pending approval
        if ($approvalStatus === 'pending') {
            try {
                $units = $user->getUnitsAttribute();
                $targetUnitId = ($units && $units->isNotEmpty()) ? $units->first()->id : null;
                $dateFormatted = Carbon::parse($today)->translatedFormat('d F Y');
                $statusLabel = [
                    'business_trip' => 'Dinas Luar',
                    'sick' => 'Sakit',
                    'permit' => 'Izin'
                ][$status] ?? $status;

                NotificationDispatcher::sendToRoles(
                    ['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'pembina_yayasan'],
                    $targetUnitId,
                    '📩 Pengajuan Absensi Baru',
                    "{$user->name} mengajukan {$statusLabel} pada tanggal {$dateFormatted}.",
                    'employee',
                    [
                        'attendance_id' => $attendanceRecord->id,
                        'can_quick_approve' => true,
                        'employee_name' => $user->name,
                        'status_type' => $statusLabel,
                    ]
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('In-app notification dispatch error: ' . $e->getMessage());
            }
        }

        // Automated WhatsApp Notification for Employee Check-in
        try {
            $phone = null;
            $staff = \App\Modules\Employee\Models\Staff::where('user_id', $user->id)->first();
            if ($staff) {
                $phone = $staff->phone;
            } else {
                $teacher = \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first();
                if ($teacher) {
                    $phone = $teacher->phone;
                }
            }

            if (!empty($phone)) {
                $unitName = 'Namira School';
                $units = $user->getUnitsAttribute();
                if ($units && $units->isNotEmpty()) {
                    $unitName = $units->first()->name;
                }

                $statusLabel = [
                    'present' => 'Hadir',
                    'late' => 'Terlambat',
                    'business_trip' => 'Dinas Luar',
                    'sick' => 'Sakit',
                    'permit' => 'Izin'
                ][$status] ?? $status;

                $timeFormatted = substr($checkInTime, 0, 5);
                $dateFormatted = Carbon::parse($today)->translatedFormat('d F Y');

                $message = "📋 *Konfirmasi Kehadiran Karyawan*\n\n"
                    . "Halo *{$user->name}*,\n\n"
                    . "Absensi masuk Anda telah berhasil tercatat dengan rincian berikut:\n"
                    . "• *Status*: {$statusLabel}\n"
                    . "• *Tanggal*: {$dateFormatted}\n"
                    . "• *Waktu*: {$timeFormatted} WIB\n"
                    . ($lateMinutes > 0 ? "• *Keterangan*: Terlambat {$lateMinutes} menit\n" : "")
                    . (!empty($request->note) ? "• *Catatan*: {$request->note}\n" : "") . "\n"
                    . "Selamat bekerja dan tetap semangat! 💪\n\n"
                    . "Terima kasih.\n-- *{$unitName}*";

                \App\Helpers\WhatsAppHelper::send($phone, $message);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('WA employee check-in notification error: ' . $e->getMessage());
        }

        // Send FCM Push Notification
        try {
            $fcmService = app(\App\Services\FcmService::class);
            $statusLabel = [
                'present' => 'Hadir',
                'late' => 'Terlambat',
                'business_trip' => 'Dinas Luar',
                'sick' => 'Sakit',
                'permit' => 'Izin'
            ][$status] ?? $status;

            $fcmTime = substr($checkInTime, 0, 5);
            $fcmService->sendToUser($user, 'Absensi Masuk Berhasil!', "Anda berhasil melakukan absensi ({$statusLabel}) pada pukul {$fcmTime}.");

            $units = $user->getUnitsAttribute();
            if ($units && $units->isNotEmpty()) {
                $unitIds = $units->pluck('id')->toArray();
                $adminIds = \DB::table('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->whereIn('model_has_roles.team_id', $unitIds)
                    ->whereIn('roles.name', ['admin_unit', 'kepala_sekolah', 'super_admin_yayasan', 'admin_yayasan'])
                    ->pluck('model_has_roles.model_id')
                    ->unique()
                    ->toArray();

                if (!empty($adminIds)) {
                    $admins = \App\Models\User::whereIn('id', $adminIds)
                        ->where('id', '!=', $user->id)
                        ->get();

                    foreach ($admins as $admin) {
                        $fcmService->sendToUser($admin, "Absensi Masuk: {$user->name}", "{$user->name} melakukan absensi ({$statusLabel}) pada pukul {$fcmTime}.");
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('FCM trigger check-in error: ' . $e->getMessage());
        }

        // Broadcast Event via Laravel Reverb WebSockets
        try {
            $units = $user->getUnitsAttribute();
            broadcast(new \App\Events\EmployeeCheckedIn(
                $user->name,
                $statusLabel ?? 'Hadir',
                substr($checkInTime, 0, 5),
                'check-in',
                $units ? $units->pluck('id')->toArray() : []
            ))->toOthers();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('WebSocket broadcast check-in error: ' . $e->getMessage());
        }

        // In-app notification: for sick/permit/business_trip (pending approval) → notify admin
        if ($approvalStatus === 'pending') {
            $statusLabel = [
                'business_trip' => 'Dinas Luar',
                'sick'          => 'Sakit',
                'permit'        => 'Izin',
            ][$status] ?? $status;

            $units = $user->getUnitsAttribute();
            $unitId = ($units && $units->isNotEmpty()) ? $units->first()->id : null;

            NotificationDispatcher::sendToRoles(
                ['admin_unit', 'kepala_sekolah', 'super_admin_yayasan', 'admin_yayasan'],
                $unitId,
                'Pengajuan Absensi Menunggu Persetujuan',
                "{$user->name} mengajukan absensi {$statusLabel} pada " . Carbon::parse($today)->translatedFormat('d F Y') . ". Silakan verifikasi.",
                'employee',
                ['user_id' => $user->id]
            );
        }

        return redirect()->back()->with('success', 'Data absensi berhasil dikirim.');
    }

    public function update(Request $request, EmployeeAttendance $attendance)
    {
        // Check Out (Only for Present/Late/BusinessTrip)
        // If Sick/Permit, checkout not needed usually.

        $user = Auth::user();

        if ($attendance->user_id !== $user->id) {
            $today = Carbon::today()->toDateString();
            $userTodayAttendance = EmployeeAttendance::where('user_id', $user->id)
                ->where('date', $today)
                ->first();

            if ($userTodayAttendance) {
                $attendance = $userTodayAttendance;
            } else {
                abort(403, 'Akses Ditolak: Anda hanya dapat melakukan checkout pada absensi Anda sendiri.');
            }
        }

        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // Validate Location if WFO
        if ($attendance->status === 'present' || $attendance->status === 'late') {
             $location = $this->validateLocation($request->latitude, $request->longitude);
             if (!$location) {
                return redirect()->back()->with('error', 'Anda berada di luar radius lokasi absensi.');
            }
        }
       
        $checkOutTime = Carbon::now()->toTimeString();
        $attendance->update([
            'check_out_time' => $checkOutTime,
            'check_out_latitude' => $request->latitude,
            'check_out_longitude' => $request->longitude,
        ]);

        // Automated WhatsApp Notification for Employee Check-out
        try {
            $user = Auth::user();
            $phone = null;
            $staff = \App\Modules\Employee\Models\Staff::where('user_id', $user->id)->first();
            if ($staff) {
                $phone = $staff->phone;
            } else {
                $teacher = \App\Modules\Academic\Models\Teacher::where('user_id', $user->id)->first();
                if ($teacher) {
                    $phone = $teacher->phone;
                }
            }

            if (!empty($phone)) {
                $unitName = 'Namira School';
                $units = $user->getUnitsAttribute();
                if ($units && $units->isNotEmpty()) {
                    $unitName = $units->first()->name;
                }

                $timeFormatted = substr($checkOutTime, 0, 5);
                $dateFormatted = Carbon::parse($attendance->date)->translatedFormat('d F Y');

                $message = "📋 *Konfirmasi Kepulangan Karyawan*\n\n"
                    . "Halo *{$user->name}*,\n\n"
                    . "Absensi pulang Anda telah berhasil tercatat dengan rincian berikut:\n"
                    . "• *Tanggal*: {$dateFormatted}\n"
                    . "• *Waktu Pulang*: {$timeFormatted} WIB\n\n"
                    . "Hati-hati di jalan saat pulang ke rumah. Terima kasih atas dedikasi Anda hari ini! 🙏\n\n"
                    . "-- *{$unitName}*";

                \App\Helpers\WhatsAppHelper::send($phone, $message);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('WA employee check-out notification error: ' . $e->getMessage());
        }

        // Send FCM Push Notification
        try {
            $user = Auth::user();
            $fcmService = app(\App\Services\FcmService::class);
            $fcmTime = substr($checkOutTime, 0, 5);

            $fcmService->sendToUser($user, 'Absensi Pulang Berhasil!', "Anda berhasil melakukan absensi pulang pada pukul {$fcmTime}.");

            $units = $user->getUnitsAttribute();
            if ($units && $units->isNotEmpty()) {
                $unitIds = $units->pluck('id')->toArray();
                $adminIds = \DB::table('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->whereIn('model_has_roles.team_id', $unitIds)
                    ->whereIn('roles.name', ['admin_unit', 'kepala_sekolah', 'super_admin_yayasan', 'admin_yayasan'])
                    ->pluck('model_has_roles.model_id')
                    ->unique()
                    ->toArray();

                if (!empty($adminIds)) {
                    $admins = \App\Models\User::whereIn('id', $adminIds)
                        ->where('id', '!=', $user->id)
                        ->get();

                    foreach ($admins as $admin) {
                        $fcmService->sendToUser($admin, "Absensi Pulang: {$user->name}", "{$user->name} melakukan absensi pulang pada pukul {$fcmTime}.");
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('FCM trigger check-out error: ' . $e->getMessage());
        }

        // Broadcast Event via Laravel Reverb WebSockets
        try {
            $units = $user->getUnitsAttribute();
            broadcast(new \App\Events\EmployeeCheckedIn(
                $user->name,
                'Pulang',
                substr($checkOutTime, 0, 5),
                'check-out',
                $units ? $units->pluck('id')->toArray() : []
            ))->toOthers();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('WebSocket broadcast check-out error: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Berhasil Absen Pulang!');
    }

    private function validateLocation($lat, $lng)
    {
        $user = auth()->user();
        $unitIds = [];
        if ($user) {
            $units = $user->getUnitsAttribute();
            if ($units && $units->count() > 0) {
                $unitIds = $units->pluck('id')->toArray();
            }
        }

        $query = AttendanceLocation::where('is_active', true);
        if (!empty($unitIds)) {
            $query->whereIn('unit_id', $unitIds);
        }

        $locations = $query->get();
        foreach ($locations as $location) {
            $distance = $this->haversineGreatCircleDistance($lat, $lng, $location->latitude, $location->longitude);
            if ($distance <= $location->radius) {
                return $location; // Return the object
            }
        }
        return null; // False
    }

    private function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }

    /**
     * Get real-time unit attendance data for the Live Radar chart & employee list
     */
    private function getLiveAttendanceData(Request $request, $user): array
    {
        $selectedDate = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        $isGlobalAdmin = $user && $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan']);
        
        if ($isGlobalAdmin) {
            $unitId = $request->input('unit_id') ?: (session('active_unit_id') ?: \App\Modules\Yayasan\Models\Unit::first()?->id);
            $units = \App\Modules\Yayasan\Models\Unit::orderBy('name')->get(['id', 'name', 'code']);
        } else {
            // Untuk akun guru / pegawai biasa: Mutlak terkunci ke unit tempat mereka ditugaskan
            $unitId = $user->teacher_profile?->unit_id 
                ?: ($user->staff?->unit_id 
                    ?: (\DB::table('model_has_roles')->where('model_id', $user->id)->whereNotNull('team_id')->value('team_id')
                        ?: (session('active_unit_id') ?: \App\Modules\Yayasan\Models\Unit::first()?->id)
                    )
                );
            if (!$unitId) {
                $unitId = \App\Modules\Yayasan\Models\Unit::first()?->id;
            }
            $units = []; // Guru biasa tidak menerima daftar unit lain
        }

        $activeUnit = $unitId ? \App\Modules\Yayasan\Models\Unit::find($unitId) : null;

        // Employee role names across modules
        $employeeRoleNames = [
            'teacher', 'staff', 'admin_unit', 'staff_unit',
            'wali_kelas', 'bk', 'guru', 'finance', 'kepala_sekolah',
            'koordinator_kurikulum', 'koordinator_sarpar', 'koordinator_keuangan', 'koordinator_tahfidz'
        ];

        $roleUserIds = \DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('roles.name', $employeeRoleNames)
            ->when($unitId, function ($q) use ($unitId) {
                $q->where('model_has_roles.team_id', $unitId);
            })
            ->pluck('model_id');

        $teacherUserIds = \App\Modules\Academic\Models\Teacher::when($unitId, fn ($q) => $q->where('unit_id', $unitId))->pluck('user_id');
        $staffUserIds = \App\Modules\Employee\Models\Staff::when($unitId, fn ($q) => $q->where('unit_id', $unitId))->pluck('user_id');

        $allUserIds = $roleUserIds->concat($teacherUserIds)->concat($staffUserIds)->unique()->filter();

        $employees = \App\Models\User::whereIn('id', $allUserIds)
            ->whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['siswa', 'student', 'super_admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan']);
            })
            ->with(['teacher_profile', 'staff', 'roles'])
            ->orderBy('name')
            ->get();

        $dateString = $selectedDate->toDateString();
        $todayAttendances = EmployeeAttendance::whereIn('user_id', $employees->pluck('id'))
            ->where('date', $dateString)
            ->get()
            ->keyBy('user_id');

        \Carbon\Carbon::setLocale('id');
        $dateFormatted = $selectedDate->translatedFormat('l, j F Y');
        $isToday = $selectedDate->isToday();

        $liveAttendance = [
            'date' => $dateString,
            'date_formatted' => $dateFormatted,
            'is_today' => $isToday,
            'unit_name' => $activeUnit?->name ?? 'Semua Unit',
            'unit_code' => $activeUnit?->code ?? '-',
            'unit_id' => $unitId,
            'is_global_admin' => $isGlobalAdmin,
            'units' => $units,
            'stats' => [
                'total' => $employees->count(),
                'present' => 0,
                'late' => 0,
                'permit' => 0,
                'not_checked_in' => 0,
                'attendance_count' => 0,
                'attendance_percentage' => 0,
            ],
            'lists' => [
                'not_checked_in' => [],
                'present' => [],
                'late' => [],
                'permit' => [],
                'all' => [],
            ],
        ];

        $hourlyDistribution = [
            'early' => 0,       // < 06:30
            'on_time' => 0,     // 06:30 - 07:00
            'grace' => 0,       // 07:01 - 07:15
            'late' => 0,        // > 07:15
        ];

        $checkInMinutes = [];
        $earliestCheckIn = null;
        $latestCheckIn = null;

        foreach ($employees as $emp) {
            $att = $todayAttendances->get($emp->id);

            // Track check in metrics for present/late
            if ($att && in_array($att->status, ['present', 'late']) && $att->check_in_time) {
                $timeParts = explode(':', $att->check_in_time);
                if (count($timeParts) >= 2) {
                    $minutes = ((int)$timeParts[0] * 60) + (int)$timeParts[1];
                    $checkInMinutes[] = $minutes;
                    $shortTime = sprintf('%02d:%02d', (int)$timeParts[0], (int)$timeParts[1]);

                    if ($minutes < 390) {
                        $hourlyDistribution['early']++;
                    } elseif ($minutes <= 420) {
                        $hourlyDistribution['on_time']++;
                    } elseif ($minutes <= 435) {
                        $hourlyDistribution['grace']++;
                    } else {
                        $hourlyDistribution['late']++;
                    }

                    if (!$earliestCheckIn || $minutes < $earliestCheckIn['minutes']) {
                        $earliestCheckIn = [
                            'name' => $emp->name,
                            'time' => $shortTime,
                            'minutes' => $minutes,
                        ];
                    }
                    if (!$latestCheckIn || $minutes > $latestCheckIn['minutes']) {
                        $latestCheckIn = [
                            'name' => $emp->name,
                            'time' => $shortTime,
                            'minutes' => $minutes,
                        ];
                    }
                }
            }

            // Determine jabatan
            if ($emp->staff?->position) {
                $jabatan = $emp->staff->position;
            } elseif ($emp->hasRole('kepala_sekolah')) {
                $jabatan = 'Kepala Sekolah';
            } elseif ($emp->hasRole('wali_kelas')) {
                $jabatan = 'Wali Kelas';
            } elseif ($emp->teacher_profile) {
                $jabatan = 'Guru';
            } else {
                $jabatan = ucfirst(str_replace('_', ' ', $emp->roles->first()?->name ?? 'Staff'));
            }

            $nip = $emp->teacher_profile?->nip ?? $emp->staff?->nip ?? null;

            $empData = [
                'id' => $emp->id,
                'name' => $emp->name,
                'photo' => $emp->profile_photo_url,
                'phone' => $emp->phone ?: ($emp->teacher_profile?->phone ?: ($emp->staff?->phone ?: null)),
                'jabatan' => $jabatan,
                'nip' => $nip,
                'status' => 'not_checked_in',
                'status_label' => 'Belum Absen',
                'check_in_time' => null,
                'check_out_time' => null,
                'check_in_photo' => $att?->check_in_photo,
                'check_out_photo' => $att?->check_out_photo,
                'permit_file' => $att?->permit_file,
                'late_minutes' => 0,
                'note' => null,
            ];

            if (!$att) {
                $empData['status'] = 'not_checked_in';
                $empData['status_label'] = 'Belum Absen';
                $liveAttendance['stats']['not_checked_in']++;
                $liveAttendance['lists']['not_checked_in'][] = $empData;
            } elseif ($att->status === 'present') {
                $empData['status'] = 'present';
                $empData['status_label'] = 'Tepat Waktu';
                $empData['check_in_time'] = $att->check_in_time ? substr($att->check_in_time, 0, 5) : null;
                $empData['check_out_time'] = $att->check_out_time ? substr($att->check_out_time, 0, 5) : null;
                $liveAttendance['stats']['present']++;
                $liveAttendance['lists']['present'][] = $empData;
            } elseif ($att->status === 'late') {
                $empData['status'] = 'late';
                $empData['status_label'] = 'Terlambat (' . ($att->late_minutes ?? 0) . 'm)';
                $empData['check_in_time'] = $att->check_in_time ? substr($att->check_in_time, 0, 5) : null;
                $empData['check_out_time'] = $att->check_out_time ? substr($att->check_out_time, 0, 5) : null;
                $empData['late_minutes'] = $att->late_minutes ?? 0;
                $liveAttendance['stats']['late']++;
                $liveAttendance['lists']['late'][] = $empData;
            } elseif (in_array($att->status, ['sick', 'permit', 'cuti', 'business_trip'])) {
                $statusLabels = [
                    'sick' => 'Sakit',
                    'permit' => 'Izin',
                    'cuti' => 'Cuti',
                    'business_trip' => 'Dinas Luar',
                ];
                $empData['status'] = 'permit';
                $empData['status_type'] = $att->status;
                $empData['status_label'] = $statusLabels[$att->status] ?? 'Izin';
                $empData['note'] = $att->note;
                $empData['check_in_time'] = $att->check_in_time ? substr($att->check_in_time, 0, 5) : null;
                $liveAttendance['stats']['permit']++;
                $liveAttendance['lists']['permit'][] = $empData;
            } else {
                $empData['status'] = 'not_checked_in';
                $empData['status_label'] = 'Tidak Hadir';
                $liveAttendance['stats']['not_checked_in']++;
                $liveAttendance['lists']['not_checked_in'][] = $empData;
            }

            $liveAttendance['lists']['all'][] = $empData;
        }

        $total = $liveAttendance['stats']['total'];
        $attendanceCount = $liveAttendance['stats']['present'] + $liveAttendance['stats']['late'];
        $liveAttendance['stats']['attendance_count'] = $attendanceCount;
        $attendancePercentage = $total > 0 ? round(($attendanceCount / $total) * 100, 1) : 0;
        $liveAttendance['stats']['attendance_percentage'] = $attendancePercentage;

        $presentCount = $liveAttendance['stats']['present'];
        $onTimePercentage = $attendanceCount > 0 ? round(($presentCount / $attendanceCount) * 100, 1) : 0;
        $liveAttendance['stats']['on_time_percentage'] = $onTimePercentage;

        // Quorum status (Target 95%)
        $quorumTarget = 95;
        $quorumStatus = 'attention';
        if ($attendancePercentage >= $quorumTarget) {
            $quorumStatus = 'achieved';
        } elseif ($attendancePercentage >= 70) {
            $quorumStatus = 'progress';
        }
        $liveAttendance['stats']['quorum_status'] = $quorumStatus;
        $liveAttendance['stats']['quorum_target'] = $quorumTarget;

        // Calculate average & peak
        $avgCheckInTime = null;
        if (count($checkInMinutes) > 0) {
            $avgMinutes = round(array_sum($checkInMinutes) / count($checkInMinutes));
            $avgCheckInTime = sprintf('%02d:%02d WIB', floor($avgMinutes / 60), $avgMinutes % 60);
        }

        $peakLabels = [
            'early' => 'Sebelum 06:30 WIB',
            'on_time' => '06:30 - 07:00 WIB',
            'grace' => '07:01 - 07:15 WIB',
            'late' => 'Setelah 07:15 WIB',
        ];

        $maxKey = 'on_time';
        $maxVal = $hourlyDistribution['on_time'];
        foreach ($hourlyDistribution as $k => $v) {
            if ($v > $maxVal) {
                $maxKey = $k;
                $maxVal = $v;
            }
        }

        $liveAttendance['hourly_distribution'] = $hourlyDistribution;
        $liveAttendance['insights'] = [
            'average_check_in' => $avgCheckInTime,
            'earliest' => $earliestCheckIn,
            'latest' => $latestCheckIn,
            'peak_period' => $maxVal > 0 ? $peakLabels[$maxKey] : null,
        ];

        return $liveAttendance;
    }
}
