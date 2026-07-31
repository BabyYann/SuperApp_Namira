<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendance;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceApprovalController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses persetujuan absensi.');
        }

        $unitId = session('active_unit_id');

        $query = EmployeeAttendance::with('user')
            ->where('approval_status', 'pending');

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $query->whereHas('user', function ($q) use ($unitId) {
                $q->whereHas('roles', function ($sub) use ($unitId) {
                     $sub->whereRaw("model_has_roles.model_id = users.id AND model_has_roles.team_id = ?", [$unitId]);
                });
            });
        } else {
            if ($unitId) {
                $query->whereHas('user', function ($q) use ($unitId) {
                    $q->whereHas('roles', function ($sub) use ($unitId) {
                         $sub->whereRaw("model_has_roles.model_id = users.id AND model_has_roles.team_id = ?", [$unitId]);
                    });
                });
            }
        }

        $approvals = $query->latest('date')->get();

        return Inertia::render('Yayasan/Attendance/Approval', [
            'approvals' => $approvals,
        ]);
    }

    public function update(Request $request, EmployeeAttendance $attendance)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk memproses persetujuan absensi.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $employeeUnitId = \DB::table('model_has_roles')
                ->where('model_id', $attendance->user_id)
                ->whereNotNull('team_id')
                ->value('team_id');
            if ($employeeUnitId != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Karyawan ini bukan dari unit Anda.');
            }
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'reason' => 'required_if:status,rejected|nullable|string',
        ]);

        $attendance->update([
            'approval_status' => $request->status,
            'approved_by' => Auth::id(),
            'rejection_reason' => $request->status === 'rejected' ? $request->reason : null,
        ]);

        // Send feedback notification to the employee
        if ($attendance->user) {
            $dateFormatted = Carbon::parse($attendance->date)->translatedFormat('d F Y');
            $statusLabel = [
                'business_trip' => 'Dinas Luar',
                'sick'          => 'Sakit',
                'permit'        => 'Izin',
            ][$attendance->status] ?? $attendance->status;

            if ($request->status === 'approved') {
                NotificationDispatcher::sendToUser(
                    $attendance->user,
                    '✅ Pengajuan Absensi Disetujui',
                    "Pengajuan absensi {$statusLabel} Anda pada tanggal {$dateFormatted} telah disetujui.",
                    'employee',
                    ['attendance_id' => $attendance->id]
                );
            } else {
                $reasonText = $request->reason ? " Catatan: {$request->reason}" : '';
                NotificationDispatcher::sendToUser(
                    $attendance->user,
                    '❌ Pengajuan Absensi Ditolak',
                    "Pengajuan absensi {$statusLabel} Anda pada tanggal {$dateFormatted} tidak disetujui.{$reasonText}",
                    'employee',
                    ['attendance_id' => $attendance->id]
                );
            }
        }

        return redirect()->back()->with('success', 'Status pengajuan diperbarui.');
    }
}
