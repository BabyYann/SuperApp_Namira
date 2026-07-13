<?php

namespace App\Modules\Employee\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLocation;
use App\Models\EmployeeAttendance;
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

        // Get history (for list view, maybe last 10)
        $history = EmployeeAttendance::where('user_id', $user->id)
            ->where('date', '<=', $today->toDateString())
            ->latest('date')
            ->take(10)
            ->get();

        // Get allowed locations
        $locations = AttendanceLocation::all(); 

        return Inertia::render('Employee/Attendance/Index', [
            'todayAttendance' => $todayAttendance,
            'history' => $history,
            'calendarData' => $calendarRecords,
            'locations' => $locations,
            'currentMonth' => (int)$month,
            'currentYear' => (int)$year,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:present,business_trip,sick,permit',
            'latitude' => 'required_if:type,present,business_trip|numeric',
            'longitude' => 'required_if:type,present,business_trip|numeric',
            'photo' => 'required_if:type,present,business_trip|nullable|string', // Base64
            'note' => 'required_if:type,business_trip,sick,permit|nullable|string',
            'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // For sick/permit
        ]);

        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // Check if already checked in
        $existing = EmployeeAttendance::where('user_id', $user->id)->where('date', $today)->first();
        if ($existing) {
            return redirect()->back()->with('error', 'Anda sudah melakukan input hari ini.');
        }

        $status = $request->type;
        $approvalStatus = 'pending'; // Default for non-present
        $locationId = null;
        $checkInTime = Carbon::now()->toTimeString();
        $photoPath = null;
        $permitPath = null;
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
                        $lateMinutes = $lateThreshold->diffInMinutes($now); // Calculate shortage
                        // Improving logic: calculate difference from START TIME or THRESHOLD? 
                        // Usually logic is: Late 1 minute > Threshold -> Late 1 minute relative to Start Time + Tolerance OR Start Time?
                        // Let's count from Start Time + Tolerance to be fair, or strictly from Start Time?
                        // User said: "Jika absen > 07.15 -> hitung berapa menit telatnya". 07.15 probably includes tolerance.
                        // Let's diff from Schedule Start to capture full lateness if they miss the tolerance window. 
                        // Standard HR practice: If you miss tolerance, you are late from the beginning (07.00).
                        
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

        EmployeeAttendance::create([
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
        ]);

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
                    ->whereIn('roles.name', ['admin_unit', 'super_admin_yayasan', 'admin_yayasan'])
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

        return redirect()->back()->with('success', 'Data absensi berhasil dikirim.');
    }

    public function update(Request $request, EmployeeAttendance $attendance)
    {
        // Check Out (Only for Present/Late/BusinessTrip)
        // If Sick/Permit, checkout not needed usually.

        if ($attendance->user_id !== Auth::id()) {
            abort(403, 'Akses Ditolak: Anda hanya dapat melakukan checkout pada absensi Anda sendiri.');
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
                    ->whereIn('roles.name', ['admin_unit', 'super_admin_yayasan', 'admin_yayasan'])
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
}
