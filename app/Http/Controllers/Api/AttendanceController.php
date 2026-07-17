<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendance;
use App\Models\AttendanceLocation;
use App\Modules\Employee\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $attendance = EmployeeAttendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        return response()->json([
            'date' => $today,
            'attendance' => $attendance ? $this->formatAttendance($attendance) : null,
        ]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:present,business_trip,sick,permit',
            'latitude' => 'required_if:type,present,business_trip|nullable|numeric',
            'longitude' => 'required_if:type,present,business_trip|nullable|numeric',
            'photo' => 'nullable|string',
            'note' => 'nullable|string',
            'attendance_location_id' => 'nullable|integer|exists:attendance_locations,id',
        ]);

        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $existing = EmployeeAttendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Anda sudah melakukan absensi hari ini.',
            ], 422);
        }

        $type = $request->type;
        $status = 'absent';
        $approvalStatus = 'not_required';
        $lateMinutes = null;

        if ($type === 'present') {
            $location = $this->validateLocation($request->latitude, $request->longitude, $user);

            if (!$location) {
                return response()->json([
                    'message' => 'Anda berada di luar radius lokasi absensi.',
                ], 422);
            }

            $status = 'present';
            $lateMinutes = $this->calculateLateMinutes($user);

            if ($lateMinutes !== null && $lateMinutes > 0) {
                $status = 'late';
            }
        } elseif ($type === 'business_trip') {
            $status = 'business_trip';
            $approvalStatus = 'pending';
        } elseif ($type === 'sick') {
            $status = 'sick';
            $approvalStatus = 'pending';
        } elseif ($type === 'permit') {
            $status = 'permit';
            $approvalStatus = 'pending';
        }

        $photoPath = null;
        if ($request->photo) {
            $photoPath = $this->savePhoto($request->photo, $user->id, 'check_in');
        }

        $attendance = EmployeeAttendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'check_in_time' => now()->format('H:i:s'),
            'check_in_latitude' => $request->latitude,
            'check_in_longitude' => $request->longitude,
            'check_in_photo' => $photoPath,
            'status' => $status,
            'late_minutes' => $lateMinutes,
            'note' => $request->note,
            'attendance_location_id' => $type === 'present' ? ($this->validateLocation($request->latitude, $request->longitude, $user)?->id) : null,
            'approval_status' => $approvalStatus,
        ]);

        return response()->json([
            'message' => 'Berhasil melakukan check-in.',
            'attendance' => $this->formatAttendance($attendance),
        ]);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $attendance = EmployeeAttendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json([
                'message' => 'Anda belum melakukan check-in hari ini.',
            ], 422);
        }

        if ($attendance->check_out_time) {
            return response()->json([
                'message' => 'Anda sudah melakukan check-out hari ini.',
            ], 422);
        }

        if (in_array($attendance->status, ['present', 'late'])) {
            $location = $this->validateLocation($request->latitude, $request->longitude, $user);
            if (!$location) {
                return response()->json([
                    'message' => 'Anda berada di luar radius lokasi absensi.',
                ], 422);
            }
        }

        $photoPath = null;
        if ($request->photo) {
            $photoPath = $this->savePhoto($request->photo, $user->id, 'check_out');
        }

        $attendance->update([
            'check_out_time' => now()->format('H:i:s'),
            'check_out_latitude' => $request->latitude,
            'check_out_longitude' => $request->longitude,
            'check_out_photo' => $photoPath,
        ]);

        return response()->json([
            'message' => 'Berhasil melakukan check-out.',
            'attendance' => $this->formatAttendance($attendance->fresh()),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'page' => 'nullable|integer|min:1',
        ]);

        $user = $request->user();
        $month = $request->month ? Carbon::parse($request->month . '-01') : Carbon::now()->startOfMonth();

        $attendances = EmployeeAttendance::where('user_id', $user->id)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->orderByDesc('date')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $attendances->getCollection()->map(fn ($a) => $this->formatAttendance($a)),
            'current_page' => $attendances->currentPage(),
            'last_page' => $attendances->lastPage(),
            'total' => $attendances->total(),
            'per_page' => $attendances->perPage(),
        ]);
    }

    public function locations(Request $request): JsonResponse
    {
        $user = $request->user();
        $unitIds = $user->getUnitsAttribute()->pluck('id')->toArray();

        $locations = AttendanceLocation::where('is_active', true)
            ->whereIn('unit_id', $unitIds)
            ->get()
            ->map(fn ($loc) => [
                'id' => $loc->id,
                'name' => $loc->name,
                'latitude' => (float) $loc->latitude,
                'longitude' => (float) $loc->longitude,
                'radius' => $loc->radius,
                'unit_id' => $loc->unit_id,
            ]);

        return response()->json(['data' => $locations]);
    }

    private function validateLocation(?float $lat, ?float $lng, $user): ?AttendanceLocation
    {
        if ($lat === null || $lng === null) return null;

        $unitIds = $user->getUnitsAttribute()->pluck('id')->toArray();

        $locations = AttendanceLocation::where('is_active', true)
            ->whereIn('unit_id', $unitIds)
            ->get();

        foreach ($locations as $loc) {
            $distance = $this->haversineDistance($lat, $lng, (float) $loc->latitude, (float) $loc->longitude);
            if ($distance <= $loc->radius) {
                return $loc;
            }
        }

        return null;
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function calculateLateMinutes($user): ?int
    {
        $staff = Staff::where('user_id', $user->id)->first();
        if (!$staff) return null;

        $unit = $staff->unit;
        if (!$unit || !$unit->work_start_time) return null;

        $workStart = Carbon::parse($unit->work_start_time);
        $tolerance = $unit->late_tolerance_minutes ?? 0;
        $limitTime = $workStart->copy()->addMinutes($tolerance);
        $now = Carbon::now();

        if ($now->greaterThan($limitTime)) {
            return (int) $workStart->diffInMinutes($now);
        }

        return 0;
    }

    private function savePhoto(string $base64Data, int $userId, string $type): string
    {
        $data = $base64Data;
        if (str_contains($base64Data, ',')) {
            $data = explode(',', $base64Data)[1];
        }

        $decoded = base64_decode($data);
        $filename = "attendance_photos/attendance_{$userId}_{$type}_" . time() . ".jpg";

        Storage::disk('public')->put($filename, $decoded);

        return $filename;
    }

    private function formatAttendance(EmployeeAttendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'date' => $attendance->date,
            'check_in_time' => $attendance->check_in_time,
            'check_out_time' => $attendance->check_out_time,
            'status' => $attendance->status,
            'late_minutes' => $attendance->late_minutes,
            'note' => $attendance->note,
            'approval_status' => $attendance->approval_status,
            'rejection_reason' => $attendance->rejection_reason,
            'check_in_photo' => $attendance->check_in_photo
                ? Storage::disk('public')->url($attendance->check_in_photo)
                : null,
            'check_out_photo' => $attendance->check_out_photo
                ? Storage::disk('public')->url($attendance->check_out_photo)
                : null,
            'duration' => $this->calculateDuration($attendance),
        ];
    }

    private function calculateDuration(EmployeeAttendance $attendance): ?string
    {
        if (!$attendance->check_in_time || !$attendance->check_out_time) return null;

        $checkIn = Carbon::parse($attendance->check_in_time);
        $checkOut = Carbon::parse($attendance->check_out_time);
        $diff = $checkIn->diff($checkOut);

        return sprintf('%02d:%02d', $diff->h + ($diff->i / 60 >= 30 ? 1 : 0), $diff->i % 60 >= 30 ? 30 : 0);
    }
}
